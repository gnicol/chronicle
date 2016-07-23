<?php
/**
 * Test the dynamic menu handler facility.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Navigation_DynamicHandlerTest extends TestCase
{
    /**
     * Extend setUp to clear out any handlers our tests add
     */
    public function setUp()
    {
        P4Cms_PubSub::clearHandles('p4cms.navigation.dynamicHandlers');
        parent::setUp();
    }

    /**
     * Test calling fetch with a bad id
     */
    public function testBadFetch()
    {
        $handler = new P4Cms_Navigation_DynamicHandler;

        try {
            $handler->fetch('badId');
            $this->fail('Expected exception to occur');
        } catch (P4Cms_Model_NotFoundException $e) {
            $this->assertSame(
                "Cannot fetch handler. The requested handler does not exist.",
                $e->getMessage(),
                'Expected matching exception message'
            );
        }
    }

    /**
     * Test calling fetch with a known good id
     */
    public function testGoodFetch()
    {
        $this->assertSame(
            0,
            count(P4Cms_Navigation_DynamicHandler::fetchAll()),
            'Expected no dynamic handlers at start'
        );

        // Join pub/sub so we will be seen
        P4Cms_PubSub::subscribe('p4cms.navigation.dynamicHandlers',
            function()
            {
                $handler = new P4Cms_Navigation_DynamicHandler;
                $handler->setId('test')
                        ->setLabel('Test')
                        ->setExpansionCallback(
                            function()
                            {
                            }
                        );

                return array($handler);
            }
        );

        $this->assertSame(
            1,
            count(P4Cms_Navigation_DynamicHandler::fetchAll()),
            'Expected one handler post add'
        );
    }

    /**
     * Verify multiple handlers with the same ID crush
     */
    public function testGoodFetchClobbering()
    {
        $this->assertSame(
            0,
            count(P4Cms_Navigation_DynamicHandler::fetchAll()),
            'Expected no dynamic handlers at start'
        );

        // Join pub/sub so we will be seen
        P4Cms_PubSub::subscribe('p4cms.navigation.dynamicHandlers',
            function()
            {
                $handler = new P4Cms_Navigation_DynamicHandler;
                $handler->setId('test')
                        ->setLabel('Test')
                        ->setExpansionCallback(
                            function()
                            {
                            }
                        );

                $handler2 = new P4Cms_Navigation_DynamicHandler;
                $handler2->setId('test')
                         ->setLabel('Test')
                         ->setExpansionCallback(
                            function()
                            {
                            }
                         );
                return array($handler, $handler2);
            }
        );
        // Join pub/sub so we will be seen
        P4Cms_PubSub::subscribe('p4cms.navigation.dynamicHandlers',
            function()
            {
                $handler = new P4Cms_Navigation_DynamicHandler;
                $handler->setId('test')
                        ->setLabel('Test')
                        ->setExpansionCallback(
                            function()
                            {
                            }
                        );

                return array($handler);
            }
        );
        
        $this->assertSame(
            1,
            count(P4Cms_Navigation_DynamicHandler::fetchAll()),
            'Expected one handler post add of conflicting handlers'
        );
    }

    /**
     * Test using (get|call)ExpansionCallback when none is set
     */
    public function testBadGetCallExpansionCallback()
    {
        $handler = new P4Cms_Navigation_DynamicHandler;
        $handler->setId('test')
                ->setLabel('Test');

        try {
            $handler->getExpansionCallback();
            $this->fail('Expected exception on get');
        } catch (P4Cms_Navigation_Exception $e) {
            $this->assertSame(
                'Cannot get expansion callback, no valid callback has been set',
                $e->getMessage(),
                'Expected matching exception message'
            );
        }

        try {
            $item = new P4Cms_Navigation_Page_Dynamic();
            $handler->callExpansionCallback($item, array());
            $this->fail('Expected exception on call');
        } catch (P4Cms_Navigation_Exception $e) {
            $this->assertSame(
                'Cannot get expansion callback, no valid callback has been set',
                $e->getMessage(),
                'Expected matching exception message'
            );
        }
    }

    /**
     * Test setting the expansion callback to bad values
     */
    public function testBadSetExpansionCallback()
    {
        $tests = array(
            array(
                'title' => __LINE__ . ' null',
                'value' => null
            ),
            array(
                'title' => __LINE__ . ' array',
                'value' => array('test')
            ),
            array(
                'title' => __LINE__ . ' int',
                'value' => 10
            ),
            array(
                'title' => __LINE__ . ' string',
                'value' => "test"
            ),
            array(
                'title' => __LINE__ . ' private function',
                'value' => array('P4Cms_DynamicHandler_MenuTest', '_testFunction')
            ),

        );

        foreach ($tests as $test) {
            try {
                $handler = new P4Cms_Navigation_DynamicHandler;
                $handler->setExpansionCallback($test['value']);
                $this->fail('Expected Exception for test '. $test['title']);
            } catch (P4Cms_Navigation_Exception $e) {
                $this->assertSame(
                    'Cannot set expansion callback, passed value is not callable',
                    $e->getMessage(),
                    'Expected matching message for test '. $test['title']
                );
            }
        }
    }

    /**
     * An empty, protected, function used by testBadSetExceptionCallback
     */
    protected static function _testFunction()
    {
    }
}
