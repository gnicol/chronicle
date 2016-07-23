<?php
/**
 * This is the category form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Category_Form_Manage extends P4Cms_Form
{
    protected $_uniqueTitleRequired = true;

    /**
     * Defines the elements that make up the category form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // form should use p4cms-ui styles.
        $this->setAttrib('class', 'p4cms-ui category-form');

        // set the method for the form to POST
        $this->setMethod('post');

        // add a field to collect the category title.
        $this->addElement(
            'text',
            'title',
            array(
                'label'         => 'Title',
                'required'      => true,
                'filters'       => array('StringTrim')
            )
        );

        $this->getElement('title')
             ->addDecorator(
                'Label',
                array('tag'=>'dt', 'class' => 'title-label')
             );

        // add a field to pick the parent category.
        $categories = Category_Model_Category::fetchAll();
        $options    = array('' => "No Parent");
        foreach ($categories as $category) {
            // to indent we use UTF-8 non-breaking spaces - we can't just
            // use &nbsp, because the ampersand gets escaped when rendered.
            $indent = str_repeat(static::UTF8_NBSP, $category->getDepth() * 2);
            $options[$category->getId()] = $indent . $category->getValue('title');
        }
        $this->addElement(
            'select',
            'parent',
            array(
                'label'         => 'Parent Category',
                'multiOptions'  => $options,
                'ignore'        => true
            )
        );

        // customize error for parent select input.
        $this->getElementValidator('parent', 'InArray')
             ->setMessage(
                "'%value%' is not a valid parent category.",
                Zend_Validate_InArray::NOT_IN_ARRAY
             );

        $this->getElement('parent')
             ->addDecorator(
                'Label',
                array('tag'=>'dt', 'class' => 'parent-label')
             );

        // add a field to collect the category description.
        $this->addElement(
            'textarea',
            'description',
            array(
                'label'         => 'Description',
                'required'      => false,
                'filters'       => array('StringTrim'),
                'rows'          => 3,
                'cols'          => 80
            )
        );

        // add a select list to pick display content or built-in rendering.
        $this->addElement(
            'contentSelect',
            'indexContent',
            array(
                'label'         => 'Index Page',
                'required'      => false,
                'description'   => 'Select a page to display when a user '
                                .  'navigates to this category. Leave blank '
                                .  'for the default presentation.',
                'browseOptions' => array('type' => array('types' => array('Pages/*')))
            )
        );

        // add the submit button
        $this->addElement(
            'SubmitButton',
            'save',
            array(
                'label'     => 'Save',
                'required'  => false,
                'class'     => 'preferred',
                'ignore'    => true
            )
        );

        // put the button in a fieldset.
        $this->addDisplayGroup(
            array('save'),
            'buttons',
            array('class' => 'buttons')
        );
    }

    /**
     * Set whether unique title is required.
     *
     * @param bool $uniqueTitleRequired     If true then form validation will
     *                                      fail if title is not unique.
     */
    public function setUniqueTitleRequired($uniqueTitleRequired)
    {
        $this->_uniqueTitleRequired = (bool) $uniqueTitleRequired;
    }

    /**
     * Override isValid to include check for unique title (if required).
     *
     * @param   array       $data   the field values to validate.
     * @return  boolean             true if the form values are valid.
     */
    public function isValid($data)
    {
        $valid = parent::isValid($data);

        // check if the title has at least one letter
        if (isset($data['title']) && !preg_match('/[a-zA-Z]/', $data['title'])) {
            $this->getElement('title')->addError(
                'The title must have at least one letter. '
                . 'Please choose a different title.'
            );

            $valid = false;
        } else if ($this->_uniqueTitleRequired) {
            // if required, check unique title
            if (Category_Model_Category::exists($this->composeCategoryId())) {
                $this->getElement('title')->addError(
                    'The title you provided conflicts with another. '
                    . 'Please choose a different title.'
                );
                $valid = false;
            }
        }

        return $valid;
    }

    /**
     * Provide functionality to convert a title->id.
     *
     * @return  string  id based on title.
     */
    public function composeCategoryId()
    {
        $id = $this->getValue('parent');
        if (strlen($id)) {
            $id .= '/';
        }

        $filter = new P4Cms_Filter_TitleToId;
        $id    .= $filter->filter($this->getValue('title'));

        return $id;
    }
}
