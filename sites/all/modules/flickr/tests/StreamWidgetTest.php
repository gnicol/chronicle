<?php
/**
 * Test the flickr stream widget subform.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Flickr_Test_StreamWidgetTest extends ModuleTest
{
    /**
     * Initialize the module
     */
    public function setUp()
    {
        parent::setUp();

        // enable flickr module
        $module = P4Cms_Module::fetch('Flickr');
        $module->enable();
        $module->load();
    }

    /**
     * Test form initialization and default values.  Verifies that the defaults
     * and form structure hasn't been accidently changed.
     */
    public function testFormInit()
    {
        // verify form defaults
        $form = new Flickr_Form_StreamWidget;

        $expected = array(
            '' => array(
                'sourceType'        => Flickr_Form_StreamWidget::SOURCE_TAG,
                'sourceTag'         => '',
                'sourceUser'        => '',
                'sourceGroup'       => '',
                'imageSize'         => Flickr_Form_StreamWidget::DIMENSION_THUMBNAIL,
                'showImageTitle'    => 0,
                'imageDelay'        => 3
            )
        );

        $this->assertEquals(
            $expected,
            $form->getValues(),
            "Expected default values"
        );
    }

    /**
     * Verifies the validation of the form.
     */
    public function testValidation()
    {
        // configure the widget
        // should fail because it is missing the required source key
        $data = array(
            'region'            => 'test',
            'widget'            => '1',
            'title'             => 'Flickr Test',
            'config'            => array(
                'sourceType'        => Flickr_Form_StreamWidget::SOURCE_TAG,
                'sourceTag'         => '',
                'sourceUser'        => '',
                'sourceGroup'       => '',
                'imageSize'         => Flickr_Form_StreamWidget::DIMENSION_THUMBNAIL,
                'showImageTitle'    => 0,
                'imageDelay'        => 3
            )
        );

        $form = new Widget_Form_Config;
        $form->addSubForm(new Flickr_Form_StreamWidget, 'config', 2);
        $form->setCsrfProtection(false);
        
        // will be false because no tag is set
        $this->assertFalse($form->isValid($data), 'Expected form to be invalid.');

        // expected to be false due to missing information
        $data['config']['sourceType']   = Flickr_Form_StreamWidget::SOURCE_GROUP;
        $data['config']['sourceGroup']  = '';
        $this->assertFalse($form->isValid($data), 'Expected form to be invalid.');

        // expected to be false due to missing information
        $data['config']['sourceType']   = Flickr_Form_StreamWidget::SOURCE_USER;
        $data['config']['sourceUser']   = '';
        $this->assertFalse($form->isValid($data), 'Expected form to be invalid.');

        $form = new Widget_Form_Config;
        $form->addSubForm(new Flickr_Form_StreamWidget, 'config', 2);
        $form->setCsrfProtection(false);

        $data['config']['sourceType']   = Flickr_Form_StreamWidget::SOURCE_TAG;
        $data['config']['sourceTag']    = 'perforce';
        $this->assertTrue($form->isValid($data), 'Expected form to be valid.');

        $data['config']['sourceType']   = Flickr_Form_StreamWidget::SOURCE_GROUP;
        $data['config']['sourceGroup']  = '52241285452@N01';   // victoria, bc group
        $this->assertTrue($form->isValid($data), 'Expected form to be valid.');

        $data['config']['sourceType']   = Flickr_Form_StreamWidget::SOURCE_USER;
        $data['config']['sourceUser']  = 'p4cms';
        $this->assertTrue($form->isValid($data), 'Expected form to be valid.');
    }
}
