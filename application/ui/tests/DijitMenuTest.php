<?php
/**
 * Test methods for the P4Cms DijitMenu Navigation View Helper.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Ui_Test_DijitMenuTest extends ModuleTest
{
    /**
     * Verify nested menu gets properly output
     */
    public function testRenderNestedMenu()
    {
        $view   = Zend_Layout::getMvcInstance()->getView();
        $helper = $view->navigation()->findHelper('dijitMenu');

        $output = $helper->renderMenu(
            new P4Cms_Navigation(
                array(
                    array(
                        'label'     => 'Test',
                        'uri'       => 'http://example.com',
                        'class'     => 'blahIcon',
                        'onShow'    => 'console.log("show");',
                        'pages'     => array(
                            array(
                                'label'     => 'Test2',
                                'uri'       => '',
                                'onClick'   => 'alert("hello!");',
                                'class'     => 'blahIcon',
                                'onShow'    => 'console.log("show");'
                            )
                        )
                    )
                )
            ),
            array(
                'attribs' => array(
                    'id'  => 'test',
                    'style' => 'display:none;'
                )
            )
        );

        $expected = '<div dojoType="p4cms.ui.Menu" id="dijitmenu-test" style="display:none;"'
                  . ' wrapperClass=" level-0">' . "\n"
                  . '        <div dojotype="dijit.PopupMenuItem"><span>Test</span>' . "\n"
                  . '        <div dojoType="p4cms.ui.Menu" wrapperClass=" level-1">' . "\n"
                  . '                <div dojotype="dijit.MenuItem" iconClass="blahIcon">'
                  . '<script type="dojo/connect" event="onClick">alert("hello!");</script>'
                  . '<script type="dojo/connect" event="onShow" args="menuItem,menu">'
                  . 'console.log("show");</script>Test2</div>' . "\n"
                  . '        </div>' . "\n"
                  . '        </div>' . "\n"
                  . '</div>';

        $this->assertSame($expected, $output, 'Expected matching output.');
    }

    /**
     * Verify flat menu gets properly output
     */
    public function testRenderFlatMenu()
    {
        $view   = Zend_Layout::getMvcInstance()->getView();
        $helper = $view->navigation()->findHelper('dijitMenu');

        $output = $helper->renderMenu(
            new P4Cms_Navigation(
                array(
                    array(
                        'label'     => 'Test',
                        'uri'       => 'http://example.com',
                        'class'     => 'blahIcon',
                        'onShow'    => 'console.log("show");',
                    ),
                    array(
                        'label'     => '-'
                    ),
                    array(
                        'label'     => '--'
                    ),
                    array(
                        'label'     => 'Test2',
                        'uri'       => '',
                        'onClick'   => 'alert("hello!");',
                        'class'     => 'blahIcon',
                        'onShow'    => 'console.log("show");'
                    )
                )
            ),
            array(
                'attribs' => array(
                    'id'  => 'test',
                    'style' => 'display:none;'
                )
            )
        );

        $expected = '<div dojoType="p4cms.ui.Menu" id="dijitmenu-test" style="display:none;"'
                  . ' wrapperClass=" level-0">' . "\n"
                  . '        <div dojotype="dijit.MenuItem" iconClass="blahIcon">'
                  . '<script type="dojo/connect" event="onClick">window.location = "http:\/\/example.com"'
                  . '</script><script type="dojo/connect" event="onShow" args="menuItem,menu">'
                  . 'console.log("show");</script>Test</div>' . "\n"
                  . '        <div dojotype="dijit.MenuSeparator"></div>' . "\n"
                  . '        <div dojotype="dijit.MenuSeparator"></div>' . "\n"
                  . '        <div dojotype="dijit.MenuItem" iconClass="blahIcon">'
                  . '<script type="dojo/connect" event="onClick">alert("hello!");</script>'
                  . '<script type="dojo/connect" event="onShow" args="menuItem,menu">'
                  . 'console.log("show");</script>Test2</div>' . "\n"
                  . '</div>';

        $this->assertSame($expected, $output, 'Expected matching output.');
    }
}
