<?php
/**
 * Test methods for the P4Cms HeadLink View Helper.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Ui_Controller_Helper_HelpUrlTest extends ModuleTest
{
    /**
     * Test helpUrl
     */
    public function testHelpUrl()
    {
        $originalBaseUrl = Zend_Controller_Front::getInstance()->getRequest()->getBaseUrl();
        $helpDefaultPage = Ui_Controller_Helper_HelpUrl::HELP_DEFAULT_PAGE;
        $helpBaseUrl     = Ui_Controller_Helper_HelpUrl::HELP_BASE_URL;
        $helpCookie      = Ui_Controller_Helper_HelpUrl::HELP_COOKIE;

        $tests = array(
            array(
                'label'     => __LINE__ .': no url',
                'url'       => null,
                'expected'  => '/'. $helpBaseUrl . '/' . $helpDefaultPage,
            ),
            array(
                'label'     => __LINE__ .': relative url',
                'url'       => 'help.html',
                'expected'  => "/". $helpBaseUrl ."/help.html",
            ),
            array(
                'label'     => __LINE__ .': relative url with custom base url',
                'url'       => 'help.html',
                'expected'  => "http://site.help/". $helpBaseUrl ."/help.html",
                'baseUrl'   => 'http://site.help'
            ),
            array(
                'label'     => __LINE__ .': absolute url',
                'url'       => 'http://help.html',
                'expected'  => "http://help.html",
            ),
            array(
                'label'     => __LINE__ .': cookie url when no URL specified',
                'url'       => null,
                'cookie'    => 'http://cookie-url.com/',
                'expected'  => "http://cookie-url.com/",
            ),
            array(
                'label'     => __LINE__ .': cookie url when URL specified',
                'url'       => 'help.html',
                'cookie'    => 'http://cookie-url.com/',
                'expected'  => "/". $helpBaseUrl ."/help.html",
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            if (array_key_exists('baseUrl', $test)) {
                Zend_Controller_Front::getInstance()->getRequest()->setBaseUrl($test['baseUrl']);
            }
            if (array_key_exists('cookie', $test)) {
                $_COOKIE[$helpCookie] = $test['cookie'];
            }

            $helper = new Ui_Controller_Helper_HelpUrl;
            $helper->setUrl($test['url']);
            $this->assertEquals($test['expected'], $helper->getUrl(), "$label - Expected output");

            if (array_key_exists('baseUrl', $test)) {
                Zend_Controller_Front::getInstance()->getRequest()->setBaseUrl($originalBaseUrl);
            }
            if (array_key_exists('cookie', $test)) {
                unset($_COOKIE[$helpCookie]);
            }
        }
    }

}
