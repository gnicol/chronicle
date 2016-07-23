<?php
/**
 * Allows the user to select one or more content entries.
 * The value is a single content id string, or an array of
 * content ids. To allow for multiple selection, set the
 * 'multiple' to true.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Form_Element_ContentSelect
    extends     Zend_Dojo_Form_Element_Dijit
    implements  P4Cms_Content_EnhancedElementInterface
{
    public      $helper             = 'ContentSelect';
    protected   $_contentRecord     = null;

    /**
     * Get the associated content record (if set).
     *
     * @return  null|P4Cms_Content  the associated content record or null if none set.
     */
    public function getContentRecord()
    {
        return $this->_contentRecord;
    }

    /**
     * Set the associated content record for this element.
     *
     * @param   P4Cms_Content   $content  the associated content record for this element.
     */
    public function setContentRecord($content)
    {
        $this->_contentRecord = $content;
    }

    /**
     * Get the default display decorators to use when rendering
     * content elements of this type. Renders using the purpose
     * built display selected content decorator (which is a wrapper
     * of the content list view helper).
     *
     * Configures it not to display a message when no content is
     * selected. This allows placeholder values to work.
     *
     * @return  array   decorators configuration array suitable for passing
     *                  to element setDecorators().
     */
    public function getDefaultDisplayDecorators()
    {
        return array(
            array(
                'decorator' => 'DisplaySelectedContent',
                'options'   => array(
                    'emptyMessage' => ''
                )
            )
        );
    }

    /**
     * Extends validate to ensure that value is not an array when
     * multiple select is disabled (and vice-versa) and to verify
     * that selected content ids exist.
     *
     * @param  string|array     $value      the selected content id(s).
     * @param  mixed            $context    optional context
     * @return bool
     */
    public function isValid($value, $context = null)
    {
        if (!$this->getValidator('P4Cms_Validate_Callback')) {
            $element   = $this;
            $validator = new P4Cms_Validate_Callback;
            $validator->setCallback(
                function ($value) use ($element, $validator)
                {
                    $multiple   = $element->getAttrib('multiple');
                    $validTypes = (array) $element->getAttrib('validTypes');

                    // set value to list of ids to simplify validation.
                    $value = $element::extractIds($value);

                    // ensure single select is enforced.
                    if (count($value) > 1 && !$multiple) {
                        $validator->setMessage(
                            "You may only select one content entry.",
                            $validator::INVALID_VALUE
                        );
                        return false;
                    }

                    // validate the content id(s).
                    $invalidIds  = array();
                    $idValidator = new P4Cms_Validate_ContentId;
                    $idValidator->setAllowNonExistent(false)->setAllowEmpty(true);
                    foreach ($value as $id) {
                        if (!$idValidator->isValid($id)) {
                            $invalidIds[] = $id;
                        }
                    }
                    if ($invalidIds) {
                        $validator->setMessage(
                            "One or more of the selected entries could not be found. " .
                            "Please review and clear these selections.",
                            $validator::INVALID_VALUE
                        );
                        return false;
                    }

                    // ensure that entries have valid content types
                    if ($validTypes) {
                        $entries = P4Cms_Content::fetchAll(
                            array(
                                'ids'         => $value,
                                'limitFields' => array('contentType')
                            )
                        );
                        foreach ($entries as $entry) {
                            if (!in_array($entry->getContentTypeId(), $validTypes)) {
                                $validator->setMessage(
                                    "One or more entries have an invalid content type"
                                    . " (allowed types: " . implode(', ', $validTypes) . ').',
                                    $validator::INVALID_VALUE
                                );
                                return false;
                            }
                        }
                    }

                    return true;
                }
            );
            $this->addValidator($validator);
        }

        return parent::isValid($value, $context);
    }

    /**
     * Normalize value to an array of arrays with id elements.
     * Content select values take several forms in storage:
     *  - a single string
     *  - an array of strings
     *  - an array of arrays
     *
     * @param   mixed   $value  the value from storage to normalize.
     * @return  array   the value normalized to an array of arrays.
     */
    public static function normalizeValue($value)
    {
        $normalized = array();
        foreach ((array) $value as $element) {
            if (!is_array($element)) {
                $element = array('id' => $element);
            }
            if (isset($element['id']) && is_string($element['id']) && strlen($element['id'])) {
                $normalized[] = $element;
            }
        }

        return $normalized;
    }

    /**
     * Extract a list of ids from the given content select value.
     *
     * @param   mixed   $value  the content select value
     * @return  array   a list of selected ids.
     */
    public static function extractIds($value)
    {
        $ids = array();
        foreach (static::normalizeValue($value) as $element) {
            $ids[] = $element['id'];
        }

        return $ids;
    }
}
