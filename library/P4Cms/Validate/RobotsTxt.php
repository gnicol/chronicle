<?php
/**
 * Validates string for suitability as a robots.txt definition.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Validate_RobotsTxt extends Zend_Validate_Abstract
{
    const   ALLOW_BEFORE_USER_AGENT     = 'allowBeforeUserAgent';
    const   DIRECTIVE_BEFORE_USER_AGENT = 'directiveBeforeUserAgent';
    const   CONSTRAINT_REQUIRED         = 'constraintRequired';
    const   SITEMAP_INCOMPLETE          = 'sitemapIncomplete';
    const   USER_AGENT_INCOMPLETE       = 'userAgentIncomplete';

    protected   $_messageTemplates  = array(
        self::DIRECTIVE_BEFORE_USER_AGENT
            => "The User-agent directive must precede any other per-record directives.",
        self::SITEMAP_INCOMPLETE
            => "A Sitemap directive is missing a sitemap URL.",
        self::USER_AGENT_INCOMPLETE
            => "A User-agent directive is missing a user agent identifier.",
    );

    /**
     * Defined by Zend_Validate_Interface
     *
     * Checks if the given string appears to be a valid robots.txt definition
     *
     * @param   string   $value  The value to validate.
     * @return  boolean  true if value is a valid robots.txt definition, false otherwise.
     */
    public function isValid($value)
    {
        $lines = array_map('trim', preg_split("/\r\n|\n|\r/", $value));
        $record = array();
        $counter = 0;
        foreach ($lines as $line) {
            $counter++;

            // test for comment lines
            if (preg_match('/^\s*#/', $line)) {
                continue;
            }

            // test for empty lines, which complete a record
            if (preg_match('/^\s*$/', $line)) {
                // if a record was started it is now terminated, reset
                if (count($record)) {
                    $record = array();
                }

                continue;
            }

            // test for user agent directives
            if (preg_match('/^User-agent:(.*)$/i', $line, $matches)) {
                $userAgent = trim($matches[1]);
                if (!strlen($userAgent)) {
                    $this->_error(self::USER_AGENT_INCOMPLETE);
                    return false;
                }

                // remember that we've seen the user agent directive
                if (!array_key_exists('userAgent', $record)) {
                    $record['userAgent'] = 0;
                }
                $record['userAgent']++;

                continue;
            }

            // test for sitemap directives
            if (preg_match('/^Sitemap:(.*)$/i', $line, $matches)) {
                $sitemap = trim($matches[1]);
                if (!strlen($sitemap)) {
                    $this->_error(self::SITEMAP_INCOMPLETE);
                    return false;
                }

                continue;
            }

            // at this point, we're handling a non-blank line that does not contain
            // a User-agent directive. Verify that a User-agent directive has already
            // been seen for this record.
            if (!array_key_exists('userAgent', $record)) {
                $this->_error(self::DIRECTIVE_BEFORE_USER_AGENT);
                return false;
            }
        }

        return true;
    }
}