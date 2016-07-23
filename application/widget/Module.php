<?php
/**
 * Integrate the widget module with the rest of the application.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Widget_Module extends P4Cms_Module_Integration
{
    /**
     * Perform early integration work (before load).
     */
    public static function init()
    {
        // update widgets when a site is created.
        P4Cms_PubSub::subscribe('p4cms.site.created',
            function(P4Cms_Site $site)
            {
                $adapter = $site->getStorageAdapter();
                P4Cms_Widget::installDefaults($adapter);
            }
        );

        // update widgets when a module/theme is enabled.
        $installDefaults = function(P4Cms_Site $site, P4Cms_PackageAbstract $package)
        {
            $adapter = $site->getStorageAdapter();
            P4Cms_Widget::installPackageDefaults($package, $adapter, true);
        };

        P4Cms_PubSub::subscribe('p4cms.site.module.enabled', $installDefaults);
        P4Cms_PubSub::subscribe('p4cms.site.theme.enabled',  $installDefaults);

        // update widgets when a module/theme is disabled
        $removeDefaults = function(P4Cms_Site $site, P4Cms_PackageAbstract $package)
        {
            $adapter = $site->getStorageAdapter();
            P4Cms_Widget::removePackageDefaults($package, $adapter);
        };

        P4Cms_PubSub::subscribe('p4cms.site.module.disabled', $removeDefaults);
        P4Cms_PubSub::subscribe('p4cms.site.theme.disabled',  $removeDefaults);

        // organize widget records when pulling changes.
        P4Cms_PubSub::subscribe(
            'p4cms.site.branch.pull.groupPaths',
            function($paths, $source, $target, $result)
            {
                $paths->addSubGroup(
                    array(
                        'label'         => 'Widgets',
                        'basePaths'     => $target->getId() . '/widgets/...',
                        'inheritPaths'  => $target->getId() . '/widgets/...',
                        'pullByDefault' => true,
                        'count'         =>
                            function($group, $count, $options) use ($target)
                            {
                                return $group->getPaths($options)->filter(
                                    'depotFile',
                                    $target->getId() . '/widgets/',
                                    array(
                                        P4Cms_Model_Iterator::FILTER_COPY,
                                        P4Cms_Model_Iterator::FILTER_STARTS_WITH
                                    )
                                )->count();
                            },
                        'details'       =>
                            function($paths) use ($source, $target)
                            {
                                $pathsById = array();
                                foreach ($paths as $path) {
                                    if (strpos($path->depotFile, $target->getId() . '/widgets/') === 0) {
                                        $pathsById[P4Cms_Widget::depotFileToId($path->depotFile)] = $path;
                                    }
                                }

                                $details = new P4Cms_Model_Iterator;
                                $entries = Site_Model_PullPathGroup::fetchRecords(
                                    array_keys($pathsById), 'P4Cms_Widget', $source, $target
                                );
                                foreach ($entries as $entry) {
                                    $path      = $pathsById[$entry->getId()];
                                    $label     = str_replace(array('-', '_'), ' ', $entry->getValue('region'));
                                    $label     = ucwords($label) . ' - ' . $entry->getValue('title');
                                    $details[] = new P4Cms_Model(
                                        array(
                                            'conflict' => $path->conflict,
                                            'action'   => $path->action,
                                            'label'    => $label
                                        )
                                    );
                                }

                                $details->setProperty(
                                    'columns',
                                    array('label' => 'Widget', 'action' => 'Action')
                                );

                                return $details;
                            }
                    )
                );
            }
        );
    }

    /**
     * Explicitly add the views/scripts path because the widget
     * and region.phtml files aren't in conventional locations.
     */
    public static function load()
    {
        $view = Zend_Layout::getMvcInstance()->getView();
        $view->addScriptPath(dirname(__FILE__) . '/views/scripts/');
    }
}
