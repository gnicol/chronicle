<?php
/**
 * Test the content module's acl assertions.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Test_AclAssertsTest extends ModuleTest
{
    /**
     * Test the is owner acl assertion.
     */
    public function testIsOwner()
    {
        $acl        = new Zend_Acl;
        $role       = new Zend_Acl_Role('editor');
        $resource   = new Zend_Acl_Resource('content');
        $privilege  = 'does-not-matter';

        // assert instance
        $isOwner = new Content_Acl_Assert_IsOwner();

        // active user
        $user = new P4Cms_User;
        $user->setId('joe');
        P4Cms_User::setActive($user);

        // non-content resource should return false.
        $resource = new Zend_Acl_Resource('lasdfjk');
        $this->assertFalse(
            $isOwner->assert($acl, $role, $resource, $privilege),
            'Unexpected isOwner = true with non-content resource.'
        );

        // content resource with no id should return false.
        $resource = new Zend_Acl_Resource('content');
        $this->assertFalse(
            $isOwner->assert($acl, $role, $resource, $privilege),
            'Unexpected isOwner = true with non-content resource.'
        );
        $resource = new Zend_Acl_Resource('content/');
        $this->assertFalse(
            $isOwner->assert($acl, $role, $resource, $privilege),
            'Unexpected isOwner = true with non-content resource.'
        );

        // no active user should return false
        P4Cms_User::clearActive();
        $this->assertFalse(
            $isOwner->assert($acl, $role, $resource, $privilege),
            'Unexpected isOwner = true with no active user.'
        );

        // anonymous user should return false
        $user->setId(null);
        P4Cms_User::setActive($user);
        $this->assertFalse(
            $isOwner->assert($acl, $role, $resource, $privilege),
            'Unexpected isOwner = true with anonymous user.'
        );

        // content resource with invalid id should return false.
        $resource = new Zend_Acl_Resource('content/123');
        $this->assertFalse(
            $isOwner->assert($acl, $role, $resource, $privilege),
            'Unexpected isOwner = true with non-existent content resource.'
        );

        // make content entry.
        P4Cms_Content::store(
            array('id' => 1, 'title' => 'test', 'contentOwner' => 'tester')
        );

        // valid content resource, but not owner should return false.
        $user->setId('joe');
        P4Cms_User::setActive($user);
        $resource = new Zend_Acl_Resource('content/1');
        $this->assertFalse(
            $isOwner->assert($acl, $role, $resource, $privilege),
            'Unexpected isOwner = true when user not owner.'
        );

        // valid owner should return true.
        $user->setId('tester');
        P4Cms_User::setActive($user);
        $resource = new Zend_Acl_Resource('content/1');
        $this->assertTrue(
            $isOwner->assert($acl, $role, $resource, $privilege),
            'Expected isOwner = true when user is owner.'
        );
    }

    /**
     * Test the can edit acl assertion.
     *
     * @param   string  $privilege      optional - the privilege to test defaults
     *                                  to 'edit', pass 'delete' to test CanDelete
     * @param   string  $privilegeAll   optional - 'superior' privilege to test
     *                                  defaults to 'edit-all', pass 'delete-any'
     *                                  to test CanDelete
     */
    public function testCanEdit($privilege = 'edit', $privilegeAll = 'edit-all')
    {
        $acl        = new Zend_Acl;
        $author     = new Zend_Acl_Role('author');
        $editor     = new Zend_Acl_Role('editor');
        $resource   = new Zend_Acl_Resource('content');

        // assert instance
        $canDo = new P4Cms_Acl_Assert_Proxy(
            "Content_Acl_Assert_Can" . ucfirst($privilege)
        );

        // active user
        $user = new P4Cms_User;
        $user->setId('joe');
        P4Cms_User::setActive($user);

        // configure acl.
        $acl->addRole($author);
        $acl->addRole($editor);
        $acl->addResource($resource);
        $acl->allow($author, $resource, $privilege . '-own');
        $acl->allow($editor, $resource, $privilegeAll);

        // non-content resource should return false.
        $resource = new Zend_Acl_Resource('lasdfjk');
        $this->assertFalse(
            $canDo->assert($acl, $editor, $resource, $privilege),
            'Unexpected canDo = true with non-content resource.'
        );

        // non-content resource should return false.
        $resource = new Zend_Acl_Resource('contentkasdjf');
        $this->assertFalse(
            $canDo->assert($acl, $editor, $resource, $privilege),
            'Unexpected can ' . $privilege . ' = true with non-content resource.'
        );

        // editor role (ie. edit-all) should return true.
        $resource = new Zend_Acl_Resource('content');
        $this->assertTrue(
            $canDo->assert($acl, $editor, $resource, $privilege),
            'Unexpected can ' . $privilege . ' = false with editor role.'
        );

        // author (ie. edit-own) should return false for 'content' resource
        $resource = new Zend_Acl_Resource('content');
        $this->assertFalse(
            $canDo->assert($acl, $author, $resource, $privilege),
            'Unexpected can ' . $privilege . ' = true with author role.'
        );

        // author should return false for non-existent 'content' resource
        $resource = new Zend_Acl_Resource('content/1');
        $this->assertFalse(
            $canDo->assert($acl, $author, $resource, $privilege),
            'Unexpected can ' . $privilege . ' = true with non-existent content.'
        );

        // author some content.
        P4Cms_Content::store(
            array('id' => 1, 'title' => 'test', 'contentOwner' => 'joe')
        );

        // author should return true for owned content.
        $resource = new Zend_Acl_Resource('content/1');
        $this->assertTrue(
            $canDo->assert($acl, $author, $resource, $privilege),
            'Unexpected can ' . $privilege . ' = false for owned content.'
        );

        // switch id of active user to be different from content owner.
        P4Cms_User::fetchActive()->setId($this->p4->getUser());

        // author should return false for un-owned content.
        $resource = new Zend_Acl_Resource('content/1');
        $this->assertFalse(
            $canDo->assert($acl, $author, $resource, $privilege),
            'Unexpected can ' . $privilege . ' = true with un-owned content.'
        );
    }

    /**
     * Test can delete behavior. Should behave exactly like
     * can edit, just with delete privilege instead of edit.
     */
    public function testCanDelete()
    {
        $this->testCanEdit('delete', 'delete-any');
    }
}
