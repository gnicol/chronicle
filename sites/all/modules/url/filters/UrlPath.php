<?php
/**
 * Filter to normalize a url path for use as a 'custom url'.
 *
 * Normalizes the path to encode (and only encode) 'unsafe' characters.
 * The list of 'safe' characters includes all of the unreserved and reserved
 * URI characters (see http://en.wikipedia.org/wiki/Percent-encoding), 
 * excluding '?' and '#' since those will terminate the path component.
 * 
 * Additionally converts unencoded backslashes to forward-slashes and trims
 * any unencoded leading or trailing slashes and whitespace.
 * 
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Url_Filter_UrlPath implements Zend_Filter_Interface
{
    /**
     * Normalize a url path component such that characters are
     * consistently encoded. Only 'unsafe' characters are encoded.
     *
     * @param   string|null                 $value  the url path component to filter.
     * @throws  InvalidArgumentException    if given value is not a string.
     * @return  string                      the normalized url path.
     */
    public function filter($value)
    {
        // leave null values alone.
        if (is_null($value)) {
            return $value;
        }
        
        // ensure we're dealing with a string.
        if (!is_string($value)) {
            throw new InvalidArgumentException(
                "Cannot normalize url path. Value must be a string."
            );
        }
        
        // translate unencoded backslashes to forward-slashes.
        $value = str_replace('\\', '/', $value);

        // trim unencoded leading/trailing slashes and whitespace.
        $value = trim($value, " \t\n\r/");

        // to achieve a consistent level of encoding, we first decode 
        // all characters and then (re)encode the 'unsafe' ones.
        $value = rawurldecode($value);
        
        // identify 'safe' characters.
        $safe = array(
            // unreserved characters (alpha-numerics are handled below).
            '-', '_', '.', '~',
            // reserved characters.
            '!', '*', '\'', '(', ')', ';', ':', '@', '&', '=', '+', '$', ',', '/', '[', ']'
        );

        // encode everything not in our whitelist of safe characters.
        $value = preg_replace_callback(
            '/[^a-z0-9\\' . implode('\\', $safe) . ']/i',
            function($matches)
            {
                return "%" . bin2hex($matches[0]);
            },
            $value
        );

        return $value;
    }
}
