<?php
/**
 * Tracks which users have a given content record open for edit
 * in their browser.
 * 
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Content_Opened extends P4Cms_Record_Volatile
{
    protected   $_storageSubPath    = 'opened';

    const       PING_TIMEOUT        = 70;      // 2*30 + 10; represents 2 missed pings with a buffer

    /**
     * Use this accessor to get all of the keys grouped by user; 
     * e.g. users[user][keyName]. This method will screen out
     * any entries older than PING_TIMEOUT and adds in 'Offset' 
     * values for the start/ping/edit times.
     * 
     * Calling getValues on this model will return keys in the
     * format user-keyName. We store the keys this way to allow
     * setting a single user/key/value without knowing the other
     * values (minimizes race conditions).
     * 
     * @return  array   the current values organized by user
     */
    public function getUsers()
    {
        $users  = array();
        foreach ($this->getValues() as $key => $value) {
            $parts = explode('-', $key, 2);
            if (count($parts) !== 2) {
                continue;
            }

            list($user, $key) = $parts;
            if (!isset($users[$user])) {
                $users[$user] = array();
            }

            $users[$user][$key] = $value;
        }

        // do a second loop to remove expired entries
        $time = time();
        foreach ($users as $user => &$values) {
            // normalize array
            $values += array('pingTime' => null, 'editTime' => null, 'startTime' => null);
            
            // if start or ping time are missing, or the ping is expired remove entry
            if (!$values['startTime'] || !$values['pingTime'] 
                || ($time - $values['pingTime']) > static::PING_TIMEOUT
            ) {
                unset($users[$user]);
            }
        }

        // sort the users based on their last edit and start time
        uasort(
            $users, 
            function($a, $b) 
            {
                if ($a['editTime'] || $b['editTime']) {
                    return $a['editTime'] - $b['editTime'];
                }
                
                return $a['startTime'] - $b['startTime'];
            }
        );

        return $users;
    }

    /**
     * Set the ping time for a specified user. The default time of true
     * will automatically set the current time. Passing false will clear
     * the time for the specified user and any other value will be used
     * unchanged.
     * 
     * @param   string|P4Cms_User   $user   The user id to set this property on
     * @param   mixed               $time   The time to use - optional
     * @return  P4Cms_Content_Opened        To maintain a fluent interface
     */
    public function setUserPingTime($user, $time = true)
    {
        return $this->setUserTimeProperty($user, 'ping', $time);
    }
    
    /**
     * Set the edit time for a specified user. The default time of true
     * will automatically set the current time. Passing false will clear
     * the time for the specified user and any other value will be used
     * unchanged.
     * 
     * @param   string|P4Cms_User   $user   The user id to set this property on
     * @param   mixed               $time   The time to use - optional
     * @return  P4Cms_Content_Opened        To maintain a fluent interface
     */
    public function setUserEditTime($user, $time = true)
    {
        return $this->setUserTimeProperty($user, 'edit', $time);
    }

    /**
     * Set the start time for a specified user. The default time of true
     * will automatically set the current time. Passing false will clear
     * the time for the specified user and any other value will be used
     * unchanged.
     * 
     * @param   string|P4Cms_User   $user   The user id to set this property on
     * @param   mixed               $time   The time to use - optional
     * @return  P4Cms_Content_Opened        To maintain a fluent interface
     */
    public function setUserStartTime($user, $time = true)
    {
        return $this->setUserTimeProperty($user, 'start', $time);
    }

    /**
     * Set the time for a specified user property. The default time of true
     * will automatically set the current time. Passing false will clear
     * the time for the specified user and any other value will be used
     * unchanged.
     * 
     * @param   string|P4Cms_User   $user       The user id to set this property on
     * @param   string              $property   The 'time' property to set (e.g. start/ping)
     * @param   mixed               $time       The time to use - optional
     * @return  P4Cms_Content_Opened            To maintain a fluent interface
     */
    public function setUserTimeProperty($user, $property, $time = true)
    {
        // normalize user; like a boss
        $user = $user instanceof P4Cms_User ? $user->getId() : $user;

        // default case; use current time
        if ($time === true) {
            $time = time();
        }
        
        // normalize false values to null
        if (!$time) {
            $time = null;
        }

        return $this->setValue($user . '-' . $property . 'Time', $time);
    }
}