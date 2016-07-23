<?php
/**
 * Test methods for the P4Cms HeadStyle View Helper.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_View_Helper_HeadStyleTest extends TestCase
{
    protected $_helper;

    /**
     * Setup the helper and view for each test.
     */
    public function setUp()
    {
        parent::setUp();
        P4Cms_Theme::addPackagesPath(SITES_PATH . '/all/themes');
        P4Cms_PackageAbstract::setDocumentRoot(SITES_PATH);

        $this->_helper = new P4Cms_View_Helper_HeadStyle;
    }

    /**
     * Test teardown.
     */
    public function tearDown()
    {
        P4Cms_PackageAbstract::setDocumentRoot(null);
        parent::tearDown();
    }

    /**
     * Test itemToString().
     */
    public function testItemToString()
    {
        $tests = array(
            array(
                'label'       => 'test 1',
                'content'     => 'Test Content',
                'attributes'  => array('media' => 'all and (expression)'),
                'expectMedia' => 'all and (expression)'
            ),
            array(
                'label'       => 'test 2',
                'content'     => 'Test Content',
                'attributes'  => array('media' => 'only screen and (expression)'),
                'expectMedia' => 'only screen and (expression)'
            ),
            array(
                'label'       => 'test 3',
                'content'     => 'Test Content',
                'attributes'  => array('media' => 'not screen and (expression)'),
                'expectMedia' => 'not screen and (expression)'
            ),
            array(
                'label'       => 'test 4',
                'content'     => 'Test Content',
                'attributes'  => array('media' => 'all  and  ( expression )'),
                'expectMedia' => 'all  and  ( expression )'
            ),
            array(
                'label'       => 'test 5',
                'content'     => 'Test Content',
                'attributes'  => array('media' => 'all and (expression)'),
                'expectMedia' => 'all and (expression)'
            ),
            array(
                'label'       => 'test 6',
                'content'     => 'Test Content',
                'attributes'  => array('media' => '(expression)'),
                'expectMedia' => '(expression)'
            ),
            array(
                'label'       => 'test 7',
                'content'     => 'Test Content',
                'attributes'  => array('media' => 'none and (expression)'),
                'expectMedia' => 'none and (expression)'
            ),
            array(
                'label'       => 'test 8',
                'content'     => 'Test Content',
                'attributes'  => array('media' => 'onlyscreen and (expression)'),
                'expectMedia' => 'onlyscreen and (expression)'
            ),
            array(
                'label'       => 'test 9',
                'content'     => 'Test Content',
                'attributes'  => array('media' => 'notscreen and (expression)'),
                'expectMedia' => 'notscreen and (expression)'
            ),
            array(
                'label'       => 'test 10',
                'content'     => 'Test Content',
                'attributes'  => array('media' => 'screen and (expression)'),
                'expectMedia' => 'screen and (expression)'
            ),
            array(
                'label'       => 'test 11',
                'content'     => 'Test Content',
                'attributes'  => array('media' => '  screen and (expression)  '),
                'expectMedia' => '  screen and (expression)  '
            ),
            array(
                'label'       => 'test 12',
                'content'     => 'Test Content',
                'attributes'  => array('media' => 'all,  screen and (expression)  '),
                'expectMedia' => 'all,screen and (expression)'
            ),
            array(
                'label'       => 'test 13',
                'content'     => 'Test Content',
                'attributes'  => array('media' => 'only screen and (max-depth: 100px) , all,screen and (expression) '),
                'expectMedia' => 'only screen and (max-depth: 100px),all,screen and (expression)'
            ),
            array(
                'label'       => 'test 14',
                'content'     => 'Test Content',
                'attributes'  => array('media' => 'onlyscreen and (max-depth: 100px),all,screen and (expression)  '),
                'expectMedia' => 'onlyscreen and (max-depth: 100px),all,screen and (expression)'
            ),
        );
        
        foreach ($tests as $test) {
            $item = $this->_helper->createData($test['content'], $test['attributes']);
            $media = isset($test['expectMedia']) ?  " media=\"{$test['expectMedia']}\"" : '';
            $expected = <<<EOD
<style type="text/css"{$media}>
<!--
{$test['content']}
-->
</style>
EOD;
            $result = $this->_helper->itemToString($item, null);
            $this->assertSame($expected, $result, $test['label']);
        }
    }

}
