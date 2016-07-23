<?php
/**
 * Display link tag for associated file. File link is taken to
 * be the download URI for element's associated content record.
 * The element must be content type enhanced to get the
 * associated content.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Form_Decorator_DisplayFileLink extends Zend_Form_Decorator_Abstract
{
    // put a friendlier face on how to replace
    const   REPLACE = false;

    /**
     * Display download link for element's associated file.
     *
     * @param   string  $content  The content to render.
     * @return  string
     */
    public function render($content)
    {
        $element = $this->getElement();
        $label   = $element->getLabel() ? : $element->getName();

        // element must have an associated content record.
        if ($element instanceof P4Cms_Content_EnhancedElementInterface
            && $element->getContentRecord() instanceof P4Cms_Content
        ) {
            $record = $element->getContentRecord();
            if ($record->getId() !== null && $element instanceof Content_Form_Element_File) {
                $info = $record->getFieldMetadata($element->getName());
                if (isset($info['filename']) && strlen($info['filename'])) {
                    $label = $info['filename'];
                }
            }
        } else {
            throw new Content_Exception(
                "Cannot render download decorator. Element has no associated content record."
            );
        }

        // early exit if file has no length and no metadata.
        // @todo    possibly optimize this to not read entire value to
        //          check for length (e.g. could check filesize in case
        //          of actual file fields - wouldn't work for traits).
        $value    = $record->getValue($element->getName());
        $metadata = $record->getFieldMetadata($element->getName());
        if (!strlen($value) && empty($metadata)) {
            return $content;
        }

        // if this field is not the primary file field, pass it in the uri.
        if ($record->hasFileContentField()
            && $record->getFileContentField() !== $element->getName()
        ) {
            $params = array('field' => $element->getName());
        } else {
            $params = array();
        }

        // include version if decorating a historic revision.
        if ($record->hasId()) {
            $file = $record->toP4File();
            if (!$file->isHead()) {
                $params['version'] = $file->getStatus('headRev');
            }
        }

        $html = $this->_renderHtmlTag($label, $params);

        switch ($this->getPlacement()) {
            case self::APPEND:
                return $content . $html;
            case self::PREPEND:
                return $html . $content;
            default:
                return $html;
        }
    }

    /**
     * Produce html tag for current element and given label. Produces a link (anchor tag).
     *
     * @param   string  $label  the label to include in the tag.
     * @param   array   $params the paramaters to provide to the Uri function
     * @return  string  the rendered html tag.
     */
    protected function _renderHtmlTag($label, $params)
    {
        $record = $this->getElement()->getContentRecord();
        $uri    = $record->getUri('download', $params);
        return "<a href='" . htmlentities($uri) . "'>" . htmlentities($label) . "</a>";
    }
}
