<?php
/**
 * Test the video widget subform.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Youtube_Test_VideoWidgetTest extends ModuleTest
{
    /**
     * Initialize the module
     */
    public function setUp()
    {
        parent::setUp();

        // enable youtube module
        $module = P4Cms_Module::fetch('Youtube');
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
        $form = new Youtube_Form_VideoWidget;

        $expected = array(
            '' => array(
                'videoUrl'          => '',
                'videoSize'         => Youtube_Form_VideoWidget::DIMENSION_MEDIUM,
                'videoWidth'        => '',
                'videoHeight'       => '',
                'controls'          => Youtube_Form_VideoWidget::CONTROLS_ALWAYS_SHOW,
                'autoplay'          => true,
                'loop'              => 0,
                'allowFullscreen'   => true,
                'playHd'            => true,
                'showAnnotations'   => 0,
                'showRelated'       => 0
            )
        );

        $this->assertEquals(
            $expected,
            $form->getValues(),
            "Expected default values"
        );
    }

    /**
     * Verifies the validation of the form, specificly the youtube url.
     */
    public function testValidation()
    {
        // configure the widget
        // should fail because it is missing the required video url field.
        $data = array(
            'region'            => 'test',
            'widget'            => '1',
            'title'             => 'YouTube test',
            'config'            => array(
                'videoUrl'          => ''
            )
        );

        $form = new Widget_Form_Config;
        $form->addSubForm(new Youtube_Form_VideoWidget, 'config', 2);
        $form->setCsrfProtection(false);
        $this->assertFalse($form->isValid($data), 'Expected form to be invalid.');

        // should fail because it is contains an invalid url
        $data['config']['videoUrl'] = 'invalidyoutubeurl';
        $form = new Widget_Form_Config;
        $form->addSubForm(new Youtube_Form_VideoWidget, 'config', 2);
        $form->setCsrfProtection(false);
        $this->assertFalse(
            $form->isValid($data),
            'Expected url ' . $data['config']['videoUrl'] .' to be invalid.'
        );

        // should fail because it is contains no video id
        $data['config']['videoUrl'] = 'http://www.youtube.com/';
        $form = new Widget_Form_Config;
        $form->addSubForm(new Youtube_Form_VideoWidget, 'config', 2);
        $form->setCsrfProtection(false);
        $this->assertFalse(
            $form->isValid($data),
            'Expected url ' . $data['config']['videoUrl'] .' to be invalid.'
        );

        // should fail because it is contains an invalid domain
        $data['config']['videoUrl'] = 'http://www.perforce.com/watch?v=CDunnQz81FY';
        $form = new Widget_Form_Config;
        $form->addSubForm(new Youtube_Form_VideoWidget, 'config', 2);
        $form->setCsrfProtection(false);
        $this->assertFalse(
            $form->isValid($data),
            'Expected url ' . $data['config']['videoUrl'] .' to be invalid.'
        );

        // should pass
        $data['config']['videoUrl'] = 'http://www.youtube.com/watch?v=CDunnQz81FY';
        $form = new Widget_Form_Config;
        $form->addSubForm(new Youtube_Form_VideoWidget, 'config', 2);
        $form->setCsrfProtection(false);
        $this->assertTrue(
            $form->isValid($data),
            'Expected url ' . $data['config']['videoUrl'] .' to be valid.'
        );

        // should pass as well, as the id as appended to a hardcoded domain in the view script
        $data['config']['videoUrl'] = 'http://www.youtube.evilhacksite.com/watch?v=CDunnQz81FY';
        $form = new Widget_Form_Config;
        $form->addSubForm(new Youtube_Form_VideoWidget, 'config', 2);
        $form->setCsrfProtection(false);
        $this->assertTrue(
            $form->isValid($data),
            'Expected url ' . $data['config']['videoUrl'] .' to be valid.'
        );
    }
}
