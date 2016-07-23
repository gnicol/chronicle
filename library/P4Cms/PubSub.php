<?php
/**
 * P4Cms_PubSub is a subclass of Phly_PubSub which makes use of our own
 * pubsub provider, to make subscription results consistent.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_PubSub extends Phly_PubSub
{
    const   TOPIC_DELIMITER = '.';
        
    /**
     * @var P4Cms_PubSub_Provider
     */
    protected static $_instance;

    /**
     * Retrieve PubSub provider instance
     *
     * @return P4Cms_PubSub_Provider
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::setInstance(new P4Cms_PubSub_Provider());
        }
        return self::$_instance;
    }

    /**
     * Set PubSub provider instance
     *
     * @param  P4Cms_PubSub_Provider $provider instance of pub/sub provider
     * @return void
     */
    public static function setInstance(P4Cms_PubSub_Provider $provider)
    {
        self::$_instance = $provider;
    }

    /**
     * Publish to all handlers for a given topic
     *
     * @param  string $topic Topic to publish
     * @param  mixed  $args  All arguments besides the topic are passed as arguments to the handler
     * @return void
     */
    public static function publish($topic, $args = null)
    {
        return call_user_func_array(
            array(self::getInstance(), 'publish'),
            func_get_args()
        );
    }

    /**
     * Notify subscribers until return value of one causes a callback to
     * evaluate to true
     *
     * Publishes subscribers until the provided callback evaluates the return
     * value of one as true, or until all subscribers have been executed.
     *
     * @param  Callable $callback Callback function to test when notifications should cease
     * @param  string   $topic    Topic to publish
     * @param  mixed    $args     All arguments besides the topic are passed as arguments to the handler
     * @return mixed
     * @throws Phly_PubSub_InvalidCallbackException if invalid callback provided
     */
    public function publishUntil($callback, $topic, $args = null)
    {
        return call_user_func_array(
            array(self::getInstance(), 'publishUntil'),
            func_get_args()
        );
    }

    /**
     * Filter a value
     *
     * Notifies subscribers to the topic and passes the single value provided
     * as an argument. Each subsequent subscriber is passed the return value
     * of the previous subscriber, and the value of the last subscriber is
     * returned.
     *
     * @param  string $topic Topic to apply filter to
     * @param  mixed  $args  All arguments besides the topic are passed as arguments to the handler
     * @return mixed
     */
    public function filter($topic, $args)
    {
        return call_user_func_array(
            array(self::getInstance(), 'filter'),
            func_get_args()
        );
    }

    /**
     * Subscribe to a topic
     *
     * @param  string        $topic   Topic to subscribe to
     * @param  string|object $context Function name, class name, or object instance
     * @param  null|string   $handler If $context is a class or object, the name of the method to call
     * @return Phly_PubSub_Handle Pub-Sub handle (to allow later unsubscribe)
     */
    public static function subscribe($topic, $context, $handler = null)
    {
        $provider = self::getInstance();
        return $provider->subscribe($topic, $context, $handler);
    }

    /**
     * Unsubscribe a handler from a topic
     *
     * @param  Phly_PubSub_Handle $handle Handler to unsubcribe from a topic
     * @return bool Returns true if topic and handle found, and unsubscribed;
     *              returns false if either topic or handle not found.
     */
    public static function unsubscribe(Phly_PubSub_Handle $handle)
    {
        $provider = self::getInstance();
        return $provider->unsubscribe($handle);
    }

    /**
     * Retrieve all registered topics
     *
     * @return array
     */
    public static function getTopics()
    {
        $provider = self::getInstance();
        return $provider->getTopics();
    }

    /**
     * Retrieve all handlers for a given topic
     *
     * @param  string $topic Topic to get handlers for
     * @return array Array of Phly_PubSub_Handle objects
     */
    public static function getSubscribedHandles($topic)
    {
        $provider = self::getInstance();
        return $provider->getSubscribedHandles($topic);
    }

    /**
     * Clear all handlers for a given topic
     *
     * @param  string $topic Topic to clear handlers for
     * @return void
     */
    public static function clearHandles($topic)
    {
        $provider = self::getInstance();
        return $provider->clearHandles($topic);
    }
}
