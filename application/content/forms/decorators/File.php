<?php
/**
 * Extends Zend_Form_Decorator_File to present options on
 * handling an existing file if one is set.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Form_Decorator_File extends Zend_Form_Decorator_File
{
    protected   $_dojoType      = "p4cms.content.FileUpload";

    /**
     * Extend render to show existing file if set.
     *
     * @param   string  $content  The content to render.
     * @return  string
     */
    public function render($content)
    {
        $content = parent::render($content);

        // if the element has an existing file, present options
        // to keep, remove or replace it.
        $element = $this->getElement();
        if ($element->hasExistingFile()) {
            $info = $element->getExistingFileInfo();

            $html = "<div class='existing-file'>";
            if (isset($info['iconUri'])) {
                $html .= '<span class="existing-file-icon">'
                      .  '<img src="'. $info['iconUri'] .'"></span>';
            }
            if (isset($info['filename'])) {
                $html .= '<span class="existing-file-name">'
                      .  htmlspecialchars($info['filename'])
                      . '</span>';
            }

            // build up action options for existing file.
            $inputName = $element->getName() . '-existing-file-action';
            $options   = array(
                Content_Form_Element_File::ACTION_KEEP    => 'Keep Existing',
                Content_Form_Element_File::ACTION_REMOVE  => 'Remove Existing',
                Content_Form_Element_File::ACTION_REPLACE => 'Replace Existing',
            );

            $html .= '<ul class="existing-file-options">';
            foreach ($options as $value => $label) {
                $actionId = $element->getId() . '-action-' . $value;
                $checked  = $element->getActionFieldValue() === $value ? ' checked="true"' : '';
                $html    .= "<li>";
                $html    .= "<input id=\"$actionId\" name=\"$inputName\"";
                $html    .= " type=\"radio\" value=\"$value\" $checked>";
                $html    .= "<label for=\"$actionId\">$label</label>";
                $html    .= "</li>";
            }
            $html .= '</ul></div>';

            $content = $html . $content;
        }

        // get list of valid extensions from validator.
        $extensions = array();
        $minSize    = 0;
        $maxSize    = 0;
        
        $validators = $element->getValidators();
        foreach ($validators as $validator) {
            if ($validator instanceof Zend_Validate_File_Extension) {
                $extensions = $validator->getExtension();
            }
            if ($validator instanceof Zend_Validate_File_Size) {
                $minSize = $validator->getMin(true);
                $maxSize = $validator->getMax(true);
            }
        }
        
        // wrap file upload controls in a dijit.
        $view    = $element->getView();
        $content = $view->customDijit(
            "dijit-" . $element->getId(),
            $content,
            array(
                "dojoType"      => $this->_dojoType,
                "extensions"    => Zend_Json::encode($extensions),
                "minSize"       => $minSize,
                "maxSize"       => $maxSize,
                "required"      => $element->isRequired()
            )
        );
        
        return $content;
    }
}
