<?php
/**
 * Test the menu model.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Controller_Action_Helper_WidgetContextTest extends TestCase
{
    /**
     * Container for the Zend_Session
     *
     * @var mixed
     */
    private static $_session;

    /**
     * Setup
     */
    public function setUp()
    {
        parent::setUp();

        $widgetContext = new P4Cms_Controller_Action_Helper_WidgetContext;
        $widgetContext->clearContext();
    }

    /**
     * Test get and set context values.
     */
    public function testGetSet()
    {
        // test initial disposition of the context
        $widgetContext = new P4Cms_Controller_Action_Helper_WidgetContext;
        $actual = $widgetContext->getValues();
        $this->assertSame(array(), $actual, 'Expected initial context');

        // set some values in the context
        $widgetContext->setValue('foo', 'bar')
                      ->setValue('test', 'result');
        $actual = $widgetContext->getValues();
        $this->assertSame(
            array(
                'foo'   => 'bar',
                'test'  => 'result',
            ),
            $actual,
            'Expected context after setValue'
        );

        // test setting a null value
        $widgetContext->setValue('test');
        $actual = $widgetContext->getValues();
        $this->assertSame(
            array(
                'foo'   => 'bar',
            ),
            $actual,
            'Expected context after setValue with null'
        );

        // test setting multiple values
        $widgetContext->setValues(array('a' => 1, 'b' => array('one', 'two'), 'c' => 'three', 'd' => 'poof'))
                      ->setValues(array('a' => 2, 'd' => null));
        $actual = $widgetContext->getValues();
        $this->assertSame(
            array(
                'foo'   => 'bar',
                'a'     => 2,
                'b'     => array('one', 'two'),
                'c'     => 'three',
            ),
            $actual,
            'Expected context after setValues'
        );

        // test retrieving a specific value
        $actual = $widgetContext->getValues('b');
        $this->assertSame(
            array('one', 'two'),
            $actual,
            'Expected context value for b'
        );

        // test clearing the context
        $widgetContext->clearContext();
        $actual = $widgetContext->getValues();
        $this->assertSame(array(), $actual, 'Expected context after clear');
    }

    /**
     * Test behaviour of getEncodedValues
     */
    public function testGetEncodedValues()
    {
        // setup some values in the context
        $widgetContext = new P4Cms_Controller_Action_Helper_WidgetContext;
        $actual = $widgetContext->setValue('foo', 'bar')->setValue('test', 'result')->getValues();
        $this->assertSame(
            array(
                'foo'   => 'bar',
                'test'  => 'result',
            ),
            $actual,
            'Expected context after setValue'
        );

        // now get the encoded values
        $encoded = $widgetContext->getEncodedValues();
        $this->assertEquals(
            Zend_Json::encode($actual),
            $encoded,
            "Expected encoded values"
        );
    }

    /**
     * Test behvaiour of setEncodedValues
     */
    public function testSetEncodedValues()
    {
        // prime the context with a value
        $widgetContext = new P4Cms_Controller_Action_Helper_WidgetContext;
        $widgetContext->setValue('in-the-way', 'should not be removed');

        // set some encoded values
        $values = array(
            'foo'   => 'bar',
            'test'  => 'result',
        );
        $widgetContext->setEncodedValues(Zend_Json::encode($values));

        // check the context
        $values['in-the-way'] = 'should not be removed';
        $actual = $widgetContext->getValues();
        $this->assertEquals(
            $values,
            $actual,
            'Expected context values after setting encoded values'
        );
    }
}
