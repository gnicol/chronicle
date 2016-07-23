<?php
/**
 * Format a passed date/time in a friendlier manner such as:
 *  just now
 *  1 minute ago
 *  3 weeks ago
 *  etc.
 *  January 2, 2010 5:23 pm
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_View_Helper_TimeAgo extends Zend_Dojo_View_Helper_Dojo
{
    /**
     * Takes the passed dateTime and converts it to a
     * friendlier format such as:
     *  just now
     *  1 minute ago
     *  3 weeks ago
     *  etc.
     *
     * We assume a month is 28 days. For values older
     * than 12 of our months (336 days) the fallback
     * format will be utilized with date to generate
     * the output. The default format returns the style:
     * January 4, 2010 4:32 pm
     *
     * @param   string|int  $dateTime           the value to format; strings must be strtotime
     *                                          compatible
     * @param   string      $fallbackFormat     optional - date format to use for values older
     *                                          than 336 days (12 psuedo months)
     * @return  string  the truncated string.
     */
    public function timeAgo($dateTime, $fallbackFormat = 'F d, Y g:i a')
    {
        // cast purely numeric strings to int
        if (is_string($dateTime) && $dateTime === (string)(int)$dateTime) {
            $dateTime = (int)$dateTime;
        }

        if (is_string($dateTime)) {
            $dateTime = strtotime($dateTime);
        }

        if (!is_int($dateTime)) {
            throw new InvalidArgumentException(
                'Expected int or strtotime compatible string'
            );
        }

        // entry 1 is max age in seconds that rule applies to
        // entry 2 is divisor to apply to value
        // entry 3 is text to append to divided value; text
        //         alone is used when entry 2 is 0
        $times = array(
            array(60,        0,          'just now'),
            array(120,       0,          '1 minute ago'),
            array(3600,      60,         'minutes ago'),
            array(7200,      0,          '1 hour ago'),
            array(86400,     3600,       'hours ago'),
            array(172800,    0,          'yesterday'),
            array(604800,    86400,      'days ago'),
            array(1209600,   0,          'last week'),
            array(2419200,   604800,     'weeks ago'),
            array(4838400,   0,          'last month'),
            array(29030400,  2419200,    'months ago')
        );

        $seconds = time() - $dateTime;
        foreach ($times as $time) {
            if ($seconds < $time[0]) {
                if ($time[1]) {
                    return floor($seconds / $time[1]) . " " . $time[2];
                }

                return $time[2];
            }
        }

        // if we made it here; return a formatted
        // date as entry is older than 336 days
        return date($fallbackFormat, $dateTime);
    }
}
