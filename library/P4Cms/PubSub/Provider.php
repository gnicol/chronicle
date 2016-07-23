<?php
/**
 * Extend Phly Publish Subscribe facility.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_PubSub_Provider extends Phly_PubSub_Provider
{
    /**
     * Publish to all handlers for a given topic. Extends parent implementation
     * to collect all return values.
     *
     * @param  string   $topic  Topic to publish handlers for
     * @param  mixed    $args   All arguments besides the topic are passed as arguments to the handler
     * @return array    all of the return values from all subscribers.
     */
    public function publish($topic, $args = null)
    {
        if (empty($this->_topics[$topic])) {
            return array();
        }

        $return = array();
        $args   = func_get_args();
        array_shift($args);
        foreach ($this->_topics[$topic] as $handle) {
            $return[] = $handle->call($args);
        }
        return $return;
    }

    /**
     * Filter a value. Extends parent to return original value when there are
     * no topics.
     *
     * Notifies subscribers to the topic and passes the single value provided
     * as an argument. Each subsequent subscriber is passed the return value
     * of the previous subscriber, and the value of the last subscriber is 
     * returned.
     * 
     * @param  string   $topic  Topic to apply filter to
     * @param  mixed    $args   All arguments besides the topic are passed as arguments to the handler
     * @return mixed
     */
    public function filter($topic, $args = null)
    {
        $args = func_get_args();
        array_shift($args);

        if (empty($this->_topics[$topic])) {
            return $args[0];
        }

        foreach ($this->_topics[$topic] as $handle) {
            $args[0] = $handle->call($args);
        }
        return $args[0];
    }    
}
