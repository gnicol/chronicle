<?php
/**
 * A 'pub-sub' form is a form that can be modified via pub/sub.
 * Topics published:
 *
 *  <form-topic>           -  general form manipulation at init time
 *  <form-topic>.subForms  -  provide sub forms (via return) at init time
 *  <form-topic>.validate  -  validate form data (true for valid, false otherwise)
 *  <form-topic>.populate  -  populate form from data
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Form_PubSubForm extends P4Cms_Form
{
    protected   $_topic = null;

    /**
     * If this form has a topic set, automatically publish to collect
     * any sub-forms and allow arbitrary modification of the form itself.
     */
    public function init()
    {
        if (!$this->hasTopic()) {
            return;
        }

        // collect any sub-forms.
        $this->publishSubForms();

        // allow arbitrary modification of this form.
        $this->publish();
    }

    /**
     * Specify the topic to use when publishing this form.
     *
     * @param   string  $topic              the topic to use when publishing this form.
     * @return  P4Cms_Form_PubSubForm       provides fluent interface
     * @throws  InvalidArgumentException    if topic is not a string or null
     */
    public function setTopic($topic)
    {
        if (!is_string($topic) && !is_null($topic)) {
            throw new InvalidArgumentException("Form topic must be a string or null");
        }
        
        $this->_topic = $topic;
        
        return $this;
    }

    /**
     * Get the topic for publishing this form.
     *
     * @return  string  the topic for publishing this form.
     * @throws  InvalidArgumentException    if no topic is set.
     */
    public function getTopic()
    {
        if ($this->_topic === null) {
            throw new P4Cms_Form_Exception("No topic set for this form");
        }

        return $this->_topic;
    }

    /**
     * Check if a topic has been set for this form.
     * 
     * @return  bool    true if a topic has been set, false otherwise.
     */
    public function hasTopic()
    {
        return !is_null($this->_topic);
    }

    /**
     * Collect sub-forms for this form by publishing to the
     * form topic + '/sub-forms'. Sub-forms are automatically
     * normalized for consistent presentation and added.
     *
     * @return  P4Cms_Form_PubSubForm   provides fluent interface
     */
    public function publishSubForms()
    {
        $feedback = $this->publish('subForms');

        // process sub-form feedback.
        foreach ($feedback as $subForms) {

            if (!is_array($subForms)) {
                $subForms = array($subForms);
            }

            foreach ($subForms as $subForm) {
                // skip cases where the subscriber decided not to return a form
                if (!isset($subForm)) {
                    continue;
                }
                if (!$subForm instanceof Zend_Form || !$subForm->getName()) {
                    P4Cms_Log::log(
                        "Encountered invalid pub-sub sub-form.",
                        P4Cms_Log::ERR
                    );
                    P4Cms_Log::log(print_r($subForm, true), P4Cms_Log::DEBUG);

                    // skip form.
                    continue;
                }

                $name = $subForm->getName();

                // ensure consistent sub-form markup.
                static::normalizeSubForm($subForm, $name);

                // add it.
                $this->addSubForm($subForm, $name);
            }
        }

        return $this;
    }

    /**
     * Validate the form - publish to the form topic + '/validate'
     * to allow third-party involvement in validation.
     *
     * Subscribers should return true if the data is valid and false
     * otherwise. Errors can be added directly to the form object.
     *
     * @param  array    $data   the data to validate.
     * @return boolean
     */
    public function isValid($data)
    {
        // allow third-parties to make adjustments to the 
        // form and influence the outcome of the validation.
        $this->publish('preValidate', $data);

        $isValid = parent::isValid($data);

        // allow third-parties to validate the form
        $feedback = $this->publish('validate', $data);

        // any false feedback means the form is invalid.
        foreach ($feedback as $valid) {
            if (!$valid) {
                $isValid            = false;
                $this->_errorsExist = true;
            }
        }

        return $isValid;
    }

    /**
     * Populate the form from key-value array. Extended to publish to form
     * topic + '/populate' so that third-parties can participate.
     *
     * @param   P4Cms_Record|array      $values     the values to populate the form from.
     * @return  P4Cms_Form_PubSubForm   provides fluent interface.
     */
    public function populate($values)
    {
        parent::populate($values);

        // turn records into arrays before publishing so that
        // subscribers consistently get array input.
        if ($values instanceof P4Cms_Record) {
            $values = $values->getValues();
        }
        
        $this->publish('populate', $values);

        return $this;
    }

    /**
     * Publish this form. Happens automatically on init() if the topic has been set.
     * Pass subTopic to append a suffix to the topic. Pass additional args to be
     * included in the publish call (always includes the form instance by default).
     * 
     * @param   string  $subTopic   optional - suffix to add to the form topic.
     * @param   mixed   $args       optional - all arguments besides the topic
     *                              are passed as arguments to the handler
     * @return  array   the return values of all subscribers.
     */
    public function publish($subTopic = null, $args = null)
    {
        $topic = $subTopic
            ? $this->getTopic() . P4Cms_PubSub::TOPIC_DELIMITER . $subTopic
            : $this->getTopic();

        // inject topic and form into args.
        $args = func_get_args();
        array_splice($args, 0, 1, array($topic, $this));

        return call_user_func_array(
            array('P4Cms_PubSub', 'publish'),
            $args
        );
    }
}
