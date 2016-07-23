<?php
/**
 * A 'pub-sub' record is a record that can be modified via pub/sub.
 * Topics published:
 *
 *  <record-topic>.preSave   -  do work (e.g. manipulate record) before it is saved
 *  <record-topic>.postSave  -  do work after record is saved, but before it is committed
 *  <record-topic>.query     -  influence query options for fetch/fetchAll/count/exists
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Record_PubSubRecord extends P4Cms_Record
{
    const               SAVE_SKIP_PUBSUB    = 'skipPubSub';
    protected static    $_topic             = null;

    /**
     * Get the topic for publishing this record.
     *
     * @return  string  the topic for publishing this record.
     * @throws  InvalidArgumentException    if no topic is set.
     */
    public static function getTopic()
    {
        if (static::$_topic === null) {
            throw new P4Cms_Record_Exception("No topic set for this record");
        }

        return static::$_topic;
    }

    /**
     * Check if a topic has been set for this form.
     *
     * @return  bool    true if a topic has been set, false otherwise.
     */
    public static function hasTopic()
    {
        return !is_null(static::$_topic);
    }

    /**
     * Extend basic save to allow third-party participation via pub/sub.
     * Two topics are published:
     *
     *   preSave - fires after batch is started, but before record is saved
     *  postSave - fires after record is saved, but before batch is committed
     *
     * The entire operation is wrapped in a batch (unless there is already a
     * batch underway). The batch will be committed automatically unless
     * an exception occurs, in which case the batch will be reverted.
     *
     * @param   string              $description    optional - a description of the change.
     * @param   null|string|array   $options        optional - augment save behavior:
     *
     *                                               SAVE_THROW_CONFLICTS - throw exceptions on conflicts,
     *                                                                      default is to silently overwrite
     *                                                   SAVE_SKIP_PUBSUB - don't publish pre/post save topics
     *
     * @return  P4Cms_Record        provides a fluent interface
     */
    public function save($description = null, $options = null)
    {
        // if we are skipping pub/sub, let parent take care of everything.
        if (in_array(static::SAVE_SKIP_PUBSUB, (array) $options)) {
            return parent::save($description, $options);
        }

        // ensure we have a save description.
        $description = $description ?: $this->_generateSubmitDescription();

        // start the batch
        $adapter = $this->getAdapter();
        $batch   = !$adapter->inBatch()
            ? $adapter->beginBatch($description)
            : false;

        // wrap in a try/catch so we can cleanup if something goes wrong.
        try {

            // allow third-parties to manipulate record prior to save.
            P4Cms_PubSub::publish(
                static::getTopic() . P4Cms_PubSub::TOPIC_DELIMITER . "preSave",
                $this
            );

            // now let parent do a normal save.
            parent::save($description, $options);

            // allow third-parties to interact with record post save.
            P4Cms_PubSub::publish(
                static::getTopic() . P4Cms_PubSub::TOPIC_DELIMITER . "postSave",
                $this
            );

        } catch (Exception $e) {
            if ($batch) {
                $adapter->revertBatch();
            }
            throw $e;
        }

        // commit the batch.
        if ($batch) {
            $adapter->commitBatch(null, $options);
        }

        return $this;
    }

    /**
     * Extend basic delete to allow third-party participation via pub/sub.
     *
     * @param   string  $description  optional - a description of the change.
     * @return  P4Cms_Record          provides fluent interface.
     */
    public function delete($description = null)
    {
        // ensure we have a change description.
        $description = $description ?: $this->_generateSubmitDescription();

        // start the batch
        $adapter = $this->getAdapter();
        $batch   = !$adapter->inBatch()
            ? $adapter->beginBatch($description)
            : false;

        // wrap in a try/catch so we can cleanup if something goes wrong.
        try {

            // allow third-parties to interact with record prior to delete.
            P4Cms_PubSub::publish(
                static::getTopic() . P4Cms_PubSub::TOPIC_DELIMITER . "delete",
                $this
            );

            // now let parent do a normal delete.
            parent::delete($description);

        } catch (Exception $e) {
            if ($batch) {
                $adapter->revertBatch();
            }
            throw $e;
        }

        // commit the batch.
        if ($batch) {
            $adapter->commitBatch();
        }

        return $this;
    }

    /**
     * Extended basic fetchAll to provide a 'query' pub/sub topic.
     *
     * @param   P4Cms_Record_Query|array|null   $query      optional - query options to augment result.
     * @param   P4Cms_Record_Adapter            $adapter    optional - storage adapter to use.
     * @return  P4Cms_Model_Iterator            all records of this type.
     * @todo    Change default return to be keyed by record id. This will break numerous tests.
     */
    public static function fetchAll($query = null, P4Cms_Record_Adapter $adapter = null)
    {
        $query = static::_normalizeQuery($query);

        // if no adapter given, use default.
        $adapter = $adapter ?: static::getDefaultAdapter();

        // give third-parties a change to influence query.
        P4Cms_PubSub::publish(
            static::getTopic() . P4Cms_PubSub::TOPIC_DELIMITER . "query",
            $query,
            $adapter
        );

        return parent::fetchAll($query, $adapter);
    }

    /**
     * Extended basic count to provide a 'query' pub/sub topic.
     *
     * @param   P4Cms_Record_Query      $query      optional - query options to augment result.
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     * @return  integer                 The count of all matching records
     */
    public static function count(
        P4Cms_Record_Query   $query   = null,
        P4Cms_Record_Adapter $adapter = null)
    {
        $query = static::_normalizeQuery($query);

        // if no adapter given, use default.
        $adapter = $adapter ?: static::getDefaultAdapter();

        // give third-parties a change to influence query.
        P4Cms_PubSub::publish(
            static::getTopic() . P4Cms_PubSub::TOPIC_DELIMITER . "query",
            $query,
            $adapter
        );

        return parent::count($query, $adapter);
    }
}