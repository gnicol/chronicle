<?php
/**
 * Test the site branch controller.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Site_Test_BranchControllerTest extends ModuleControllerTest
{
    /**
     * Test the manage action with no additional sites.
     */
    public function testManageEmpty()
    {
        $this->utility->impersonate('administrator');

        $this->dispatch('/site/branch/manage');
        $this->assertModule('site',       'Expected module, line ' . __LINE__);
        $this->assertController('branch', 'Expected controller, line ' . __LINE__);
        $this->assertAction('manage',     'Expected action, line ' . __LINE__);

        // verify that table and dojo data elements exist
        $this->assertXpath('//div[@dojotype="dojox.data.QueryReadStore"]', 'Expected dojo.data div');
        $this->assertXpath(
            '//table[@dojotype="p4cms.ui.grid.DataGrid" and @jsid="p4cms.site.branch.grid.instance"]',
            'Expected dojox.grid table'
        );

        // check JSON output
        $this->resetRequest()
             ->resetResponse();
        $this->dispatch('/site/branch/manage/format/json');

        $this->assertModule('site',       'Expected module, line ' . __LINE__);
        $this->assertController('branch', 'Expected controller, line ' . __LINE__);
        $this->assertAction('manage',     'Expected action, line ' . __LINE__);

        $body = $this->response->getBody();
        $data = Zend_Json::decode($body);

        //verify number of sites
        $this->assertSame(
            $data['numRows'],
            2,
            'Expected number of items'
        );
    }

    /**
     * Test manage active page - should redirect to manage
     */
    public function testManageActive()
    {
        $this->utility->impersonate('administrator');

        $this->dispatch('/site/branch/manage-active/format/json');
        $this->assertModule('site',       'Expected module, line ' . __LINE__);
        $this->assertController('branch', 'Expected controller, line ' . __LINE__);
        $this->assertAction('manage',     'Expected action, line ' . __LINE__);
    }

    /**
     * Test management pages with sites created.
     */
    public function testManageSites()
    {
        $this->utility->impersonate('administrator');

        $branchNames = array('fee', 'fi', 'fo', 'fum');
        $this->_createBranches($branchNames);

        $this->dispatch('/site/branch/manage/format/json');
        $this->assertModule('site',       'Expected module, line ' . __LINE__);
        $this->assertController('branch', 'Expected controller, line ' . __LINE__);
        $this->assertAction('manage',     'Expected action, line ' . __LINE__);

        $body = $this->response->getBody();
        $data = Zend_Json::decode($body);

        array_unshift($branchNames, P4Cms_Site::fetchActive()->getBranchBasename());
        array_unshift($branchNames, 'testsite');

        // verify number of sites
        $this->assertSame(
            count($branchNames),
            $data['numRows'],
            'Expected number of items'
        );

        foreach ($data['items'] as $key => $branch) {
            $this->assertSame(
                $branchNames[$key],
                $branch['name'],
                'Expected branch named ' . $branchNames[$key] . ' in position ' . $key . ', line ' . __LINE__
            );
        }
    }

    /**
     * Test the switch action
     */
    public function testSwitch()
    {
        $this->utility->impersonate('administrator');

        $this->_createBranches(array('test'));

        $this->dispatch('/site/branch/switch/sessionId/' . session_id());
        $this->assertModule('site',       'Expected module, line ' . __LINE__);
        $this->assertController('branch', 'Expected controller, line ' . __LINE__);
        $this->assertAction('switch',     'Expected action, line ' . __LINE__);

        $body        = $this->response->getBody();
        $decodedBody = Zend_Json_Decoder::decode($body);

        $this->assertSame($decodedBody['site'], 'chronicle-test', 'Expected site, line ' . __LINE__);
        $this->assertSame($decodedBody['branch'], 'live', 'Expected branch, line ' . __LINE__);
    }

    /**
     * Ensure that the proper form is returned when an add request is sent
     */
    public function testAddForm()
    {
        $this->utility->impersonate('administrator');

        $this->dispatch('/site/branch/add');
        $this->assertModule('site',       'Expected module, line ' . __LINE__);
        $this->assertController('branch', 'Expected controller, line ' . __LINE__);
        $this->assertAction('add',     'Expected action, line ' . __LINE__);

        // ensure that required form inputs are presented correctly.
        $this->assertQuery("#layout-main form",     "Expected add form.");
        $this->assertQuery("input[name='name']",    "Expected name input.");
        $this->assertQuery("select[name='site']",   "Expected site select input.");
        $this->assertQuery("select[name='parent']", "Expected parent select input.");
        $this->assertQuery(
            "select[name='parent'] option[value='//chronicle-test/live']",
            "Expected live parent option."
        );
        $this->assertQuery("input[type='submit']",  "Expected submit button.");
    }

    /**
     * Test a valid add form submission.
     */
    public function testAddGoodPost()
    {
        $this->utility->impersonate('administrator');
        // save acl so it can be loaded from the site as needed
        P4Cms_Site::fetchActive()->getAcl()->save();

        $branchName = 'test';

        // form request with required fields.
        $this->request->setMethod('POST');
        $this->request->setPost('name',   $branchName);
        $this->request->setPost('parent', '//chronicle-test/live');
        $this->request->setPost('site',   'chronicle-test');

        $this->dispatch('/site/branch/add');
        $this->assertModule('site',       'Expected module, line ' . __LINE__);
        $this->assertController('branch', 'Expected controller, line ' . __LINE__);
        $this->assertAction('add',        'Expected action, line ' . __LINE__);

        $id = '//' . P4Cms_Site::fetchActive()->getSiteId() . '/' . $branchName;

        $sites = P4Cms_Site::fetchAll();
        $this->assertSame(count($sites), 2, "Expected site count to include new branch.  " . __LINE__);

        try {
            $site = P4Cms_Site::fetch($id);
        }
        catch (P4Cms_Model_NotFoundException $e) {
            $this->fail('Expected newly added branch with id ' . $id . ' to be present.');
        }
        $stream = $site->getStream();
        $this->assertSame($stream->getName(), $branchName, 'Expected matching branch name, line ' . __LINE__);
        $this->assertSame(
            $stream->getParent(),
            P4Cms_Site::fetchActive()->getId(),
            'Expected matching branch parent, line ' . __LINE__
        );
        $this->assertSame($stream->getId(), $id, 'Expected matching branch id, line ' . __LINE__);
    }

    /**
     * Test delete action
     */
    public function testDelete()
    {
        $this->utility->impersonate('administrator');

        // create test branch
        $name   = 'test';
        $siteId = '//' . P4Cms_Site::fetchActive()->getSiteId() . '/' . $name;
        $this->_createBranches(array($name));

        $this->request->setMethod('POST');
        $this->request->setPost('id', $siteId);

        $this->dispatch('/site/branch/delete');

        $this->assertModule('site',       'Expected module, line ' . __LINE__);
        $this->assertController('branch', 'Expected controller, line ' . __LINE__);
        $this->assertAction('delete',     'Expected action, line ' . __LINE__);

        // verify deleted
        try {
            $site = P4Cms_Site::fetch($siteId);
        }
        catch (P4Cms_Model_NotFoundException $e) {
            return;
        }

        $this->fail('Expected branch with id ' . $siteId . ' to be deleted.');
    }

    /**
     * Test edit with bad branch id.
     */
    public function testEditId()
    {
        $this->utility->impersonate('administrator');

        $this->_createBranches(array('test', 'stage'));

        // try to edit with non-existing site id
        $this->request->setParam('id', '//' . P4Cms_Site::fetchActive()->getSiteId() . '/test-1');
        $this->dispatch('/site/branch/edit');
        $this->assertModule('error',     'Expected module.');
        $this->assertController('index', 'Expected controller.');
        $this->assertAction('error',     'Expected action.');

        // verify it works with existing id
        $this->resetRequest()->resetResponse();
        $this->request->setParam('id', '//' . P4Cms_Site::fetchActive()->getSiteId() . '/test');
        $this->dispatch('/site/branch/edit');

        $this->assertModule('site',       'Expected module #2.');
        $this->assertController('branch', 'Expected controller #2.');
        $this->assertAction('edit',       'Expected action #2.');
    }

    /**
     * Tets branch edit action.
     */
    public function testEditAction()
    {
        $this->utility->impersonate('administrator');

        $active = P4Cms_Site::fetchActive();

        $this->_createBranches(array('stage'));
        $stage  = P4Cms_Site::fetch('//' . $active->getSiteId() . '/stage');

        $this->_createBranches(array('test'), $stage);
        $test   = P4Cms_Site::fetch('//' . $active->getSiteId() . '/test');

        $this->assertSame(
            'test',
            $test->getStream()->getName(),
            "Expected name of the 'test' branch."
        );

        // edit 'test' branch
        $this->request->setMethod('POST');
        $this->request->setPost(
            array(
                'id'            => $test->getId(),
                'name'          => 'review',
                'description'   => 'review stage',
                'parent'        => '//' . $active->getSiteId() . '/live'
            )
        );
        $this->dispatch('/site/branch/edit');

        $this->assertModule('site',       'Expected module.');
        $this->assertController('branch', 'Expected controller.');
        $this->assertAction('edit',       'Expected action.');


        // verify that 'test' branch has been updated
        $branch = P4Cms_Site::fetch($test->getId());
        $stream = $branch->getStream();

        $this->assertSame(
            'review',
            $stream->getName(),
            "Expected branch name."
        );

        $this->assertSame(
            'review stage',
            trim($stream->getDescription()),
            "Expected branch description."
        );

        // ensure that parent has been changed as requested
        $this->assertSame(
            '//' . $active->getSiteId() . '/live',
            $stream->getParent(),
            "Expected changed branch parent."
        );
    }

    /**
     * Test for the pull details action with no params; expects an InvalidArgumentException.
     */
    public function testPullDetailsNoParams()
    {
        $this->utility->impersonate('administrator');

        $this->dispatch('/site/branch/pull-details');

        $this->assertModule('error',     'Expected error module.');
        $this->assertController('index', 'Expected controller.');
        $this->assertAction('error',     'Expected error action.');

        $this->assertQueryContentContains(
            'dl dd',
            'InvalidArgumentException',
            'Expected exception is not present, line '. __LINE__
        );
    }

    /**
     * Test for the pull details action with valid params
     */
    public function testPullDetailsMerge()
    {
        $this->utility->impersonate('administrator');

        // create test branch
        $name = 'test';
        $this->_createBranches(array($name));

        // switch to test branch, add some content, switch back
        P4Cms_Site::fetch('//' . P4Cms_Site::fetchActive()->getSiteId() . '/' . $name)->load();
        $this->_createContentChanges();
        P4Cms_Site::fetch('//' . P4Cms_Site::fetchActive()->getSiteId() . '/' . 'live')->load();

        $query = http_build_query(
            array(
                'groupId'   => 'content',
                'source'    => '//' . P4Cms_Site::fetchActive()->getSiteId() . '/' . $name,
                'paths'     => array('content', 'content/unpublished-entries', 'content/types')
            )
        );

        $this->dispatch('/site/branch/pull-details/?' . $query);

        $body = $this->response->getBody();

        $this->assertModule('site',         'Expected site module.  ' . $body);
        $this->assertController('branch',   'Expected branch controller.  ' . $body);
        $this->assertAction('pull-details', 'Expected pull-details action.  ' . $body);

        $this->assertQueryContentContains(
            'tbody tr td',
            'Test Type',
            'Expected content type is not present.  ' . $body
        );

        $this->assertQueryContentContains(
            'tbody tr td',
            'Title!',
            'Expected content entry title is not present.  ' . $body
        );
    }

    /**
     * Tests pull action with no changes to pull.
     */
    public function testPullFormEmpty()
    {
        $this->utility->impersonate('administrator');

        $this->_createBranches(array('test'));

        $this->dispatch(
            '/site/branch/pull/format/partial/source/'
            . urlencode('//' . P4Cms_Site::fetchActive()->getSiteId() . '/test')
        );

        $this->assertModule('site',       'Expected module.');
        $this->assertController('branch', 'Expected controller.');
        $this->assertAction('pull',       'Expected action.');

        // ensure that required form inputs are presented correctly.
        $this->assertQuery("form.pull-form",           "Expected pull form.");
        $this->assertQuery("input[name='headChange']", "Expected headChange input.");
        $this->assertQuery("select[name='source']",   "Expected sourceselect input.");
        $this->assertQuery("input[name='target'][readOnly='1']", "Expected readonly target input.");
        $this->assertQuery("input[type='radio'][name='mode']",  "Expected mode radio selector.");

        // Expect 2 paths (Configuration parent and Permissions child) due to acl.
        $this->assertQueryCount("input[type='checkbox'][name='paths[]']", 2, "Expected 2 paths available to pull.");
        $this->assertQueryContentContains(
            "ul.nested-checkbox li",
            "Configuration",
            "Expected Configuration pull path label.  " . $this->response->getBody()
        );
        $this->assertQueryContentContains(
            "ul.nested-checkbox li",
            "Permissions",
            "Expected Permissions pull path label.  " . $this->response->getBody()
        );
    }

    /**
     * Tests pull action with a merge request
     */
    public function testPullFormChanges()
    {
        $this->utility->impersonate('administrator');

        // create test branch
        $name = 'test';
        $this->_createBranches(array($name));

        // switch to test branch, add some content, switch back
        P4Cms_Site::fetch('//' . P4Cms_Site::fetchActive()->getSiteId() . '/' . $name)->load();
        $this->_createContentChanges();
        P4Cms_Site::fetch('//' . P4Cms_Site::fetchActive()->getSiteId() . '/' . 'live')->load();

        // pull content from test branch to live
        $this->dispatch(
            '/site/branch/pull/format/partial/source/'
            . urlencode('//chronicle-test/test')
        );

        $this->assertModule('site',       'Expected module.');
        $this->assertController('branch', 'Expected controller.');
        $this->assertAction('pull',       'Expected action.');

        $body = $this->response->getBody();

        // ensure that required form inputs are presented correctly.
        $this->assertQuery("form.pull-form",                      "Expected pull form." . $body);
        $this->assertQuery("input[name='headChange'][value='5']", "Expected headChange input with value 5." . $body);
        $this->assertQuery("select[name='source']",               "Expected source select." . $body);
        $this->assertQuery("input[name='target'][readOnly='1']",  "Expected readonly target input." . $body);
        $this->assertQuery("input[type='radio'][name='mode']",    "Expected mode radio selector." . $body);

        // expect 5 paths: Content parent, Published Entries and Type children; Configuration parent, Permissions child
        $this->assertQueryCount(
            "input[type='checkbox'][name='paths[]']",
            5,
            "Expected 5 paths available to pull." . $body
        );
    }

    /**
     * Tests pull action with a copy request
     */
    public function testPullCopy()
    {
        $this->utility->impersonate('administrator');

        // create test branch
        $name = 'test';
        $this->_createBranches(array($name));

        // switch to test branch, add some content, switch back
        P4Cms_Site::fetch('//' . P4Cms_Site::fetchActive()->getSiteId() . '/' . $name)->load();
        $this->_createContentChanges();
        P4Cms_Site::fetch('//' . P4Cms_Site::fetchActive()->getSiteId() . '/' . 'live')->load();
        $this->_createContentChanges();

        // modify content to ensure difference between branches
        P4Cms_Content::fetch("test")->setValue('title', 'Test?')->setValue('body', str_repeat(':', 25))->save();

        $change = P4_Change::fetchAll(
            array(
                P4_Change::FETCH_BY_STATUS => P4_Change::SUBMITTED_CHANGE,
                P4_Change::FETCH_MAXIMUM   => 1
            ),
            P4Cms_User::fetchActive()->getPersonalAdapter()->getConnection()
        )->last()->getId();

        $this->request->setMethod('POST');
        $this->request->setPost(
            array(
                'headChange' => $change,
                'source'     => '//' . P4Cms_Site::fetchActive()->getSiteId() . '/' . $name,
                'target'     => 'live',
                'mode'       => Site_Form_Pull::MODE_COPY,
                'paths'      => array('content', 'content/published-entries', 'content/types')
            )
        );

        $this->dispatch('/site/branch/pull/format/json');

        $this->assertModule('site',       'Expected module.');
        $this->assertController('branch', 'Expected controller.');
        $this->assertAction('pull',       'Expected action.');

        $body = $this->response->getBody();
        $data = Zend_Json::decode($body);

        $this->assertSame(
            $data['severity'],
            'success',
            'Expected successful pull.  ' . print_r($data['errors'], true)
        );
        $this->assertSame(
            $data['message'],
            "Pulled 2 items from 'test'.",
            'Expected message indicating successful pull of 2 records: ' . $data['message']
        );
    }

    /**
     * Tests pull action with a merge request
     *
     * @todo - add content to live and ensure it's not there.
     */
    public function testPullMerge()
    {
        $this->utility->impersonate('administrator');

        // create test branch
        $name = 'test';
        $this->_createBranches(array($name));

        // switch to test branch, add some content, switch back
        P4Cms_Site::fetch('//' . P4Cms_Site::fetchActive()->getSiteId() . '/' . $name)->load();
        $this->_createContentChanges();
        P4Cms_Site::fetch('//' . P4Cms_Site::fetchActive()->getSiteId() . '/' . 'live')->load();

        $change = P4_Change::fetchAll(
            array(
                P4_Change::FETCH_BY_STATUS => P4_Change::SUBMITTED_CHANGE,
                P4_Change::FETCH_MAXIMUM   => 1
            ),
            P4Cms_User::fetchActive()->getPersonalAdapter()->getConnection()
        )->last()->getId();

        $this->request->setMethod('POST');
        $this->request->setPost(
            array(
                'headChange' => $change,
                'source'     => '//' . P4Cms_Site::fetchActive()->getSiteId() . '/' . $name,
                'target'     => 'live',
                'mode'       => Site_Form_Pull::MODE_MERGE,
                'paths'      => array('content', 'content/published-entries', 'content/types')
            )
        );

        $this->dispatch('/site/branch/pull/format/json');

        $this->assertModule('site',       'Expected module.');
        $this->assertController('branch', 'Expected controller.');
        $this->assertAction('pull',       'Expected action.');

        $body = $this->response->getBody();
        $data = Zend_Json::decode($body);

        $this->assertSame($data['severity'], 'success', 'Expected successful pull.  ' . print_r($data, true));
        $this->assertSame(
            $data['message'],
            "Pulled 2 items from 'test'.",
            'Expected message indicating successful pull of 2 records: ' . $data['message']
        );
    }

    /**
     * Adds a content type and a content entry to the current active site.
     */
    protected function _createContentChanges()
    {
        $type = new P4Cms_Content_Type;
        $type->setId('test-type')
             ->setLabel('Test Type')
             ->setElements(
                array(
                    "title" => array(
                        "type"      => "text",
                        "options"   => array("label" => "Title", "required" => true)
                    ),
                    "body"  => array(
                        "type"      => "textarea",
                        "options"   => array("label" => "Body")
                    )
                )
             )
             ->setValue('icon', file_get_contents(TEST_ASSETS_PATH . "/images/content-type-icon.png"))
             ->setFieldMetadata('icon', array("mimeType" => "image/png"))
             ->setValue('group', 'test')
             ->save();

        $entry = new P4Cms_Content;
        $entry->setId("test")
            ->setValue('contentType', 'test-type')
            ->setValue('title',       "Title!")
            ->setValue('body',        str_repeat('.', 25))
            ->save();
    }

    /**
     * Creates several branches of the given site for testing (does not copy
     * any data from parent).
     *
     * @param   array       $branches   list of branches names to create for the given site.
     * @param   P4Cms_Site  $site       optional - site to create branches for (active site
     *                                  if not provided).
     */
    protected function _createBranches(array $branches, P4Cms_Site $site = null)
    {
        $site = $site ?: P4Cms_Site::fetchActive();

        foreach ($branches as $name) {
            $stream = new P4_Stream();
            $stream->setId('//' . $site->getSiteId() . '/' . $name)
                   ->setName($name)
                   ->setDescription('Description for ' . $name)
                   ->setParent($site->getId())
                   ->setType('development')
                   ->setOwner(P4Cms_User::fetchActive()->getId())
                   ->setPaths('share ...')
                   ->save();

            $newSite = P4Cms_Site::fetch($stream->getId());
            $acl     = $newSite->getAcl();
            $acl->installDefaults()->save();
        }
    }
}
