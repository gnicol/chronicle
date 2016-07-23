<?php
/**
 * Converts html entities to their character equivalents.
 * Supports hex and decimal html entities and case-insensitivity
 * for named entities where case does not matter (e.g. &nbsp; and &NBSP;).
 * Provides character set conversion - defaults to UTF-8.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Filter_HtmlEntityDecode implements Zend_Filter_Interface
{
    const       UTF8        = 'UTF-8';
    protected   $_charset   = self::UTF8;

    /**
     * Sets filter options (e.g. charset).
     *
     * @param  string|array|Zend_Config  $options  the character set to decode to.
     * @return void
     */
    public function __construct($options = array())
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        }

        if (is_array($options) && isset($options['charset'])) {
            $this->_charset = $options['charset'];
        } else if (is_string($options)) {
            $this->_charset = $options;
        }
    }

    /**
     * Convert html entities in the given string to their special
     * character equivalents. Note: invalid entities are not decoded.
     *
     * @param   mixed   $value  the html to be decoded.
     * @return  string  the html with valid entities decoded.
     */
    public function filter($value)
    {
        $mapping      = array();
        $entities     = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);
        $entityCounts = array_count_values(array_map('strtolower', $entities));
        foreach ($entities as $character => $entity) {

            // translated character should be in requested charset
            // using iconv for it's better charset support.
            $character = html_entity_decode($entity, ENT_QUOTES, self::UTF8);
            $character = iconv(self::UTF8, $this->_charset, $character);
            
            $mapping[$entity] = $character;

            // some entities vary by case (e.g. &aring, &Aring), if this entity
            // has only one entry, support both upper and lower-case variations.
            if ($entityCounts[strtolower($entity)] == 1) {
                $mapping[strtoupper($entity)] = $character;
                $mapping[strtolower($entity)] = $character;
            }
        }

        // do decoding of named entities.
        $value = str_replace(array_keys($mapping), array_values($mapping), $value);

        // perform decoding of hex and decimal html entities.
        $value = preg_replace_callback(
            "/&#(x([0-9a-f][0-9a-f])+|[0-9]+);/i",
            array($this, '_numericEntityCallback'),
            $value
        );

        return $value;
    }

    /**
     * Set the character set to use for entity translation.
     *
     * @param   string  $charset                the target character set.
     * @return  P4Cms_Filter_HtmlEntityDecode   provides fluent interface.
     */
    public function setCharset($charset)
    {
        if (!is_string($charset)) {
            throw new InvalidArgumentException(
                "Cannot set character set. Charset must be a string."
            );
        }

        $this->_charset = $charset;

        return $this;
    }

    /**
     * Get the character set used for entity translation.
     *
     * @return  string  the charset in use.
     */
    public function getCharset()
    {
        return $this->_charset;
    }

    /**
     * Given matches from preg replace, return either the decoded numeric
     * entity or the original entity if unable to decode.
     *
     * @param   array   $matches    array of matched elements passed from preg_replace_callback.
     * @return  string  the replacement string (decoded entity).
     */
    protected function _numericEntityCallback($matches)
    {
        // normalize entities to ints (unicode codepoints).
        if (strtolower($matches[1][0]) === 'x') {
            $value = hexdec(substr($matches[1], 1));
        } else {
            $value = intval($matches[1]);
        }

        // utf-32 (little-endian) encode unicode codepoint (utf-32 is easiest).
        // unicode codepoint must fit in a 32 bit number.
        if ($value > 0xFFFFFFFF) {
            return $matches[0];
        }
        $value = pack('V', $value);

        // return the converted character or the original entity on failure.
        $value = @iconv('UTF-32LE', $this->_charset, $value);
        return strlen($value) ? $value : $matches[0];
    }
}
