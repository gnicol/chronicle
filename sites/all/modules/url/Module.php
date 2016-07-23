<?php
/**
 * Provides support for assigning custom urls to content entries.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Url_Module extends P4Cms_Module_Integration
{
    const   ROUTE   = 'custom-url';

    /**
     * Integrate url module with content records.
     */
    public static function init()
    {
        // participate in content editing - index urls in URL records.
        P4Cms_PubSub::subscribe('p4cms.content.record.postSave',
            function(P4Cms_Content $entry)
            {
                $url  = $entry->getValue('url');
                $path = isset($url['path']) ? $url['path'] : null;

                // early exit if we have an existing record and the path is good.
                // otherwise, remove existing record if path is out of date.
                try {
                    $url = Url_Model_Url::fetchByContent(
                        $entry, null, $entry->getAdapter()
                    );
                    if ($url->getPath() === $path) {
                        return;
                    } else {
                        $url->delete();
                    }
                } catch (P4Cms_Record_NotFoundException $e) {
                    // no existing record to remove.
                }

                // write new url record if we have a custom url path.
                if ($path) {
                    // re-verify path isn't taken (no guarantees we
                    // went through form verification to get here)
                    $params = Url_Model_Url::getContentRouteParams($entry);
                    if (!Url_Module::isPathRouted($path, $params)) {
                        $url = new Url_Model_Url;
                        $url->setAdapter($entry->getAdapter())
                            ->setParams($params)
                            ->setPath($path)
                            ->save();
                    }
                }
            }
        );

        // when content is deleted, delete associated url record if there is one.
        P4Cms_PubSub::subscribe('p4cms.content.record.delete',
            function(P4Cms_Content $entry)
            {
                try {
                    Url_Model_Url::fetchByContent(
                        $entry, null, $entry->getAdapter()
                    )->delete();
                } catch (P4Cms_Record_NotFoundException $e) {
                    // no existing record to remove.
                }
            }
        );
    }

    /**
     * Integrate url module with router and content authoring.
     */
    public static function load()
    {
        // register a custom route for handling custom urls.
        $front   = Zend_Controller_Front::getInstance();
        $router  = $front->getRouter();
        $request = $front->getRequest();
        $router->addRoute(Url_Module::ROUTE, new Url_Route, true);

        // register a controller plugin to handle redirecting
        // outdated custom-urls to more permanent locations.
        $front->registerPlugin(new Url_Redirector);

        // override content uri generation to look for custom paths.
        $original = P4Cms_Content::getUriCallback();
        P4Cms_Content::setUriCallback(
            function($content, $action, $params) use ($original, $request)
            {
                $url  = $content->getValue('url');
                $path = isset($url['path']) ? $url['path'] : null;

                if ($path) {
                    $url = $request->getBranchBaseUrl() . '/' . $path;

                    // for action other than 'view', copy action into params
                    if ($action !== 'view') {
                        $params['action'] = $action;
                    }

                    if ($params) {
                        $url .= '?' . http_build_query($params);
                    }

                    return $url;
                }

                return $original($content, $action, $params);
            }
        );

        // add custom url form to content authoring interface.
        P4Cms_PubSub::subscribe(
            'p4cms.content.form.subForms',
            function(Content_Form_Content $form)
            {
                return new Url_Form_Content(
                    array(
                        'name'      => 'url',
                        'idPrefix'  => $form->getIdPrefix(),
                        'order'     => -50
                    )
                );
            }
        );

        // connect to content form validation to ensure custom urls are unique.
        P4Cms_PubSub::subscribe('p4cms.content.form.validate',
            function(Content_Form_Content $form, array $values)
            {
                // nothing to do if no url sub-form or entry.
                $entry   = $form->getEntry();
                $urlForm = $form->getSubForm('url');
                if (!$urlForm || !$entry) {
                    return true;
                }

                $path   = $urlForm->getValue('path');
                $ignore = $entry->getId() ? Url_Model_Url::getContentRouteParams($entry) : null;

                // before url paths are validated, try to make them unique
                // if the url path was auto-generated (by appending a number)
                if ($path && $urlForm->getValue('auto') === 'true') {
                    $path = Url_Module::makePathUnique($path, $ignore);
                    $urlForm->setDefault('path', $path);
                }

                // if a custom url is specified, verify it is not already in use
                // (check against application routes for a match).
                if ($path && Url_Module::isPathRouted($path, $ignore)) {
                    $urlForm->getElement('path')->addError(
                        "'" . $path . "' is already in use. Please choose a unique url path."
                    );
                    return false;
                }

                return true;
            }
        );

        // add a view script convention for urls.
        P4Cms_PubSub::subscribe('p4cms.content.view.scripts',
            function(array $scripts, P4Cms_Content $entry)
            {
                $url    = $entry->getValue('url');
                $filter = new P4Cms_Filter_TitleToId;
                if (isset($url['path'])) {
                    // place second (assumes that entry-id convention comes first).
                    array_splice($scripts, 1, 0, 'index/view-url-'. $filter->filter($url['path']));
                }

                return $scripts;
            }
        );

        // group urls under published/unpublished content when pulling changes.
        P4Cms_PubSub::subscribe(
            'p4cms.site.branch.pull.groupPaths',
            function($paths, $source, $target, $result)
            {
                // attempt to find content published and unpublished sub-groups.
                $content     = $paths->getSubGroup('Content');
                $published   = $content ? $content->getSubGroup('Published Entries')   : false;
                $unpublished = $content ? $content->getSubGroup('Unpublished Entries') : false;
                if (!$published || !$unpublished) {
                    return;
                }

                // locate any url paths
                $urlPaths = $paths->getPaths();
                $urlPaths = $urlPaths->filter(
                    'depotFile',
                    $target->getId() . '/urls/',
                    array($urlPaths::FILTER_COPY, $urlPaths::FILTER_STARTS_WITH)
                );

                // nothing to do if no affected paths
                if (!$urlPaths->count()) {
                    return;
                }

                // split urls into by-path entries and by-param entries indexed by id.
                $adapter       = $target->getStorageAdapter();
                $urlPathsById  = array();
                $urlParamsById = array();
                foreach ($urlPaths as $path) {
                    try {
                        $id = Url_Model_Url::depotFileToId($path->depotFile, $adapter);
                        $urlPathsById[$id] = $path;
                    } catch (P4Cms_Record_Exception $e) {
                        $id = basename($path->depotFile);
                        $urlParamsById[$id] = $path;
                    }
                }

                // organize the published/unpublished content by id for easier lookup
                $publishedById   = Site_Model_PullPathGroup::pathsByRecordId(
                    $published->getPaths(), 'P4Cms_Content', $adapter
                );
                $unpublishedById = Site_Model_PullPathGroup::pathsByRecordId(
                    $unpublished->getPaths(), 'P4Cms_Content', $adapter
                );

                // at this point we want to start associating url paths with
                // content paths, but to do so we need full url records so we
                // can see which content entry each url path points to.
                $urlRecords = Site_Model_PullPathGroup::fetchRecords(
                    array_keys($urlPathsById), 'Url_Model_Url', $source, $target
                );

                // now it is time to examine each url and move it to the published
                // content group if it is associated with a published content entry.
                // remaining records will be moved into the unpublished group after.
                // additionally, if the url record is in conflict, we flag the
                // associated content entry as being in conflict (if we can find it).
                foreach ($urlRecords as $urlRecord) {
                    $contentId = $urlRecord->getValue('id');
                    $urlPath   = $urlPathsById[$urlRecord->getId()];
                    $conflict  = $urlPath->conflict;

                    // find this url record's associated params path if we can.
                    // if the param path is in conflict, url is in conflict
                    $urlParams   = null;
                    $urlParamsId = basename(Url_Model_Url::makeParamId($urlRecord->getValues()));
                    if (isset($urlParamsById[$urlParamsId])) {
                        $urlParams = $urlParamsById[$urlParamsId];
                        $conflict  = $conflict || $urlParams->conflict;
                    }

                    // move to published and flag conflicts as appropriate.
                    if (isset($publishedById[$contentId])) {
                        $published->inheritPaths($urlPath);
                        if ($urlParams) {
                            $published->inheritPaths($urlParams);
                        }
                        if ($conflict) {
                            $publishedById[$contentId]->conflict = true;
                        }
                    } else if (isset($unpublishedById[$contentId]) && $conflict) {
                        $unpublishedById[$contentId]->conflict = true;
                    }
                }

                // move any remaining url paths into the 'unpublished content' group
                $remaining = $paths->getPaths();
                $remaining = $remaining->filter(
                    'depotFile',
                    $target->getId() . '/urls/',
                    array($remaining::FILTER_COPY, $remaining::FILTER_STARTS_WITH)
                );
                $unpublished->inheritPaths(
                    $remaining->invoke('getValue', array('depotFile'))
                );

                // add a custom callback so our url entries don't show in the
                // count displayed to users. our associated entries count for us.
                $countCallback = function($group, $count, $options) use ($target)
                {
                    // exclude the 'urls' entries from count
                    return $group->getPaths($options)->filter(
                        'depotFile',
                        $target->getId() . '/urls/',
                        array(
                            P4Cms_Model_Iterator::FILTER_COPY,
                            P4Cms_Model_Iterator::FILTER_STARTS_WITH,
                            P4Cms_Model_Iterator::FILTER_INVERSE
                        )
                    )->count();
                };
                $published->setCount($countCallback);
                $unpublished->setCount($countCallback);
            }
        );

        // resolve url conflicts - these can occur if the same url path is
        // used for different route params (e.g. different content entries)
        // in separate branches - we need resolve these manually because urls
        // are indexed by-path and by-param and these would fall out of sync.
        // additionally, for content records, each entry has a url attribute
        // which would become innaccurate.
        P4Cms_PubSub::subscribe(
            'p4cms.site.branch.pull.conflicts',
            function($conflicts, $target, $source, $headChange, $preview, $adapter)
            {
                // filter conflicts for url path entries and convert
                // depot paths to record ids so we can query records.
                $sourceIds = array();
                $targetIds = array();
                $basePath  = $target->getId() . '/urls/by-path/';
                foreach ($conflicts->getData() as $conflict) {
                    if (isset($conflict['depotFile'])
                        && strpos($conflict['depotFile'], $basePath) === 0
                    ) {
                        $id          = Url_Model_Url::depotFileToId($conflict['depotFile'], $adapter);
                        $sourceIds[] = $id . "@" . $headChange;
                        $targetIds[] = $id;
                    }
                }

                // if there are no url path conflicts, nothing to do.
                if (!$sourceIds || !$targetIds) {
                    return;
                }

                // we use the given adapter for target rather than calling
                // target->getStorageAdapter() to ensure we are using the
                // proper user and workspace.
                $targetAdapter = $adapter;
                $sourceAdapter = $source->getStorageAdapter();

                // fetch all conflicting urls in both the source and target
                // branches, so that we can check if the params differ.
                $options    = array('includeDeleted' => true);
                $targetUrls = Url_Model_Url::fetchAll(array('ids' => $targetIds) + $options, $targetAdapter);
                $sourceUrls = Url_Model_Url::fetchAll(array('ids' => $sourceIds) + $options, $sourceAdapter);

                // examine each url path conflict and determine if the params
                // differ, if they do we need to remove the orphaned by-params
                // record and (if it is a content url) clear out the url on
                // the associated content record.
                foreach ($targetUrls as $id => $targetUrl) {
                    // if params are identical, no problem.
                    if (!isset($sourceUrls[$id])
                        || $sourceUrls[$id]->getParams() == $targetUrl->getParams()
                    ) {
                        continue;
                    }

                    // params differ, clean-up the dangling param record
                    $targetUrl->getParamRecord()->delete();

                    // if this is for a content entry, clear its url attribute
                    // we disable pub/sub for the save as it can have negative
                    // side-effects, in particular the url path record we are
                    // merging gets deleted due to our postSave callback above
                    $contentId = $targetUrl->getValue('id');
                    if ($targetUrl->getParams() == $targetUrl->getContentRouteParams($contentId)) {
                        $content = P4Cms_Content::fetch($contentId, null, $adapter);
                        $content->setValue('url', null)->save(
                            null, array(P4Cms_Content::SAVE_SKIP_PUBSUB)
                        );
                    }
                }
            }
        );
    }

    /**
     * Check if the given path matches an application route.
     *
     * If the optional ignore params array is given, ignores
     * any route match that produces the same set of params.
     * This is useful because it is typically ok if the path
     * already routes to the same set of params.
     *
     * @param   string      $path       the path to check for a route match
     * @param   array|null  $ignore     optional set of params to ignore matches for.
     * @return  Zend_Controller_Router_Route_Interface|false    a matching route or false if no match
     */
    public static function isPathRouted($path, array $ignore = null)
    {
        $front  = Zend_Controller_Front::getInstance();
        $router = $front->getRouter();
        $routes = $router->getRoutes();
        foreach (array_reverse($routes) as $route) {
            $route = clone $route;

            // instruct the custom url route to exclude deleted url entries
            // (normally it honors deleted entries, but we want to recycle them)
            if ($route instanceof Url_Route) {
                $route->setMatchDeleted(false);
            }

            // route interface is inconsistent, some routes match on string path
            // others match on a request object - call match accordingly.
            $request = new Zend_Controller_Request_Http;
            $request->setPathInfo($path);
            $input = method_exists($route, 'getVersion') && $route->getVersion() > 1
                ? $request
                : $path;
            $match = $route->match($input);
            if ($match && $match != $ignore) {
                return $route;
            } else if ($match) {
                return false;
            }
        }

        return false;
    }

    /**
     * Attempt to make the given path unique by appending '-2', '-3', ...
     * It is not always possible to make a path unique by appending
     * a number so no guarantees that the path is actually made unique.
     *
     * @param   string          $path       the path to make unique
     * @param   array|null      $ignore     optional set of params to ignore conflicts for
     * @param   string|null     $original   used when called recursively to preserve the original path
     * @param   int|null        $attempts   used when called recursively to track and limit attempts
     * @return  string          the input path, possibly with a number appended.
     */
    public static function makePathUnique($path, array $ignore = null, $original = null, $attempts = 0)
    {
        $increment = 1;
        $original  = $original ?: $path;

        // if unique (not routed), return as-is
        $route = static::isPathRouted($path, $ignore);
        if (!$route) {
            return $path;
        }

        // we'll only try twice to make path unique.
        if ($attempts >= 2) {
            return $original;
        }

        // capture and strip any existing trailing '-' or '-1', '-2', ...
        $count = intval(substr($path, (strrpos($path, '-') + 1)));
        $path  = rtrim(preg_replace('/-[0-9]+$/', '', $path), '-');

        // if not unique due to custom url route collision,
        // find next highest number to append to make it unique.
        if ($route instanceof Url_Route) {

            // query for all urls with this path ending in '-1', '-2', ...
            // we have to hex-encode the characters we are looking for
            // because the url model encodes its ids.
            $startsWith = bin2hex($path . '-');
            $endsWith   = '(3[0-9])+$'; // hex-encoded equivalent of [0-9]+$
            $query      = new P4Cms_Record_Query;
            $filter     = new P4Cms_Record_Filter;
            $filter->addFstat('depotFile', $startsWith . $endsWith, $filter::COMPARE_REGEX);
            $query->setPaths(array($startsWith . '*'))
                  ->setFilter($filter);

            // get the matching urls and sort them so that
            // the highest numbered entry is last.
            $urls = Url_Model_Url::fetchAll($query);
            $urls->sortByCallback(
                function($a, $b)
                {
                    return strnatcasecmp($a->getId(), $b->getId());
                }
            );

            $count = 0;
            if ($urls->count()) {
                // check if this path/param combination has already been assigned a number
                foreach ($urls as $url) {
                    if ($url->getParams() == $ignore) {
                        $count     = $url->getPath();
                        $increment = 0;
                        break;
                    }
                }

                $count = $count ?: $urls->last()->getPath();
                $count = intval(substr($count, (strrpos($count, '-') + 1)));
            }
        }

        // if not unique due to some other route collision, try
        // adding a higher number - that could still collide with
        // a route, so we'll make one (and only one) more pass.
        $path .= '-' . ($count ? $count + $increment : 2);
        return static::makePathUnique($path, $ignore, $original, ($attempts + 1));
    }
}