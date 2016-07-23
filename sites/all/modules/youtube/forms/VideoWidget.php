<?php
/**
 * This is the yotube video widget config form, which accepts configuration options for the youtube
 * video stream.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Youtube_Form_VideoWidget extends P4Cms_Form_SubForm
{
    const           DIMENSION_SMALL                   = 'small';
    const           DIMENSION_MEDIUM                  = 'medium';
    const           DIMENSION_LARGE                   = 'large';
    const           DIMENSION_EXTRA_LARGE             = 'extra-large';
    const           DIMENSION_CUSTOM                  = 'custom';
    const           DIMENSION_WIDE_SMALL              = 'wide-small';
    const           DIMENSION_WIDE_MEDIUM             = 'wide-medium';
    const           DIMENSION_WIDE_LARGE              = 'wide-large';
    const           DIMENSION_WIDE_EXTRA_LARGE        = 'wide-extra-large';

    const           CONTROLS_ALWAYS_SHOW              = 0;
    const           CONTROLS_FADE_ALL                 = 1;
    const           CONTROLS_FADE_BAR                 = 2;
    const           CONTROLS_NEVER_SHOW               = 3;


    private         $_controlOptions                = array(
        self::CONTROLS_ALWAYS_SHOW          => 'Always show controls',
        self::CONTROLS_FADE_ALL             => 'Fade all out after video starts',
        self::CONTROLS_FADE_BAR             => 'Fade out progress bar after video starts',
        self::CONTROLS_NEVER_SHOW           => 'Never show controls'
    );

    private         $_dimensionOptions              = array(
        'Standard (4:3)'                    => array(
            self::DIMENSION_SMALL               => 'Small',
            self::DIMENSION_MEDIUM              => 'Medium',
            self::DIMENSION_LARGE               => 'Large',
            self::DIMENSION_EXTRA_LARGE         => 'Extra Large'
        ),
        'Widescreen (16:9)'                 => array(
            self::DIMENSION_WIDE_SMALL          => 'Small',
            self::DIMENSION_WIDE_MEDIUM         => 'Medium',
            self::DIMENSION_WIDE_LARGE          => 'Large',
            self::DIMENSION_WIDE_EXTRA_LARGE    => 'Extra Large',
        ),
        self::DIMENSION_CUSTOM              => 'Custom'
    );

    private         $_defaultControlOption          = self::CONTROLS_ALWAYS_SHOW;
    private         $_defaultVideoSize              = self::DIMENSION_MEDIUM;

    // sizes come from youtube embed code generator on the embed link on any youtube video,
    // except for 'small' which is smaller than suggested by youtube
    private static  $_dimensions             = array(
        self::DIMENSION_SMALL               => array('height' => 349, 'width' => 425),
        self::DIMENSION_MEDIUM              => array('height' => 390, 'width' => 480),
        self::DIMENSION_LARGE               => array('height' => 510, 'width' => 640),
        self::DIMENSION_EXTRA_LARGE         => array('height' => 750, 'width' => 960),
        self::DIMENSION_WIDE_SMALL          => array('height' => 349, 'width' => 560),
        self::DIMENSION_WIDE_MEDIUM         => array('height' => 390, 'width' => 640),
        self::DIMENSION_WIDE_LARGE          => array('height' => 510, 'width' => 853),
        self::DIMENSION_WIDE_EXTRA_LARGE    => array('height' => 750, 'width' => 1280)
    );

    /**
     * Defines the elements that make up the widget config form.
     * Default values are based off of youtube player default settings.
     *
     * This method is called automatically when the form object is created.
     */
    public function init()
    {
        $this->addElement(
            'text',
            'videoUrl',
            array(
                'label'         => 'Video Url',
                'size'          => 40,
                'required'      => true
            )
        );

        // Append height and width dimensions to the size displayed to the user.
        // Do this dynamically, so that if the dimensions are adjusted, there's only one place
        // to maintain it.
        $sizeOptions = array();
        foreach ($this->_dimensionOptions as $key => $options) {
            $sizeOptions[$key] = array();
            if (is_array($options)) {
                foreach ($options as $size => $option) {
                    $sizeOptions[$key][$size] = $option;
                    if (self::hasDimension($size)) {
                        $sizeOptions[$key][$size] .= ' ('
                                                  . $this->getWidth($size) . 'x'
                                                  . $this->getHeight($size) . ')';
                    }
                }
            } else {
                $sizeOptions[$key] = $options;
            }
        }

        $this->addElement(
            'select',
            'videoSize',
            array(
                'label'         => 'Size',
                'autocomplete'  => false,
                'multiOptions'  => $sizeOptions,
                'onChange'      =>
                       "var prefix = this.id.replace(/[^-]+-videoSize$/, '');"
                    .  "if (this.value == '" . self::DIMENSION_CUSTOM . "') {"
                    .  "    p4cms.ui.show(prefix + 'config-videoCustomSize');"
                    .  "} else {"
                    .  "    p4cms.ui.hide(prefix + 'config-videoCustomSize', {duration: 0});"
                    .  "}"
                    .  "var ds = dijit._dialogStack;"
                    .  "for (var index = ds.length; index > 0; index--) {"
                    .  "    if (dojo.query('#' + this.id, ds[index-1].domNode).length) {"
                    .  "        ds[index-1].updateLayout();"
                    .  "        break;"
                    .  "    }"
                    .  "}",
                'value'         => $this->_defaultVideoSize
            )
        );

        $this->addElement(
            'text',
            'videoWidth',
            array(
                'label'         => 'Width',
                'validators'    => array(
                    'digits'
                )
            )
        );

        $this->addElement(
            'text',
            'videoHeight',
            array(
                'label'         => 'Height',
                'validators'    => array(
                    'digits'
                )
            )
        );

        $this->addDisplayGroup(
            array('videoWidth', 'videoHeight'),
            'videoCustomSize',
            array('style' => 'display: none;')
        );

        $this->addElement(
            'select',
            'controls',
            array(
                'label'         => 'Show Controls',
                'multiOptions'  => $this->_controlOptions,
                'onChange'      =>
                        "var prefix     = this.id.replace(/[^-]+-controls/, '');"
                    .   "var fullscreen = dojo.query('#'+prefix+'config-allowFullscreen')[0];"
                    .   "if (this.value == " . self::CONTROLS_NEVER_SHOW . ") {"
                    .   "   dojo.attr(fullscreen, 'checked', false);"
                    .   "   dojo.attr(fullscreen, 'disabled', true);"
                    .   "} else {"
                    .   "   dojo.attr(fullscreen, 'checked', true);"
                    .   "   dojo.attr(fullscreen, 'disabled', false);"
                    .   "}",
                'value'         => $this->_defaultControlOption
            )
        );

        $this->addElement(
            'checkbox',
            'autoplay',
            array(
                'label'         => 'Play Automatically',
                'value'         => true
            )
        );

        $this->addElement(
            'checkbox',
            'loop',
            array(
                'label'         => 'Loop Video',
                'value'         => false
            )
        );

        $this->addElement(
            'checkbox',
            'allowFullscreen',
            array(
                'label'         => 'Allow Fullscreen',
                'value'         => true
            )
        );


        $this->addElement(
            'checkbox',
            'playHd',
            array(
                'label'         => 'Default to HD',
                'value'         => true
            )
        );

        $this->addElement(
            'checkbox',
            'showAnnotations',
            array(
                'label'         => 'Show Annotations',
                'value'         => false
            )
        );

        $this->addElement(
            'checkbox',
            'showRelated',
            array(
                'label'         => 'Show Related Videos',
                'value'         => false
            )
        );
    }

    /**
     * Verifies whether or not $size is a valid video dimension.
     *
     * @param   string  $size   the size to check for a dimension
     * @return  boolean whether or not $size is a valid video dimension
     */
    public static function hasDimension($size)
    {
        return array_key_exists($size, self::$_dimensions);
    }

    /**
     * Accessor for the set height for a given size.
     *
     * @param   string $size    the size for which to retrieve the height
     * @return  string the set height for this size of video
     */
    public static function getHeight($size)
    {
        return self::$_dimensions[$size]['height'];
    }

    /**
     * Accessor for the set width for a given size.
     *
     * @param   string $size    the size for which to retrieve the width
     * @return  string the set width for this size of video
     */
    public static function getWidth($size)
    {
        return self::$_dimensions[$size]['width'];
    }

    /**
     * Whenever values are set on the form, show/hide the video size
     * fieldset as appropriate.
     *
     * @param   array   $defaults           the values to populate the form
     * @return  Widget_Form_ImageWidget     provides fluent interface
     */
    public function setDefaults(array $defaults)
    {
        if (isset($defaults['config']['videoSize'])
            && $defaults['config']['videoSize'] == self::DIMENSION_CUSTOM
        ) {
            $this->getDisplayGroup('videoCustomSize')->setAttrib('style', 'display: block;');
        }

        if (isset($defaults['config']['controls'])
            && $defaults['config']['controls'] == self::CONTROLS_NEVER_SHOW
        ) {
            $this->getElement('allowFullscreen')->setAttrib('disabled', true);
        }

        return parent::setDefaults($defaults);
    }

    /**
     * Perform extra, form-specific validation on the form data.  Ensures the url provided is hosted
     * on youtube, and that a video id is provided.
     *
     * @param array     $data   the array of form submission data to validate
     * @return boolean          whether or not $data is valid
     */
    public function isValid($data)
    {
        if (!parent::isValid($data)) {
            return false;
        }

        // ensure url is valid
        try {
            $uri = Zend_Uri::factory($data['config']['videoUrl']);
        }
        catch (Exception $e) {
            // any exception invalidates the url
            $this->getElement('videoUrl')->addError(
                "Invalid video URL."
            );
            return false;
        }

        // ensure video id is set and not empty
        $query = $uri->getQueryAsArray();
        if (!array_key_exists('v', $query) || empty($query['v'])) {
            $this->getElement('videoUrl')->addError(
                "Invalid video URL - no video id found."
            );
            return false;
        }

        // ensure domain is youtube
        $host = explode('.', $uri->getHost());
        if ($host[0] == 'www') {
            array_shift($host);
        }
        // verify the host has at least one youtube in it.
        // usual format is youtube.com, so it should return right away
        foreach ($host as $segment) {
            if ($segment == 'youtube') {
                return true;
            }
        }
        
        // no youtube in domain
        $this->getElement('videoUrl')->addError(
            "Invalid video URL - must be on youtube domain."
        );
        return false;
    }
}