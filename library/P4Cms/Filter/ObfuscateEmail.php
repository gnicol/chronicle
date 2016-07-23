<?php
/**
 * Obfuscate plain-text email addresses into javascript.
 * Identifies all email addresses in the input text and
 * replaces them with an anonymous function passed to
 * document.write (or window.location for mailto's).
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Filter_ObfuscateEmail implements Zend_Filter_Interface
{
    /**
     * Obfuscate plain-text email addresses into javascript.
     *
     * @param   string  $text   the text to obfuscate email addresses in.
     * @return  string  the text with obfuscated email addresses.
     */
    public function filter($text)
    {
        $filter = $this;
        $user   = "[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*";
        $domain = "(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?";
        $text   = preg_replace_callback(
            "/(href=(['\"]?)mailto:)?($user)@($domain)/i",
            function($matches) use ($filter)
            {
                // hex encode '@', 'mailto', the user and the domain.
                $at     = $filter->jsHexEncode('@');
                $mailto = $filter->jsHexEncode('mailto:');
                $user   = $filter->jsHexEncode($matches[3]);
                $domain = $filter->jsHexEncode($matches[4]);

                // construct js function to produce email.
                $email = 'function(d,u){'
                       . 'return u+"' . $at . '"+d;}("'
                       . $domain . '","' . $user   . '")';

                // if mailto...
                if ($matches[1]) {
                    $quote = $matches[2];
                    $js    = 'window.location.href="' . $mailto . '"+' . $email . ';';
                    return 'href=' . $quote . 'javascript:' . htmlentities($js);
                } else {
                    return '<script type="text/javascript">'
                         . 'document.write(' . $email . ');'
                         . '</script>';
                }
            },
            $text
        );

        return $text;
    }

    /**
     * Hex encode the input text for javascript strings
     * (e.g. '@' => '\x40').
     *
     * @param   string  $input  the text to encode.
     * @return  string  the encoded text.
     */
    public function jsHexEncode($input)
    {
        return preg_replace('/(..)/', '\x\\1', bin2hex($input));
    }
}
