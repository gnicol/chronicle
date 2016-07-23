<?php
/**
 * Test our custom module router's functionality.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Controller_Router_Route_ModuleTest extends TestCase
{
    /**
     * Create an instance of P4CMS application sufficiently intialized
     * to exercise the router logic.
     */
    public function setUp()
    {
        parent::setUp();

        // setup dispatcher with controllers from test modules.
        $dispatcher = new Zend_Controller_Dispatcher_Standard;
        $dispatcher->setDefaultModule('core');
        $dispatcher->addControllerDirectory(
            TEST_ASSETS_PATH . '/core-modules/core/controllers',
            'core'
        );
        $dispatcher->addControllerDirectory(
            TEST_ASSETS_PATH . '/sites/test/modules/routing/controllers',
            'routing'
        );
        
        // ensure classes in routing module can be discovered.
        P4Cms_Loader::addPackagePath(
            'Routing', 
            TEST_ASSETS_PATH . '/sites/test/modules/routing'
        );        

        // setup a test router with the module route as the default.
        $this->_router = new P4Cms_Controller_Router_Rewrite;
        $this->_router->addRoute(
            'default',
            new P4Cms_Controller_Router_Route_Module(
                array(),
                $dispatcher,
                new Zend_Controller_Request_Http
            )
        );
    }

    /**
     * Test the behaviour of assemble().
     */
    public function testAssemble()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': empty',
                'data'      => array(),
                'expected'  => '/',
            ),
            array(
                'label'     => __LINE__ .': M',
                'data'      => array(
                    'module'        => 'routing',
                ),
                'expected'  => '/routing',
            ),
            array(
                'label'     => __LINE__ .': MC C=index',
                'data'      => array(
                    'module'        => 'routing',
                    'controller'    => 'index',
                ),
                'expected'  => '/routing',
            ),
            array(
                'label'     => __LINE__ .': MC C=test',
                'data'      => array(
                    'module'        => 'routing',
                    'controller'    => 'test',
                ),
                'expected'  => '/routing/test',
            ),
            array(
                'label'     => __LINE__ .': MCA C=index',
                'data'      => array(
                    'module'        => 'routing',
                    'controller'    => 'index',
                    'action'        => 'test',
                ),
                'expected'  => '/routing/index/test',
            ),
            array(
                'label'     => __LINE__ .': MCA C=test',
                'data'      => array(
                    'module'        => 'routing',
                    'controller'    => 'test',
                    'action'        => 'index',
                ),
                'expected'  => '/routing/test',
            ),
            array(
                'label'     => __LINE__ .': MCA C=index + param',
                'data'      => array(
                    'module'        => 'routing',
                    'controller'    => 'index',
                    'action'        => 'test',
                    'foo'           => 'bar',
                ),
                'expected'  => '/routing/index/test/foo/bar',
            ),
            array(
                'label'     => __LINE__ .': MCA C=test + param',
                'data'      => array(
                    'module'        => 'routing',
                    'controller'    => 'test',
                    'action'        => 'index',
                    'foo'           => 'bar',
                ),
                'expected'  => '/routing/test/foo/bar',
            ),
            array(
                'label'     => __LINE__ .': MCA C=index + slash param',
                'data'      => array(
                    'module'        => 'routing',
                    'controller'    => 'index',
                    'action'        => 'test',
                    'foo'           => 'b/a/r',
                ),
                'expected'  => '/routing/index/test?foo=b%2Fa%2Fr',
            ),
            array(
                'label'     => __LINE__ .': MCA C=test + slash param',
                'data'      => array(
                    'module'        => 'routing',
                    'controller'    => 'test',
                    'action'        => 'index',
                    'foo'           => 'b/a/r',
                ),
                'expected'  => '/routing/test?foo=b%2Fa%2Fr',
            ),
            array(
                'label'     => __LINE__ .': MCA C=test + slash param + param',
                'data'      => array(
                    'module'        => 'routing',
                    'controller'    => 'test',
                    'action'        => 'index',
                    'foo'           => 'b/a/r',
                    'baz'           => 'something',
                ),
                'expected'  => '/routing/test/baz/something?foo=b%2Fa%2Fr',
            ),
            array(
                'label'     => __LINE__ .': MCA C=index,A=index + param',
                'data'      => array(
                    'module'        => 'routing',
                    'controller'    => 'index',
                    'action'        => 'index',
                    'foo'           => 'bar',
                ),
                'expected'  => '/routing/foo/bar',
            ),
            array(
                'label'     => __LINE__ .': MCA C=index,A=index + test param',
                'data'      => array(
                    'module'        => 'routing',
                    'controller'    => 'index',
                    'action'        => 'index',
                    'test'           => 'priority',
                ),
                'expected'  => '/routing/index/index/test/priority',
            ),
            array(
                'label'     => __LINE__ .': MCA C=test,A=test + param',
                'data'      => array(
                    'module'        => 'routing',
                    'controller'    => 'test',
                    'action'        => 'test',
                    'test'           => 'priority',
                ),
                'expected'  => '/routing/test/test/test/priority',
            ),
            array(
                'label'     => __LINE__ .': MCA C=index,A=doesnotexist + param',
                'data'      => array(
                    'module'        => 'routing',
                    'controller'    => 'index',
                    'action'        => 'doesnotexist',
                    'foo'           => 'bar',
                    'unset'         => null,
                ),
                'expected'  => '/routing/doesnotexist/foo/bar',
            ),
            array(
                'label'     => __LINE__ .': MCA C=ctest,A=atest + id param',
                'data'      => array(
                    'module'        => 'routing',
                    'controller'    => 'ctest',
                    'action'        => 'atest',
                    'id'            => '.',
                ),
                'expected'  => '/routing/ctest/atest?id=.',
            ),
            array(
                'label'     => __LINE__ .': MCA C=ctest,A=atest + . param',
                'data'      => array(
                    'module'        => 'routing',
                    'controller'    => 'ctest',
                    'action'        => 'atest',
                    '.'             => 'dot',
                ),
                'expected'  => '/routing/ctest/atest?.=dot',
            ),
        );

        $router = $this->_router;
        foreach ($tests as $test) {
            $label = $test['label'];

            $actual = $router->assemble($test['data']);
            $this->assertEquals(
                $test['expected'],
                $actual,
                "$label - expected URI"
            );
        }
    }

    /**
     * Test assembly with existing params.
     */
    public function testAssembleWithExistingParams()
    {
        // setup existing params by matching a provided path
        $path = 'http://main.cms/routing/foo/bar/';
        $request = new Zend_Controller_Request_Http($path);
        $router = $this->_router;
        $actual = $router->route($request);

        $expected = array(
            'module'        => 'routing',
            'controller'    => 'index',
            'action'        => 'index',
            'foo'           => 'bar',
        );
        $this->assertSame(
            $expected,
            $actual->getParams(),
            'Expected match'
        );

        // now test assembling with a param set to null
        $expected['foo'] = null;
        $expectedPath = '/routing';
        $actual = $router->assemble($expected);
        $this->assertEquals(
            $expectedPath,
            $actual,
            'Expected URI'
        );
    }

    /**
     * Test the behaviour of match().
     */
    public function testMatch()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': empty',
                'path'      => 'http://main.cms/',
                'expected'  => array(
                    'module'        => 'core',
                    'controller'    => 'index',
                    'action'        => 'index',
                ),
            ),
            array(
                'label'     => __LINE__ .': M',
                'path'      => 'http://main.cms/routing',
                'expected'  => array(
                    'module'        => 'routing',
                    'controller'    => 'index',
                    'action'        => 'index',
                ),
            ),
            array(
                'label'     => __LINE__ .': MC C=index',
                'path'      => 'http://main.cms/routing/index',
                'expected'  => array(
                    'module'        => 'routing',
                    'controller'    => 'index',
                    'action'        => 'index',
                ),
            ),
            array(
                'label'     => __LINE__ .': MC C=test',
                'path'      => 'http://main.cms/routing/test',
                'expected'  => array(
                    'module'        => 'routing',
                    'controller'    => 'test',
                    'action'        => 'index',
                ),
            ),
            array(
                'label'     => __LINE__ .': MCA C=index,A=test',
                'path'      => 'http://main.cms/routing/index/test',
                'expected'  => array(
                    'module'        => 'routing',
                    'controller'    => 'index',
                    'action'        => 'test',
                ),
            ),
            array(
                'label'     => __LINE__ .': MCA C=index,A=doesnotexist',
                'path'      => 'http://main.cms/routing/index/doesnotexist',
                'expected'  => array(
                    'module'        => 'routing',
                    'controller'    => 'index',
                    'action'        => 'index',
                ),
            ),
            array(
                'label'     => __LINE__ .': MCA C=test',
                'path'      => 'http://main.cms/routing/test/index',
                'expected'  => array(
                    'module'        => 'routing',
                    'controller'    => 'test',
                    'action'        => 'index',
                ),
            ),
            array(
                'label'     => __LINE__ .': MCA C=,A=test',
                'path'      => 'http://main.cms/routing/test',
                'expected'  => array(
                    'module'        => 'routing',
                    'controller'    => 'test',
                    'action'        => 'index',
                ),
            ),
            array(
                'label'     => __LINE__ .': MCA C=,A=doesnotexist',
                'path'      => 'http://main.cms/routing/doesnotexist',
                'expected'  => array(
                    'module'        => 'routing',
                    'controller'    => 'index',
                    'action'        => 'index',
                ),
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];

            $request = new Zend_Controller_Request_Http($test['path']);
            $router = $this->_router;
            $actual = $router->route($request);
            $this->assertEquals(
                $test['expected'],
                $actual->getParams(),
                "$label - expected match"
            );
        }
        
        // test a bogus path.
        try {
            $path    = 'http://main.cms/bogus/maleficent/doesnotexist';
            $request = new Zend_Controller_Request_Http($path);
            $router->route($request);
            $this->fail("Expected exception");
        } catch (Zend_Controller_Router_Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Test the behaviour of formatRouteParam.
     */
    public function testFormatRouteParam()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': empty',
                'param'     => '',
                'expected'  => '',
            ),
            array(
                'label'     => __LINE__ .': string',
                'param'     => 'string',
                'expected'  => 'string',
            ),
            array(
                'label'     => __LINE__ .': camelCase',
                'param'     => 'camelCase',
                'expected'  => 'camel-case',
            ),
            array(
                'label'     => __LINE__ .': aLotOfCamelCase',
                'param'     => 'aLotOfCamelCase',
                'expected'  => 'a-lot-of-camel-case',
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];

            $actual = P4Cms_Controller_Router_Route_Module::formatRouteParam($test['param']);
            $this->assertSame(
                $test['expected'],
                $actual,
                "$label - expected route param"
            );
        }
    }
}
