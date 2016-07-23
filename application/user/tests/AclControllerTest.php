<?php
/**
 * Test the user acl controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class User_Test_AclControllerTest extends ModuleControllerTest
{
    /**
     * Set the active user and install default acl prior to each test.
     */
    public function setUp()
    {
        parent::setUp();

        // install default ACL
        $acl = P4Cms_Site::fetchActive()->getAcl();
        $acl->installDefaults()->save();
    }

    /**
     * Test acl grid view
     */
    public function testIndexAction()
    {
        $this->utility->impersonate('administrator');

        // verify that acl grid is accessible
        $this->dispatch('/user/acl');

        $this->assertModule('user', 'Expected module for dispatching /user/acl action.');
        $this->assertController('acl', 'Expected controller for dispatching /user/acl action.');
        $this->assertAction('index', 'Expected action for dispatching /user/acl action.');

        // verify that table and dojo data elements exist
        $this->assertXpath('//div[@dojotype="dojox.data.QueryReadStore"]', 'Expected dojo.data div');
        $this->assertXpath(
            '//table[@dojotype="p4cms.ui.grid.DataGrid" and @jsid="p4cms.user.acl.grid.instance"]',
            'Expected dojox.grid table.'
        );

        // verify save and restore buttons appear
        $this->assertQueryContentContains(
            "div.button button",
            "Save Changes",
            "Expected existence of save button."
        );
        $this->assertQueryContentContains(
            "div.button button",
            "Reset to Defaults",
            "Expected existence of save button."
        );

        // check initial JSON output
        $this->resetRequest()
             ->resetResponse();
        $this->dispatch('/user/acl/format/json');
        $body = $this->response->getBody();
        $this->assertModule('user', 'Expected module, dispatch #2. '. $body);
        $this->assertController('acl', 'Expected controller, dispatch #2 '. $body);
        $this->assertAction('index', 'Expected action, dispatch #2 '. $body);

        $data = Zend_Json::decode($body);

        // check number of resources and privileges
        $resources  = array();
        $privileges = array();
        foreach ($data['items'] as $item) {
            if ($item['type'] == 'resource') {
                $resources[] = $item;
            } else if ($item['type'] == 'privilege') {
                $privileges[] = $item;
            }
        }

        $this->assertSame(
            9,
            count($resources),
            "Expected matching number of resources."
        );

        $this->assertSame(
            34,
            count($privileges),
            "Expected matching number of privileges."
        );
    }

    /**
     * Test default rules rendering in the view
     */
    public function testDefaultRules()
    {
        $this->utility->impersonate('administrator');

        // get default rules
        $rules = $this->_getRules();

        // decode from json
        $rules = Zend_Json::decode($rules);

        // verify that administrator rules are defined
        $this->assertTrue(
            isset($rules['administrator']),
            "Expected rules for administrator role are defined."
        );

        // verify that member rules are defined
        $this->assertTrue(
            isset($rules['member']),
            "Expected rules for member role are defined."
        );

        // verify that anonymous rules are defined
        $this->assertTrue(
            isset($rules['anonymous']),
            "Expected rules for anonymous role are defined."
        );

        // verify that rules are defined for all roles
        $roles = P4Cms_Acl_Role::fetchAll()->invoke('getId');
        $this->assertSame(
            count($roles),
            count($rules),
            "Expected rules are defined for all roles."
        );
        foreach ($roles as $role) {
            $this->assertTrue(
                array_key_exists($role, $rules),
                "Expected rules for $role role."
            );
        }

        foreach ($rules['administrator'] as $resource => $privilleges) {
            foreach ($privilleges as $privillege => $rule) {
                $this->assertTrue(
                    $rule['allowed'],
                    "Expected administrator is allowed for $resource/$privillege."
                );
            }
        }
    }

    /**
     * Test save action
     */
    public function testSaveGoodPost()
    {
        $this->utility->impersonate('administrator');

        // get default rules
        $rules = Zend_Json::decode($this->_getRules());

        // negate member rules
        $memberRules = array();
        foreach ($rules['member'] as $resource => $privileges) {
            foreach ($privileges as $privilege => $rule) {

                // save expected rules for member after save (expect that disabled
                // rules won't be changed)
                $expectedMemberRules[$resource][$privilege] =
                    $rule['disabled']
                      ? $rule['allowed']
                      : !$rule['allowed'];

                $rules['member'][$resource][$privilege]['allowed'] = !$rule['allowed'];
            }
        }

        // save new rules
        $this->request->setMethod('POST');
        $this->request->setPost(
            array(
                'rules' => Zend_Json::encode($rules)
            )
        );
        $this->dispatch('/user/acl/save/format/json');

        $this->assertModule('user', 'Expected module when acl is saved.');
        $this->assertController('acl', 'Expected controller when acl is saved.');
        $this->assertAction('save', 'Expected action when acl is saved.');

        // grab acl and check rules for member
        $helper = Zend_Controller_Action_HelperBroker::getExistingHelper('acl');
        $acl    = $helper->getAcl();

        $this->assertTrue(
            $acl instanceof P4Cms_Acl,
            "Expected acl is instance of P4Cms_Acl."
        );

        // verify that member rules have been changed
        foreach ($expectedMemberRules as $resource => $privileges) {
            foreach ($privileges as $privilege => $rule) {
                $this->assertSame(
                    $rule,
                    $acl->isAllowed('member', $resource, $privilege),
                    "Expected acl allowed for member role, $resource resource and $privilege privilege."
                );
            }
        }
    }

    /**
     * Test reset action
     */
    public function testRestoreDefaults()
    {
        $this->utility->impersonate('administrator');

        // get default rules
        $defaultRules = $this->_getRules();

        // disable all member rules and save through post
        $rules = Zend_Json::decode($defaultRules);
        foreach ($rules['member'] as $resource => $privileges) {
            foreach ($privileges as $privilege => $rule) {
                $rules['member'][$resource][$privilege]['allowed'] = false;
            }
        }

        $this->request->setMethod('POST');
        $this->request->setPost(
            array(
                'rules' => Zend_Json::encode($rules)
            )
        );
        $this->dispatch('/user/acl/save/format/json');

        // verify that rules have been changed
        $helper = Zend_Controller_Action_HelperBroker::getExistingHelper('acl');
        $acl    = $helper->getAcl();

        foreach ($rules['member'] as $resource => $privileges) {
            foreach ($privileges as $privilege => $rule) {
                $this->assertSame(
                    false,
                    $acl->isAllowed('member', $resource, $privilege),
                    "Expected acl disabled access for member role, $resource resource and $privilege privilege."
                );
            }
        }

        // restore defaults
        $this->resetRequest()->resetResponse();
        $this->dispatch('/user/acl/reset');

        $this->assertModule('user', 'Expected module when acl reset.');
        $this->assertController('acl', 'Expected controller when acl reset.');
        $this->assertAction('reset', 'Expected action when acl reset.');

        $expectedRules = Zend_Json::decode($defaultRules);
        foreach ($rules['member'] as $resource => $privileges) {
            foreach ($privileges as $privilege => $rule) {
                $this->assertSame(
                    $expectedRules['member'][$resource][$privilege]['allowed'],
                    $acl->isAllowed('member', $resource, $privilege),
                    "Expected default access for member role, $resource resource and $privilege privilege."
                );
            }
        }

        $this->assertSame(
            $defaultRules,
            $this->_getRules(),
            "Expected rules after reset."
        );
    }

    /**
     * Return rules as they are rendered in the view.
     */
    protected function _getRules()
    {
        $this->resetRequest()->resetResponse();
        $this->dispatch("/user/acl");
        $body = $this->response->getBody();

        // cut rules from the response body
        $pattern  = "p4cms.user.acl.grid.rules =";
        $startPos = strpos($body, $pattern);
        $length   = strpos(substr($body, $startPos), "</script>");
        $rules    = substr($body, $startPos + strlen($pattern), $length - strlen($pattern));
        $rules    = trim($rules);

        //remove trailing semicolon
        return rtrim($rules, ";");
    }

}
