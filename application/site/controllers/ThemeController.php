<?php
/**
 * List and apply the site theme.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Site_ThemeController extends Zend_Controller_Action
{
    public $contexts = array(
        'index'     => array('json', 'partial'),
    );

    /**
     * Use management layout for all actions
     */
    public function init()
    {
        // list of actions that will be skipped from the permissions check
        $skipActions = array('icon');

        // enforce permissions.
        if (!in_array($this->getRequest()->getActionName(), $skipActions)) {
            $this->_helper->acl->check('site', 'manage-themes');
        }

        // enable logging of the theme parameter
        $this->getHelper('audit')->addLoggedParam('theme');

        // clear theme cache in case it is stale.
        P4Cms_Theme::clearCache();
    }

    /**
     * List available themes.
     *
     * @publishes   p4cms.site.theme.grid.data.item
     *              Return the passed item after applying any modifications (add properties, change
     *              values, etc.) to influence the row values sent to the Manage Themes grid.
     *              array                       $item       The item to potentially modify.
     *              mixed                       $model      The original object/array that was used
     *                                                      to make the item.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.site.theme.grid.data
     *              Adjust the passed data (add properties, modify values, etc.) to influence the
     *              row values sent to the Manage Themes grid.
     *              Zend_Dojo_Data              $data       The data to be filtered.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.site.theme.grid.populate
     *              Adjust the passed iterator (possibly based on values in the passed form) to
     *              filter which themes will be shown on the Manage Themes grid.
     *              P4Cms_Model_Iterator        $themes     An iterator of P4Cms_Theme objects.
     *              P4Cms_Form_PubSubForm       $form       A form containing filter options.
     *
     * @publishes   p4cms.site.theme.grid.render
     *              Make adjustments to the datagrid helper's options pre-render (e.g. change
     *              options to add columns) for the Manage Themes grid.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.site.theme.grid.form
     *              Make arbitrary modifications to the Manage Themes filters form.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *
     * @publishes   p4cms.site.theme.grid.form.subForms
     *              Return a Form (or array of Forms) to have them added to the Manage Themes
     *              filters form. The returned form(s) should have a 'name' set on them to allow
     *              them to be uniquely identified.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *
     * @publishes   p4cms.site.theme.grid.form.preValidate
     *              Allows subscribers to adjust the Manage Themes filters form prior to validation
     *              of the passed data. For example, modify element values based on related
     *              selections to permit proper validation.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     An associative array of form values.
     *
     * @publishes   p4cms.site.theme.grid.form.validate
     *              Return false to indicate the Manage Themes filters form is invalid. Return true
     *              to indicate your custom checks were satisfied, so form validity should be
     *              unchanged.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     An associative array of form values.
     *
     * @publishes   p4cms.site.theme.grid.form.populate
     *              Allows subscribers to adjust the Manage Themes filters form after it has been
     *              populated with the passed data.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     The values passed to the populate
     *                                                      method.
     */
    public function indexAction()
    {
        // get list option sub-forms.
        $request        = $this->getRequest();
        $gridNamespace  = 'p4cms.site.theme.grid';
        $form           = new Ui_Form_GridOptions(
            array(
                'namespace'   => $gridNamespace
            )
        );
        $form->populate($request->getParams());

        // setup view.
        $view               = $this->view;
        $view->form         = $form;
        $view->pageSize     = $request->getParam('count', 100);
        $view->rowOffset    = $request->getParam('start', 0);
        $view->pageOffset   = round($view->rowOffset / $view->pageSize, 0) + 1;
        $view->theme        = P4Cms_Theme::fetchActive();
        $view->headTitle()->set('Manage Themes');

        // set DataGrid view helper namespace
        $helper = $view->dataGrid();
        $helper->setNamespace($gridNamespace);

        // early exit for standard requests (ie. not json)
        if (!$this->contextSwitch->getCurrentContext()) {
            $this->getHelper('helpUrl')->setUrl('themes.management.html');
            $this->_helper->layout->setLayout('manage-layout');
            return;
        }

        // fetch themes and allow third-parties to manipulate the list
        $themes = P4Cms_Theme::fetchAll();
        try {
            $result = P4Cms_PubSub::publish($gridNamespace . '.populate', $themes, $form);
        } catch (Exception $e) {
            P4Cms_Log::logException("Error building theme list.", $e);
        }

        // prepare sorting options
        // some requested sort fields are composites of multiple fields, so we need a map
        $sortKeyMap = array(
            'name'          => 'name',
            'maintainer'    => array('maintainerInfo', 'name'),
            'status'        => array(
                'name'      => array(P4_Model_Iterator::SORT_NATURAL)
            ),
        );
        $sortKey    = $request->getParam('sort');
        $sortKey    = isset($sortKey) && strlen($sortKey) ? $sortKey : 'name';
        $sortFlags  = array(
            P4Cms_Model_Iterator::SORT_NATURAL,
            P4Cms_Model_Iterator::SORT_NO_CASE
        );
        if (substr($sortKey, 0, 1) == '-') {
            $sortKey = substr($sortKey, 1);
            $sortFlags[] = P4Cms_Model_Iterator::SORT_DESCENDING;
        }

        // apply sorting options and place in the view.
        $view->themes = $themes->sortBy($sortKeyMap[$sortKey], $sortFlags);
    }

    /**
     * Switch themes.
     *
     * @publishes   p4cms.site.theme.disabled
     *              Perform operations when a theme is disabled by the Site module.
     *              P4Cms_Site      $site   The site for which the theme is being disabled.
     *              P4Cms_Theme     $theme  The theme being disabled.
     *
     * @publishes   p4cms.site.theme.enabled
     *              Perform operations when a theme is enabled by the Site module.
     *              P4Cms_Site      $site   The site for which the theme is being enabled.
     *              P4Cms_Theme     $theme  The theme being enabled.
     */
    public function applyAction()
    {
        // enforce permissions
        $this->acl->check('site', 'manage-themes');

        // only respond to post requests.
        $request = $this->getRequest();
        if (!$request->isPost()) {
            throw new Site_Exception(
                "Can't apply theme. Request was not a valid HTTP POST.");
        }

        // get the active site branch config.
        $site   = P4Cms_Site::fetchActive();
        $config = $site->getConfig();

        // change the site theme
        try {
            // notify subscribers of theme disabled event.
            try {
                $theme = P4Cms_Theme::fetch($config->getTheme());
                P4Cms_PubSub::publish('p4cms.site.theme.disabled', $site, $theme);
            } catch (Exception $e) {
                P4Cms_Log::logException("Error disabling active theme.", $e);
            }

            // make the switch.
            $config->setTheme($request->theme)
                   ->save();

            // theme changes can have quite an affect; clear caches
            P4Cms_Cache::clean();

            // notify subscribers of theme enabled event.
            $theme = P4Cms_Theme::fetch($config->getTheme());
            P4Cms_PubSub::publish('p4cms.site.theme.enabled', $site, $theme);

            // notify user of successful theme change
            $label = $theme->getLabel();
            P4Cms_Notifications::add(
                "$label theme successfully applied.",
                P4Cms_Notifications::SEVERITY_SUCCESS
            );
        } catch (P4Cms_Theme_Exception $e) {
            // keep current theme, display error message
            P4Cms_Notifications::add(
                "Theme '" . $request->theme . "' could not be applied."
                . " Please check that the theme exists in the correct location and try your change again.",
                P4Cms_Notifications::SEVERITY_ERROR
            );
        }

        $this->redirector->gotoSimple('index');
    }

    /**
     * Check if the given request uri is for a regular (non-zero) file,
     * a directory or a symbolic link.
     *
     * @param   string  $requestUri     the uri to evaluate.
     * @return  boolean true if the uri evaluates to a file, directory or link.
     */
    private function _isResourceRequest($requestUri)
    {
        // if requestUri has no substance, return false.
        if (!$requestUri || $requestUri == '/') {
            return false;
        }

        $filename = BASE_PATH . $requestUri;

        if (is_file($filename) && filesize($filename) > 0) {
            return true;
        }
        if (is_dir($filename)) {
            return true;
        }
        if (is_link($filename)) {
            return true;
        }
        return false;
    }
}

