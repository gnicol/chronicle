<?php
/**
 * Integrate the history module with the rest of the application.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class History_Module extends P4Cms_Module_Integration
{
    /**
     * Perform early integration work (before load).
     */
    public static function init()
    {
        // provide form to filter history list by search term.
        P4Cms_PubSub::subscribe('p4cms.history.grid.form.subForms',
            function(Zend_Form $form)
            {
                return new Ui_Form_GridSearch;
            }
        );

        // filter history list by search term
        P4Cms_PubSub::subscribe('p4cms.history.grid.populate',
            function(P4_Model_Iterator $changes, Zend_Form $form)
            {
                $values = $form->getValues();

                // extract search query or empty string if none.
                $query = isset($values['search']['query'])
                    ? $values['search']['query']
                    : '';

                // if we have a query; filter down the lists of changes
                if (!empty($query)) {
                    $changes->search(array('User', 'Description', 'Date'), $query);
                }
            }
        );

        // provide form to filter history list by user.
        P4Cms_PubSub::subscribe('p4cms.history.grid.form.subForms',
            function(Zend_Form $form)
            {
                $users   = array_unique(
                    $form->getChanges()->invoke('getUser')
                );
                $options = array_combine($users, $users);

                // exit early if we have no users
                if (empty($users)) {
                    return null;
                }
                
                $subform = new P4Cms_Form_SubForm;
                $subform->setName('user')
                        ->setAttrib('class', 'history-form')
                        ->setOrder(20)
                        ->addElement(
                            'MultiCheckbox', 'users',
                            array(
                                'label'         => 'User',
                                'filters'       => array('StringTrim'),
                                'multiOptions'  => $options,
                                'autoApply'     => true
                            )
                        );

                return $subform;
            }
        );

        // filter history list by user 
        P4Cms_PubSub::subscribe('p4cms.history.grid.populate',
            function(P4_Model_Iterator $changes, Zend_Form $form)
            {
                $values = $form->getValues();

                // extract selected users.
                $users = isset($values['user']['users'])
                    ? $values['user']['users']
                    : array();

                if (!empty($users)) {
                    $changes->filter('User', $users);
                }
            }
        );

        // provide form to filter history list by age.
        P4Cms_PubSub::subscribe('p4cms.history.grid.form.subForms',
            function(Zend_Form $form)
            {
                $options = array(
                    ''  => 'Any time',
                    1   => 'Past Day',
                    7   => 'Past Week',
                    31  => 'Past Month',
                    365 => 'Past Year'
                );

                $subform = new P4Cms_Form_SubForm;
                $subform->setName('modified')
                        ->setAttrib('class', 'history-form')
                        ->setOrder(30)
                        ->addElement(
                            'radio', 'range',
                            array(
                                'label'         => 'Modified',
                                'multiOptions'  => $options,
                                'autoApply'     => true,
                                'value'         => ''

                            )
                        );

                return $subform;
            }
        );

        // filter history list by age
        P4Cms_PubSub::subscribe('p4cms.history.grid.populate',
            function(P4_Model_Iterator $changes, Zend_Form $form)
            {
                $values = $form->getValues();

                // extract selected modification range.
                $range = isset($values['modified']['range'])
                    ? $values['modified']['range']
                    : null;

                if (!empty($range)) {
                    $minAge = time() - $range * 3600 * 24;
                    $changes->filterByCallback(
                        function ($change) use ($minAge)
                        {
                            return strtotime($change->getDateTime()) >= $minAge;
                        }
                    );
                }
            }
        );
    }
}
