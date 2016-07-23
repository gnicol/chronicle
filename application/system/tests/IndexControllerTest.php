<?php
/**
 * Test the system module index controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class System_Test_IndexControllerTest extends ModuleControllerTest
{
    /**
     * Test the index action by verifying that all major headings show up properly.
     * Specific data is not tested as it may vary by platform.
     */
    public function testIndex()
    {
        $this->utility->impersonate('administrator');
        $this->dispatch('/system/index/index');

        $body = $this->getResponse()->getBody();

        $this->assertModule('system', 'Expected module. '. $body);
        $this->assertController('index', 'Expected controller. '. $body);
        $this->assertAction('index', 'Expected action. '. $body);

        $this->assertQuery(
            'span.systemInformationUpper',
            'Expected system information upper information.' . $body
        );
        $this->assertQuery(
            'div.details[title="General"]',
            'Expected General details container.' . $body
        );
        $this->assertQuery(
            'div.details[title="Modules"]',
            'Expected Modules details container.' . $body
        );
        $this->assertQuery(
            'div.details[title="Themes"]',
            'Expected Themes details container.' . $body
        );
        $this->assertXpath(
            '//div[@title="Requirements Check"]',
            'Expected Requirements Check container.' . $body
        );

        $statusChecks = array(
            'version', 'ActiveSite', 'AllSites', 'Zend', 'simplediff', 'P4Cms', 'P4', 'indexphp',
            'Bootstrapphp', 'ServerRoot', 'ServerDate', 'ServerVersion', 'CaseHandling', 'ClientVersion',
            'P4PORT', 'UserName', 'ClientHost', 'ClientAddress', 'PeerAddress', 'ServerLicense',
            'IsLicensed', 'UserLimit', 'ClientLimit', 'FileLimit'
        );

        $modules = P4Cms_Module::fetchAll()->sortBy(
            array(
                array('core', array(P4_Model_Iterator::SORT_DESCENDING)),
                array('name', array(P4_Model_Iterator::SORT_NATURAL))
            )
        )->invoke('getId');

        $themes = P4Cms_Theme::fetchAll()->invoke('getId');

        foreach (array_merge($statusChecks, $modules, $themes) as $status) {
            $status = preg_replace('/[^a-zA-Z0-9]*/', '', $status);
            $this->assertQuery(
                'div#' . $status . '-status',
                'Expected ' . $status . ' status information.  ' . $body
            );
        }
    }

    /**
     * Test the fetchMd5 static method.
     */
    public function testFetchMd5()
    {
        $path = __DIR__ . '/testmd5';
        $response = System_Module::fetchMd5($path);
        $this->assertSame(file_get_contents($path), $response);

        $response = System_Module::fetchMd5($path, true);
        $this->assertNotSame(file_get_contents($path), $response);

        $lines = explode("\n", $response);
        $this->assertSame(
            '18f03905521725972ff6e844318ba791',
            $lines[0],
            'Failed to obtain matching md5.' . $response
        );
    }

    /**
     * Dist build generates the md5 file.
     */
    public function testMd5()
    {
        $this->utility->impersonate('administrator');

        // test with valid md5 data, will result in success
        $this->dispatch('/system/index/md5/format/json/target/dependent/type/module');

        $body = $this->response->getBody();

        $this->assertModule('system', 'Expected module. '. $body);
        $this->assertController('index', 'Expected controller. '. $body);
        $this->assertAction('md5', 'Expected action. '. $body);

        $data = Zend_Json::decode($body);

        $this->assertSame(
            'good',
            $data['displayClass'],
            'Expected displayClass "good".  Response: ' . print_r($data, true)
        );

        $this->assertSame(
            'MD5 check ok',
            $data['details'][0],
            'Expected success message.  Response: ' . print_r($data, true)
        );

        // test with missing md5 data, will result in warning
        $this->resetRequest()->resetResponse();

        // content module has no md5 file on test platform
        $this->dispatch('/system/index/md5/format/json/target/page/type/module');

        $body = $this->response->getBody();

        $this->assertModule('system', 'Expected module. '. $body);
        $this->assertController('index', 'Expected controller. '. $body);
        $this->assertAction('md5', 'Expected action. '. $body);

        $data = Zend_Json::decode($body);

        $this->assertSame('warn', $data['displayClass'], 'Expected displayClass "warn".');
        $this->assertSame(
            'No MD5 sums are available to check for this module.',
            $data['details'][0],
            'Expected warning message.'
        );

        // test with bad md5 data, will result in fail.
        $this->resetRequest()->resetResponse();

        $this->dispatch('/system/index/md5/format/json/target/independent/type/module');

        $body = $this->response->getBody();

        $this->assertModule('system', 'Expected module. '. $body);
        $this->assertController('index', 'Expected controller. '. $body);
        $this->assertAction('md5', 'Expected action. '. $body);

        $data = Zend_Json::decode($body);

        $this->assertSame('bad', $data['displayClass'], 'Expected displayClass "bad". ');
        $this->assertSame(
            1,
            preg_match('/badmd5data\.entry/', $data['details'][0]),
            'Expected failure details.'
        );

        // test with invalid type
        $this->resetRequest()->resetResponse();
        $this->dispatch('/system/index/md5/format/json/target/independent/type/failure');

        $this->assertModule('error', 'Expected module. '. $body);
        $this->assertController('index', 'Expected controller. '. $body);
        $this->assertAction('error', 'Expected action. '. $body);
    }
}
