<?php
/**
 * Extended Zend_Filter_StripTags to preset default-allowed tags/attributes.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Filter_DefaultStripTags extends Zend_Filter_StripTags
{
    // list of default-allowed HTML attributes
    protected $_defaultAttributesAllowed = array(
        'align',
        'bgcolor',
        'class',
        'height',
        'id',
        'style',
        'width'
    );

    // list of default-allowed HTML tags
    protected $_defaultTagsAllowed = array(
        'a'         => array(
            'href',
            'title',
            'rel',
            'rev',
            'name',
            'target'
        ),
        'abbr'      => array(
            'title'
        ),
        'acronym'   => array(
            'title'
        ),
        'b',
        'big',
        'blockquote'=> array(
            'cite'
        ),
        'br',
        'button'    => array(
            'disabled',
            'name',
            'type',
            'value'
        ),
        'caption',
        'cite'      => array(
            'dir',
            'title'
        ),
        'code',
        'col'       => array(
            'char',
            'charoff',
            'span',
            'dir',
            'valign'
        ),
        'dd',
        'div'       => array(
            'dir'
        ),
        'dl',
        'dt',
        'em',
        'fieldset',
        'figure'    => array(
            'dir'
        ),
        'figcaption'=> array(
            'dir'
        ),
        'font'      => array(
            'color',
            'face',
            'size'
        ),
        'footer'    => array(
            'dir'
        ),
        'form'      => array(
            'action',
            'accept',
            'accept-charset',
            'enctype',
            'method',
            'name',
            'target'
        ),
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'header'    => array(
            'dir'
        ),
        'hr'        => array(
            'noshade',
            'size'
        ),
        'i',
        'img'       => array(
            'alt',
            'border',
            'hspace',
            'longdesc',
            'vspace',
            'src'
        ),
        'label'     => array(
            'for'
        ),
        'legend',
        'li',
        'p'         => array(
            'dir'
        ),
        'pre',
        'q'         => array(
            'cite'
        ),
        's',
        'span'      => array(
            'dir',
            'title'
        ),
        'strike',
        'strong',
        'sub',
        'sup',
        'table'     => array(
            'border',
            'cellpadding',
            'cellspacing',
            'dir',
            'rules',
            'summary'
        ),
        'tbody'     => array(
            'char',
            'charoff',
            'valign'
        ),
        'td'        => array(
            'abbr',
            'axis',
            'char',
            'charoff',
            'colspan',
            'dir',
            'headers',
            'nowrap',
            'rowspan',
            'scope',
            'valign'            
        ),
        'textarea'  => array(
            'cols',
            'rows',
            'disabled',
            'name',
            'readonly'
        ),
        'tfoot'     => array(
            'char',
            'charoff',
            'valign'
        ),
        'th'        => array(
            'abbr',
            'axis',
            'char',
            'charoff',
            'colspan',
            'headers',
            'nowrap',
            'rowspan',
            'scope',
            'valign'
        ),
        'thead'     => array(
            'char',
            'charoff',
            'valign'
        ),
        'title',
        'tr'        => array(
            'char',
            'charoff',
            'valign'
        ),
        'u',
        'ul'        => array(
            'type'
        ),
        'ol'        => array(
            'start',
            'type'
        )
    );

    /**
     * Overwrite parent constructor to set default allowed tags and attributes.
     *
     * @param   array|null  $options    Filter options (see parent for details).
     */
    public function __construct($options = null)
    {
        // set default allowed tags/attributes
        $this->setTagsAllowed($this->_defaultTagsAllowed);
        $this->setAttributesAllowed($this->_defaultAttributesAllowed);

        parent::__construct($options);
    }

    /**
     * Remove specified tag(s) from allowed tags list.
     * 
     * @param string|array $tags    List of tags to remove from allowed tags.
     */
    public function removeTags($tags)
    {
        if (!is_array($tags)) {
            $tags = array($tags);
        }

        foreach ($tags as $tag) {
            // normalize tag name
            $tagName = strtolower($tag);
            unset($this->_tagsAllowed[$tagName]);
        }
    }

    /**
     * Remove specified attribute(s) from allowed attributes list.
     * Function supports also an option to remove attributes only for certain tags,
     * see examples below.
     *
     * Examples of parameter values and their meaning:
     *
     * 'attr'                                   removes 'attr' attribute
     * array('attr1', 'attr2')                  removes 'attr1' and 'attr2' attributes
     * array('tag' => 'attr')                   removes 'attr' attribute only for 'tag'
     *                                          tag
     * array('tag' => array('attr1', 'attr2')   removes 'attr1' and 'attr2' attributes
     *                                          only for 'tag' tag
     *
     * @param   string|array    $attributes     List with attributes to remove from
     *                                          allowed attributes.
     */
    public function removeAttributes($attributes)
    {
        if (!is_array($attributes)) {
            $attributes = array($attributes);
        }

        foreach ($attributes as $index => $element) {
            // if attributes were provided without tag, remove them from
            // allowed attributes and also from all allowed tag attributes
            if (is_int($index)) {
                // normalize attribute name
                $attrinuteName = strtolower($element);
                unset($this->_attributesAllowed[$attrinuteName]);
                $this->_removeTagAttribute($attrinuteName);
            } else {
                if (!is_array($element)) {
                    $element = array($element);
                }
                foreach ($element as $attribute) {
                    // normalize attribute/tag names
                    $attributeName = strtolower($attribute);
                    $tagName       = strtolower($index);                    
                    $this->_removeTagAttribute($attributeName, $tagName);
                }
            }
        }
    }

    /**
     * Removes attribute from (optionaly) given tag. If tag is not specified,
     * removes attribute from all currently allowed tags.
     * 
     * @param   string      $attribute  Attribute to remove (from given tag).
     * @param   string|null $tag        Tag to remove attribute from.
     */
    protected function _removeTagAttribute($attribute, $tag = null)
    {
        if ($tag) {
            unset($this->_tagsAllowed[$tag][$attribute]);
            return;
        }

        foreach ($this->_tagsAllowed as $tag => $value) {
            unset($this->_tagsAllowed[$tag][$attribute]);
        }
    }
}
