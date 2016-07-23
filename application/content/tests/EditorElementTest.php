<?php
/**
 * Test methods for the content editor form element.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Test_EditorElementTest extends ModuleTest
{
    /**
     * Test instantiation.
     */
    public function testInstantiation()
    {
        $element = new Content_Form_Element_Editor('test');
        $this->assertTrue($element instanceof Content_Form_Element_Editor, 'Expected class');
    }

    /**
     * Verify extra plugin functionality
     */
    public function testExtraPlugins()
    {
        $element = new Content_Form_Element_Editor('test');
        $initialPlugins = array('test0', 'test1', 'test2', 'test3');

        $element->setExtraPlugins($initialPlugins);
        $plugins = $element->getExtraPlugins();
        $this->assertTrue($plugins == $initialPlugins);

        $element->removeExtraPlugin('doesNotExist');
        $plugins = $element->getExtraPlugins();
        $this->assertTrue($plugins == $initialPlugins);

        $element->addExtraPlugin('test0');
        $plugins = $element->getExtraPlugins();
        $this->assertTrue($plugins == $initialPlugins);

        $element->removeExtraPlugin('test2');
        $this->assertFalse($element->hasExtraPlugin('test2'));

        $element->clearExtraPlugins();
        $plugins = $element->getExtraPlugins();
        $this->assertTrue(empty($plugins) && is_array($plugins));

        $element->addExtraPlugin('test4');
        $this->assertTrue($element->hasExtraPlugin('test4'));
    }
}
