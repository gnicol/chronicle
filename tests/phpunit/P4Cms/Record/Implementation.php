<?php
/**
 * Test implementation of record.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Record_Implementation extends P4Cms_Record
{
    protected static    $_fields            = array(
        'title'         => array(
            'accessor'  => 'getTitle',
            'mutator'   => 'setTitle',
            'default'   => 'Record Title'
        ),
        'content'       => array(
            'accessor'  => 'getContent',
            'mutator'   => 'setContent',
            'default'   => 'Record content.'
        )
    );

    protected static    $_storageSubPath    = 'records';
    protected static    $_fileContentField  = 'content';

    /**
     * Get the value of the title field.
     *
     * @return  string  The value of the title field.
     */
    public function getTitle()
    {
        return $this->_getValue('title');
    }

    /**
     * Set the title field.
     *
     * @param   string  $title  The value to apply to the title field.
     * @return  P4Cms_Record_Implementation  Provide a fluent interface.
     */
    public function setTitle($title)
    {
        return $this->_setValue('title', $title);
    }

    /**
     * Get the value of the content field.
     *
     * @return  string  The value of the content field.
     */
    public function getContent()
    {
        return $this->_getValue('content');
    }

    /**
     * Set the content field.
     *
     * @param   string  $content  The value to apply to the content field.
     * @return  P4Cms_Record_Implementation  Provide a fluent interface.
     */
    public function setContent($content)
    {
        return $this->_setValue('content', $content);
    }

    /**
     * Provide access to toggle id encoding at runtime.
     *
     * @param   bool    $encode     true to enable encoding, false to disable.
     */
    public static function setEncodeIds($encode)
    {
        static::$_encodeIds = $encode;
    }
}
