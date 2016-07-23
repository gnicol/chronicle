<?php
/**
 * Integrate the UI module with the rest of the system.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Ui_Module extends P4Cms_Module_Integration
{
    /**
     * Subscribe to the relevant topics.
     */
    public static function init()
    {
        // provide dynamic menu items
        P4Cms_PubSub::subscribe('p4cms.navigation.dynamicHandlers',
            function()
            {
                // expand the help link to use the current help URL
                $handler = new P4Cms_Navigation_DynamicHandler;
                $handler->setId('ui.help-link')
                        ->setLabel('Help Link')
                        ->setExpansionCallback(
                            function($item, $options)
                            {
                                $helper = Zend_Controller_Action_HelperBroker::getStaticHelper('helpUrl');
                                $uri    = $helper ? $helper->getUrl() : null;

                                if ($uri) {
                                    return array(
                                        array(
                                            'label' => 'Help',
                                            'uri'   => $uri,
                                            'target' => '_blank'
                                        )
                                    );
                                }

                                return array();
                            }
                        );

                return array($handler);
            }
        );

        // provide 'baseUrl' macro.
        P4Cms_PubSub::subscribe('p4cms.macro.baseUrl',
            function($params, $body, $context)
            {
                $baseUrl = '';
                $request = Zend_Controller_front::getInstance()->getRequest();
                if ($request instanceof Zend_Controller_Request_Http) {
                    $baseUrl = $request->getBaseUrl();
                }

                return $baseUrl;
            }
        );

        // provide 'version' macro.
        P4Cms_PubSub::subscribe('p4cms.macro.version',
            function($params, $body, $context)
            {
                $field = isset($params[0]) ? $params[0] : '';

                switch ($field) {
                    case '':
                        return P4CMS_VERSION;
                        break;
                    case 'release':
                        return P4CMS_VERSION_RELEASE;
                        break;
                    case 'patch':
                        return P4CMS_VERSION_PATCHLEVEL;
                        break;
                    case 'date':
                        return P4CMS_VERSION_SUPPDATE;
                        break;
                    default:
                        return null;
                }
            }
        );
    }

    /**
     * Perform integration operations when the site is loaded.
     */
    public static function load()
    {
        // register routes with js router.
        $view     = Zend_Layout::getMvcInstance()->getView();
        $routes   = Zend_Controller_Front::getInstance()->getRouter()->getRoutes();
        $jsRoutes = array();
        foreach ($routes as $name => $route) {
            if ($route instanceof Zend_Controller_Router_Route_Regex) {
                $properties = P4Cms_Controller_Router_Route_Regex::toArray($route);
                $jsRoutes[$name] = array(
                    'type'      => 'p4cms.ui.router.route.Regex',
                    'defaults'  => $properties['defaults'],
                    'reverse'   => $properties['reverse'],
                    'map'       => $properties['map']
                );
            }

            if ($route instanceof Zend_Controller_Router_Route_Module) {
                $jsRoutes[$name] = array(
                    'type'      => 'p4cms.ui.router.route.Module',
                    'defaults'  => $route->getDefaults()
                );
            }
        }

        // expose route and server time details to client side javascript
        $request    = Zend_Controller_Front::getInstance()->getRequest();
        $baseUrl    = $request ? $request->getBaseUrl() : '';
        $script     = "p4cms.ui.Router.addRoutes(" . Zend_Json::encode($jsRoutes) . ");\n "
                    . "dojo.setObject('p4cms.ui.serverTimeOffset', (new Date().getTime()/1000) - " . time() . ");\n ";

        // include the CSRF token if one is in use
        $token = P4Cms_Form::getCsrfToken();
        if ($token) {
            $script .= "dojo.setObject('p4cms.ui.csrfToken', '" . $token . "');\n ";
        }

        $view->headScript()->appendScript($script);

        // append default meta tags to the view
        $view->headMeta()->appendHttpEquiv('Content-Type', 'text/html; charset=UTF-8');

        // ensure that the Dojo stylesheets we depend on come first, so we can override
        // them as required. Note that these stylesheets are prepended, so the array
        // is specified in reverse order.
        $baseUrl = P4Cms_Module::fetch('dojo')->getBaseUrl();
        $dojoCss = array(
            'dojox/grid/enhanced/resources/claro/EnhancedGrid.css',
            'dijit/themes/claro/claro.css',
            'dijit/themes/claro/document.css'
        );
        foreach ($dojoCss as $css) {
            $view->headLink()->prependStylesheet(
                "$baseUrl/$css",
                'all',
                false,
                array('buildGroup' => 'packages')
            );
        }
    }
}
