<?php
/**
 * Test our custom regex router's functionality.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Controller_Router_Route_RegexTest extends TestCase
{
    /**
     * Create an instance of P4CMS application sufficiently intialized
     * to exercise the router logic.
     */
    public function setUp()
    {
        parent::setUp();

        // setup a test router with the module route as the default.
        $this->_router = new P4Cms_Controller_Router_Rewrite;
        $this->_router->addRoute(
            'default',
            new P4Cms_Controller_Router_Route_Regex(
                'm/(c|)/(a|)',
                array(
                    'controller'    => '',
                    'action'        => '',
                    'param'         => 'p'
                ),
                array(
                    'controller'    => 1,
                    'action'        => 2
                ),
                'm/%s-%s'
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
                'expected'  => '/m/-',
            ),
            array(
                'label'     => __LINE__ .': known param',
                'data'      => array(
                    'param'  => 'foo'
                ),
                'expected'  => '/m/-',
            ),
            array(
                'label'     => __LINE__ .': unknown param',
                'data'      => array(
                    'test'  => 'p'
                ),
                'expected'  => '/m/-?test=p',
            ),
            array(
                'label'     => __LINE__ .': mixed params',
                'data'      => array(
                    'params'    => 'foo',
                    'param'     => 'bar'
                ),
                'expected'  => '/m/-?params=foo',
            ),
            array(
                'label'     => __LINE__ .': mixed params',
                'data'      => array(
                    'action'        => 'a',
                    'param'         => 'foo',
                    'test'          => 'bar'
                ),
                'expected'  => '/m/-a?test=bar',
            ),
            array(
                'label'     => __LINE__ .': mixed params',
                'data'      => array(
                    'action'        => 'a',
                    'controller'    => 'b',
                    'param'         => 'foo',
                    'test'          => 'bar'
                ),
                'expected'  => '/m/b-a?test=bar',
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
}