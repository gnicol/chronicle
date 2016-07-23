<?php
/**
 * This is the content entry form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Form_Content extends P4Cms_Form_PubSubForm
{
    protected   $_contentEntry  = null;

    /**
     * Sets up the content entry form
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // we need the content entry and type to make the form.
        $entry = $this->getEntry();
        $type  = $entry->getContentType();

        // set the pub/sub topic so others can influence form
        $this->setTopic('p4cms.content.form');

        $this->setMethod('post');
        $this->setAttrib('class', 'p4cms-ui content-form');

        // add a hidden content type field.
        $this->addElement(
            'hidden',
            P4Cms_Content::TYPE_FIELD,
            array('value' => $type->getId())
        );

        // add the type specific elements to the form.
        $this->addElements($type->getFormElements());

        // if form contains an id element, disable it for edits
        // and apply special validation rules for adds.
        $id = $this->getElement('id');
        if ($id && $entry->getId()) {
            $id->setValue($entry->getId())
               ->setAttrib('disabled', true);
        } else if ($id) {
            if (!array_key_exists('ContentId', $id->getValidators())) {
                $id->addValidator('ContentId');
            }

            $id->getValidator('ContentId')
               ->setAllowExisting(false)
               ->setAllowEmpty(false);
        }

        // decorate each form element so that we can easily identify them in the dom.
        foreach ($this->getElements() as $element) {
            $element->addDecorator(
                array('DivTag' => 'HtmlTag'),
                array(
                    'tag'   => 'div',
                    'id'    => 'content-form-' . $element->getName(),
                    'class' => 'content-form-element'
                )
            );
        }

        // put all of the general content elements into a display-group.
        $this->addDisplayGroup(
            array_keys($this->getElements()),
            'content-form-elements',
            array(
                'class' => 'content-form-elements',
                'order' => -1000
            )
        );

        // create save sub-form to provide save button and comment field.
        $saveForm = new Content_Form_Save(
            array(
                'idPrefix'  => $this->getIdPrefix(),
                'dojoType'  => 'p4cms.content.SaveSubForm',
                'order'     => 1000
            )
        );

        // normalize sub-form (e.g. to have the same decorators as
        // sub-forms added via pub/sub sub-form topic) - we must
        // set is-array to false after normalization because part of
        // the normalization is to set is-array to true.
        static::normalizeSubForm($saveForm, 'save');
        $saveForm->setIsArray(false);
        $this->addSubForm($saveForm, 'save');

        // call parent to publish the form.
        parent::init();
    }

    /**
     * Set the content entry instance we are constructing a form for.
     *
     * @param   P4Cms_Content   $entry  the entry instance to make a form for
     * @return  Content_Form_Content    provides fluent interface.
     */
    public function setEntry(P4Cms_Content $entry)
    {
        $this->_contentEntry = $entry;

        return $this;
    }

    /**
     * Get the content entry instance we are constructing a form for.
     *
     * @return  P4Cms_Content   the content entry this form is for.
     */
    public function getEntry()
    {
        if (!$this->_contentEntry instanceof P4Cms_Content) {
            throw new Content_Exception(
                "Cannot get content entry. No entry has been set."
            );
        }

        return $this->_contentEntry;
    }

    /**
     * Extends parent to populate from entry when called without values.
     *
     * @param   P4Cms_Record|array|null     $values     optional - values to populate the form from.
     *                                                  if null, populates from entry.
     * @return  P4Cms_Form                  provides fluent interface.
     */
    public function populate($values = null)
    {
        // if values input is empty, populate from entry.
        if (empty($values)) {
            $values = $this->getEntry();
        }

        return parent::populate($values);
    }

    /**
     * Ensure consistent presentation of sub-forms.
     * Extended to make sub-forms into dijits.
     *
     * @param   Zend_Form   $form   the sub-form to normalize.
     * @param   string      $name   the name of the sub-form.
     * @return  Zend_Form   the updated form.
     */
    public function addSubForm($form, $name)
    {
        parent::addSubForm($form, $name);

        // set dojoType from the attribute if its present or assign default value
        // clear the dojoType attribute to avoid turning another element into the
        // same dijit as well
        $type = $form->getAttrib('dojoType');
        if ($type) {
            $form->removeAttrib('dojoType');
        } else {
            $type = 'p4cms.content.SubForm';
        }

        $fieldset = $form->getDecorator('DdTag');
        $fieldset->setOption('formName',    $name)
                 ->setOption('id',          $this->getIdPrefix() . $name . '-sub-form')
                 ->setOption('class',       'content-sub-form')
                 ->setOption('dojoType',    $type);

        return $form;
    }
}
