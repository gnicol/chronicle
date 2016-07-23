<?php
/**
 * Test workflow conditions infrastructure.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_Test_ConditionsTest extends ModuleTest
{
    /**
     * Exercise the condition loader.
     */
    public function testConditionLoader()
    {
        $loader = Workflow_Module::getPluginLoader('condition');

        $this->assertTrue($loader instanceof Zend_Loader_PluginLoader);
        $this->assertTrue(count($loader->getPaths()) > 0);

        // ensure loader is made, but once.
        $this->assertSame(
            spl_object_hash($loader),
            spl_object_hash(Workflow_Module::getPluginLoader('condition'))
        );

        // ensure we can clear it to regenerate loader.
        Workflow_Module::clearPluginLoaders();
        $this->assertNotSame(
            spl_object_hash($loader),
            spl_object_hash(Workflow_Module::getPluginLoader('condition'))
        );

        // ensure we can resolve the class name of our test condition.
        $this->assertSame(
            'Workflow_Workflow_Condition_False',
            $loader->load('false')
        );
    }

    /**
     * Test (set/get)Options() methods.
     */
    public function testConditionSetGetOptions()
    {
        $class = Workflow_Module::getPluginLoader('condition')->load('false');

        $condition = new $class;
        $this->assertSame(
            array(),
            $condition->getOptions(),
            "Expected empty condition options by default."
        );

        // pass options via constructor
        $options   = array('opt1' => 'a', 'opt2' => array(1, 2, 3), 'opt3' => false);
        $condition = new $class($options);
        $this->assertSame(
            $options,
            $condition->getOptions(),
            "Expected condition options #1."
        );

        // pass options via setOptions
        $condition = new $class;
        $condition->setOptions($options);
        $this->assertSame(
            $options,
            $condition->getOptions(),
            "Expected condition options #2."
        );
    }

    /**
     * Test isNegated() method.
     */
    public function testConditionIsNegated()
    {
        $class = Workflow_Module::getPluginLoader('condition')->load('false');

        $condition = new $class;
        $this->assertFalse(
            $condition->isNegated(),
            "Expected condition is not negated."
        );

        $condition->setOptions(array(Workflow_ConditionAbstract::OPTION_NEGATE => true));
        $this->assertTrue(
            $condition->isNegated(),
            "Expected condition is negated."
        );
    }

    /**
     * Test evaluate() method.
     */
    public function testConditionEvaluate()
    {
        $class = Workflow_Module::getPluginLoader('condition')->load('false');

        $condition = new $class;
        $this->assertFalse(
            $condition->evaluate(new Workflow_Model_Transition, new P4Cms_Content),
            "Expected evaluate to false."
        );

        $condition = new $class(array('negate' => true));
        $this->assertTrue(
            $condition->evaluate(new Workflow_Model_Transition, new P4Cms_Content),
            "Expected evaluate to true if condition is negated."
        );
    }
}
