<?php
/**
 * Integrate the site module with the rest of the application.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Site_Module extends P4Cms_Module_Integration
{
    /**
     * Perform early integration work (before load).
     */
    public static function init()
    {
        // register a controller plugin to handle access/branch acl check
        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin(new Site_AccessBranchCheck);

        // provide form to filter modules by keyword search.
        P4Cms_PubSub::subscribe('p4cms.site.module.grid.form.subForms',
            function(Zend_Form $form)
            {
                return new Ui_Form_GridSearch;
            }
        );

        // provide form to filter modules by type
        P4Cms_PubSub::subscribe('p4cms.site.module.grid.form.subForms',
            function(Zend_Form $form)
            {
                $options = array(
                    ''          => 'Any Type',
                    'core'      => 'Only Core',
                    'external'  => 'Only Optional'
                );

                $form = new P4Cms_Form_SubForm;
                $form->setName('typeFilter')
                     ->setAttrib('class', 'type-filter-form')
                     ->setOrder(10)
                     ->addElement(
                        'Radio', 'display',
                        array(
                            'label'         => 'Type',
                            'multiOptions'  => $options,
                            'autoApply'     => true,
                            'order'         => 20,
                            'value'         => ''
                        )
                     );

                return $form;
            }
        );

        // provide form to filter modules by status
        P4Cms_PubSub::subscribe('p4cms.site.module.grid.form.subForms',
            function(Zend_Form $form)
            {
                $options = array(
                    ''          => 'Any Status',
                    'enabled'   => 'Only Enabled',
                    'disabled'  => 'Only Disabled'
                );

                $form = new P4Cms_Form_SubForm;
                $form->setName('statusFilter')
                     ->setAttrib('class', 'status-filter-form')
                     ->setOrder(20)
                     ->addElement(
                        'Radio', 'display',
                        array(
                            'label'         => 'Status',
                            'multiOptions'  => $options,
                            'autoApply'     => true,
                            'order'         => 20,
                            'value'         => ''
                        )
                     );

                return $form;
            }
        );

        // provide form to filter modules by tag
        P4Cms_PubSub::subscribe('p4cms.site.module.grid.form.subForms',
            function(Zend_Form $form)
            {
                $tags = array();
                foreach (P4Cms_Module::fetchAll() as $module) {
                    $moduleTags = $module->getTags();
                    if (count($moduleTags)) {
                        $tags += array_combine($moduleTags, $moduleTags);
                    }
                }
                if (!count($tags)) {
                    return null;
                }

                natcasesort($tags);
                $form = new P4Cms_Form_SubForm;
                $form->setName('tagFilter')
                     ->setAttrib('class', 'tag-filter-form')
                     ->setOrder(30)
                     ->addElement(
                        'MultiCheckbox', 'display',
                        array(
                            'label'         => 'Tags',
                            'multiOptions'  => $tags,
                            'autoApply'     => true,
                            'order'         => 20,
                        )
                     );

                return $form;
            }
        );

        // filter modules by status
        P4Cms_PubSub::subscribe('p4cms.site.module.grid.populate',
            function(P4Cms_Model_Iterator $modules, Zend_Form $form)
            {
                // extract selected status.
                $values = $form->getValues();
                $status = $values['statusFilter']['display'];
                $status = isset($status) ? $status : '';

                switch ($status) {
                    case 'enabled':  return $modules->filter('enabled', array(true));
                    case 'disabled': return $modules->filter('enabled', array(false));
                    default:         return $modules;
                }
            }
        );

        // filter modules by type
        P4Cms_PubSub::subscribe('p4cms.site.module.grid.populate',
            function(P4Cms_Model_Iterator $modules, Zend_Form $form)
            {
                // extract selected status.
                $values = $form->getValues();
                $type = $values['typeFilter']['display'];
                $type = isset($type) ? $type : '';

                switch ($type) {
                    case 'core':     return $modules->filter('core', array(true));
                    case 'external': return $modules->filter('core', array(false));
                    default:         return $modules;
                }
            }
        );

        // filter modules by tag
        P4Cms_PubSub::subscribe('p4cms.site.module.grid.populate',
            function(P4Cms_Model_Iterator $modules, Zend_Form $form)
            {
                // extract selected status.
                $values = $form->getValues();
                if (!array_key_exists('tagFilter', $values)) {
                    return $modules;
                }
                $tags = $values['tagFilter']['display'];
                if (!isset($tags)) {
                    return $modules;
                }

                return $modules->filter(
                    'tags',
                    $tags,
                    array(
                        P4_Model_Iterator::FILTER_CONTAINS,
                        P4_Model_Iterator::FILTER_IMPLODE,
                    )
                );
            }
        );

        // filter module list by keyword search.
        P4Cms_PubSub::subscribe('p4cms.site.module.grid.populate',
            function(P4Cms_Model_Iterator $modules, Zend_Form $form)
            {
                $values = $form->getValues();

                // skip searching if there is no query
                $query = $values['search']['query'];
                if (!isset($query) || !strlen($query)) {
                    return;
                }

                // remove users that don't match search query.
                return $modules->search(
                    array('name', 'description', 'version', 'maintainerInfo'),
                    $values['search']['query']
                );
            }
        );

        // provide form to filter theme by keyword search.
        P4Cms_PubSub::subscribe('p4cms.site.theme.grid.form.subForms',
            function(Zend_Form $form)
            {
                return new Ui_Form_GridSearch;
            }
        );

        // filter theme list by keyword search.
        P4Cms_PubSub::subscribe('p4cms.site.theme.grid.populate',
            function(P4Cms_Model_Iterator $themes, Zend_Form $form)
            {
                $values = $form->getValues();

                // skip searching if there is no query
                $query = $values['search']['query'];
                if (!isset($query) || !strlen($query)) {
                    return;
                }

                // return themes that match search query.
                return $themes->search(
                    array('name', 'label', 'description', 'version', 'maintainerInfo'),
                    $query
                );
            }
        );

        // provide form to filter themes by tag
        P4Cms_PubSub::subscribe('p4cms.site.theme.grid.form.subForms',
            function(Zend_Form $form)
            {
                $tags = array();
                foreach (P4Cms_Theme::fetchAll() as $theme) {
                    $themeTags = $theme->getTags();
                    if (count($themeTags)) {
                        $tags += array_combine($themeTags, $themeTags);
                    }
                }
                if (!count($tags)) {
                    return null;
                }

                natcasesort($tags);
                $form = new P4Cms_Form_SubForm;
                $form->setName('tagFilter')
                     ->setAttrib('class', 'tag-filter-form')
                     ->setOrder(30)
                     ->addElement(
                        'MultiCheckbox', 'display',
                        array(
                            'label'         => 'Tags',
                            'multiOptions'  => $tags,
                            'autoApply'     => true,
                            'order'         => 20,
                        )
                     );

                return $form;
            }
        );

        // filter themes by tag
        P4Cms_PubSub::subscribe('p4cms.site.theme.grid.populate',
            function(P4Cms_Model_Iterator $themes, Zend_Form $form)
            {
                // extract selected status.
                $values = $form->getValues();
                if (!array_key_exists('tagFilter', $values)) {
                    return $themes;
                }
                $tags = $values['tagFilter']['display'];
                if (!isset($tags)) {
                    return $themes;
                }

                return $themes->filter(
                    'tags',
                    $tags,
                    array(
                        P4_Model_Iterator::FILTER_CONTAINS,
                        P4_Model_Iterator::FILTER_IMPLODE,
                    )
                );
            }
        );

        // provide form to filter sites/branches by keyword search.
        P4Cms_PubSub::subscribe('p4cms.site.branch.grid.form.subForms',
            function(Zend_Form $form)
            {
                return new Ui_Form_GridSearch;
            }
        );

        // filter site/branch list by keyword search.
        P4Cms_PubSub::subscribe('p4cms.site.branch.grid.populate',
            function(P4Cms_Model_Iterator $items, Zend_Form $form)
            {
                $values = $form->getValues();

                // skip searching if there is no query
                $query = $values['search']['query'];
                if (!isset($query) || !strlen($query)) {
                    return;
                }

                // return items that match search query.
                return $items->search(
                    array('name', 'owner'),
                    $query
                );
            }
        );

        // provide form to filter site/branch list by site.
        P4Cms_PubSub::subscribe('p4cms.site.branch.grid.form.subForms',
            function(Site_Form_BranchGridOptions $form)
            {
                $items   = $form->getItems()
                                ->filter('type', 'site', P4Cms_Model_Iterator::FILTER_COPY);
                $options = array_combine(
                    $items->invoke('getId'), $items->invoke('getValue', array('name'))
                );

                // exit early if we have no options
                if (empty($options)) {
                    return null;
                }

                $subform = new P4Cms_Form_SubForm;
                $subform->setName('site')
                        ->setAttrib('class', 'site-branch-form')
                        ->setOrder(20)
                        ->addElement(
                            'MultiCheckbox', 'sites',
                            array(
                                'label'         => 'Site',
                                'multiOptions'  => $options,
                                'autoApply'     => true
                            )
                        );

                return $subform;
            }
        );

        // filter site/branch list by site
        P4Cms_PubSub::subscribe('p4cms.site.branch.grid.populate',
            function(P4Cms_Model_Iterator $items, Zend_Form $form)
            {
                $values = $form->getValues();

                // extract selected sites.
                $sites = isset($values['site']['sites'])
                    ? $values['site']['sites']
                    : array();

                if (!empty($sites)) {
                    $items->filter('siteId', $sites);
                }
            }
        );

        // provide form to filter site/branch list by owner.
        P4Cms_PubSub::subscribe('p4cms.site.branch.grid.form.subForms',
            function(Site_Form_BranchGridOptions $form)
            {
                $users   = $form->getItems()->invoke('getValue', array('owner'));
                $options = array_combine($users, $users);

                // exit early if we have no users
                if (empty($users)) {
                    return null;
                }

                $subform = new P4Cms_Form_SubForm;
                $subform->setName('user')
                        ->setAttrib('class', 'site-branch-form')
                        ->setOrder(30)
                        ->addElement(
                            'MultiCheckbox', 'users',
                            array(
                                'label'         => 'Owner',
                                'filters'       => array('StringTrim'),
                                'multiOptions'  => $options,
                                'autoApply'     => true
                            )
                        );

                return $subform;
            }
        );

        // filter site/branch list by owner
        P4Cms_PubSub::subscribe('p4cms.site.branch.grid.populate',
            function(P4_Model_Iterator $items, Zend_Form $form)
            {
                $values = $form->getValues();

                // extract selected users.
                $users = isset($values['user']['users'])
                    ? $values['user']['users']
                    : array();

                if (!empty($users)) {
                    $items->filter('owner', $users);
                }
            }
        );

        // provide site/branch management actions
        P4Cms_PubSub::subscribe('p4cms.site.branch.grid.actions',
            function($actions)
            {
                $actions->addPages(
                    array(
                        array(
                            'label'     => 'View',
                            'onClick'   => 'p4cms.site.branch.grid.Actions.onClickView();',
                            'onShow'    => 'p4cms.site.branch.grid.Actions.onShowView(this);',
                            'order'     => '10'
                        ),
                        array(
                            'label'     => 'Edit',
                            'onClick'   => 'p4cms.site.branch.grid.Actions.onClickEdit();',
                            'onShow'    => 'p4cms.site.branch.grid.Actions.onShowEdit(this);',
                            'order'     => '20'
                        ),
                        array(
                            'label'     => 'Delete',
                            'onClick'   => 'p4cms.site.branch.grid.Actions.onClickDelete();',
                            'onShow'    => 'p4cms.site.branch.grid.Actions.onShowDelete(this);',
                            'order'     => '30'
                        ),
                        array(
                            'type'      => 'P4Cms_Navigation_Page_Separator',
                            'order'     => '40'
                        ),
                        array(
                            'label'     => 'Add Branch',
                            'onClick'   => 'p4cms.site.branch.grid.Actions.onClickAddBranch();',
                            'onShow'    => 'p4cms.site.branch.grid.Actions.onShowAddBranch(this);',
                            'order'     => '50'
                        )
                    )
                );
            }
        );

        // provide 'site' macro.
        P4Cms_PubSub::subscribe('p4cms.macro.site',
            function($params, $body, $context)
            {
                $field  = isset($params[0]) ? $params[0] : 'title';
                $site   = P4Cms_Site::fetchActive();
                $config = $site->getConfig();

                switch ($field) {
                    case 'title':
                        return $config->getTitle();
                        break;
                    case 'description':
                        return $config->getDescription();
                        break;
                    case 'theme':
                        return $config->getTheme();
                        break;
                    case 'branch':
                        return $site->getStream()->getName();
                        break;
                    default:
                        return null;
                }
            }
        );

        // provide 'theme' macro.
        P4Cms_PubSub::subscribe('p4cms.macro.theme',
            function($params, $body, $context)
            {
                $field = isset($params[0]) ? $params[0] : 'baseUrl';
                $theme = P4Cms_Theme::fetchActive();

                switch ($field) {
                    case 'baseUrl':
                        return $theme->getBaseUrl();
                        break;
                    default:
                        return null;
                }
            }
        );

        // organize config-related records when pulling changes.
        P4Cms_PubSub::subscribe(
            'p4cms.site.branch.pull.groupPaths',
            function($paths, $source, $target, $result)
            {
                // make a umbrella group for all config related entries.
                $config = new Site_Model_PullPathGroup;
                $config->setLabel('Configuration');
                $paths->addSubGroup($config);

                // make sub-groups to hold general config, theme selection and module config.
                $config->addSubGroup(
                    array(
                        'label'         => 'General Settings',
                        'hideCount'     => true,
                        'inheritPaths'  => $target->getId() . '/' . P4Cms_Site_Config::ID_GENERAL,
                        'details'       =>
                            function($paths) use ($source)
                            {
                                $path    = $paths->first();
                                $details = new P4Cms_Model_Iterator;

                                if ($path) {
                                    $details[] = new P4Cms_Model(
                                        array(
                                            'conflict' => $path->conflict,
                                            'action'   => $path->action,
                                            'type'     => 'Configuration',
                                            'label'    => 'General Settings'
                                        )
                                    );
                                }

                                return $details;
                            }
                    )
                );
                $config->addSubGroup(
                    array(
                        'label'         => 'Theme Setting',
                        'hideCount'     => true,
                        'inheritPaths'  => $target->getId() . '/' . P4Cms_Site_Config::ID_THEME,
                        'details'       =>
                            function($paths) use ($source)
                            {
                                $path    = $paths->first();
                                $details = new P4Cms_Model_Iterator;

                                if ($path) {
                                    $details[] = new P4Cms_Model(
                                        array(
                                            'conflict' => $path->conflict,
                                            'action'   => $path->action,
                                            'type'     => 'Configuration',
                                            'label'    => 'Theme Setting'
                                        )
                                    );
                                }

                                return $details;
                            }
                    )
                );
                // @todo if modules become branch specific we will have to be careful
                //       which branch we query here. We need to read from source.
                $config->addSubGroup(
                    array(
                        'label'         => 'Module Settings',
                        'basePaths'     => $target->getId() . '/config/module/...',
                        'inheritPaths'  => $target->getId() . '/config/module/...',
                        'details'       =>
                            function($paths) use ($source)
                            {
                                $details = new P4Cms_Model_Iterator;
                                foreach ($paths as $path) {
                                    $id        = basename($path->depotFile);
                                    $entry     = P4Cms_Module::fetch($id);

                                    // customize action to reflect module operations
                                    $action    = $path->action;
                                    $action    = $action == 'branch'    ? 'enable'    : $action;
                                    $action    = $action == 'delete'    ? 'disable'   : $action;
                                    $action    = $action == 'integrate' ? 'configure' : $action;

                                    $details[] = new P4Cms_Model(
                                        array(
                                            'conflict' => $path->conflict,
                                            'action'   => $action,
                                            'label'    => $entry->getName()
                                        )
                                    );
                                }

                                $details->setProperty(
                                    'columns',
                                    array('label' => 'Module', 'action' => 'Action')
                                );

                                return $details;
                            }
                    )
                );

                // exclude the url configuration from being pulled
                $paths->getPaths()->filter(
                    'depotFile',
                    $target->getId() . '/' . P4Cms_Site_Config::ID_URLS,
                    P4Cms_Model_Iterator::FILTER_INVERSE
                );
            }
        );

        // provide dynamic branches menu
        P4Cms_PubSub::subscribe('p4cms.navigation.dynamicHandlers',
            function()
            {
                $handler = new P4Cms_Navigation_DynamicHandler;
                $handler->setId('site.branches')
                        ->setLabel('Branch Listing')
                        ->setExpansionCallback(
                            function($item, $options)
                            {
                                // fetch branches for the active site.
                                $active   = P4Cms_Site::fetchActive();
                                $branches = P4Cms_Site::fetchAll(
                                    array(
                                        P4Cms_Site::FETCH_BY_SITE   => $active->getSiteId(),
                                        P4Cms_Site::FETCH_SORT_FLAT => true
                                    )
                                );

                                // add each branch as a menu item.
                                $items = array();
                                $user  = P4Cms_User::fetchActive();
                                foreach ($branches as $branch) {
                                    // doesn't make sense to include the active branch
                                    // as these items can only switch-to or pull-from
                                    if ($branch->getId() == $active->getId()) {
                                        continue;
                                    }

                                    // by default skip branches the user doesn't have
                                    // access to, if doPull is set, only skip branches
                                    // the user doesn't have permission to pull from.
                                    $privilege = $item->doPull ? 'pull-from' : 'access';
                                    if (!$user->isAllowed('branch', $privilege, $branch->getAcl())) {
                                        continue;
                                    }

                                    // we need json-encoded branch data for switch-to
                                    // and pull-from javascript functions.
                                    $data = Zend_Json::encode(
                                        array(
                                            'id'        => $branch->getId(),
                                            'name'      => $branch->getStream()->getName(),
                                            'basename'  => $branch->getBranchBasename(),
                                            'url'       => $branch->getConfig()->getUrl()
                                        )
                                    );

                                    // default behavior of item is to switch
                                    // to the branch, if doPull is set, prompt
                                    // to pull from the branch.
                                    $items[] = array(
                                        'label'   => $branch->getStream()->getName(),
                                        'onClick' => $item->doPull
                                            ? "p4cms.site.branch.pullFrom($data);"
                                            : "p4cms.site.branch.switchTo($data);"
                                    );
                                }

                                // if we don't have any items, add empty text if specified
                                if (!$items && $item->emptyText) {
                                    $items[] = array(
                                        'label'    => $item->emptyText,
                                        'disabled' => true
                                    );
                                }

                                // if there are items, add the separator if one was requested
                                if ($items && $item->separator == 'after') {
                                    $items[] = array('label' => '-');
                                }
                                if ($items && $item->separator == 'before') {
                                    array_unshift($items, array('label' => '-'));
                                }

                                return $items;
                            }
                        );

                return array($handler);
            }
        );
    }

    /**
     * Perform integration operations when the site is loaded.
     */
    public static function load()
    {
        // include the site description, if there is one, in the meta tags
        if (P4Cms_Site::hasActive()) {
            $site        = P4Cms_Site::fetchActive();
            $description = $site->getConfig()->getDescription();
            if (strlen($description)) {
                Zend_Layout::getMvcInstance()
                    ->getView()
                    ->headMeta()
                    ->appendName('description', $description);
            }

            // make 'id' and 'siteId' of the active site visible to javascript
            $view = Zend_Layout::getMvcInstance()->getView();
            $data = array(
                'id'     => $site->getId(),
                'siteId' => $site->getSiteId()
            );
            $script = "dojo.setObject('p4cms.site.active', " . Zend_Json::encode($data) . ");";
            $view->headScript()->appendScript($script);
        }
    }
}