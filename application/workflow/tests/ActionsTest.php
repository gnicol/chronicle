<?php
/**
 * Test workflow actions infrastructure.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_Test_ActionsTest extends ModuleTest
{
    /**
     * Exercise the action loader.
     */
    public function testActionLoader()
    {
        $loader = Workflow_Module::getPluginLoader('action');
        
        $this->assertTrue($loader instanceof Zend_Loader_PluginLoader);
        $this->assertTrue(count($loader->getPaths()) > 0);

        // ensure loader is made, but once.
        $this->assertSame(
            spl_object_hash($loader),
            spl_object_hash(Workflow_Module::getPluginLoader('action'))
        );

        // ensure we can clear it to regenerate loader.
        Workflow_Module::clearPluginLoaders();
        $this->assertNotSame(
            spl_object_hash($loader),
            spl_object_hash(Workflow_Module::getPluginLoader('action'))
        );

        // ensure we can resolve the class name of our test action.
        $this->assertSame(
            'Workflow_Workflow_Action_Noop',
            $loader->load('noop')
        );
    }

    /**
     * Test (set/get)Options() methods.
     */
    public function testActionSetGetOptions()
    {
        $class = Workflow_Module::getPluginLoader('action')->load('noop');

        $action = new $class;
        $this->assertSame(
            array(),
            $action->getOptions(),
            "Expected empty action options by default."
        );

        // pass options via constructor
        $options = array('opt1' => 'a', 'opt2' => array(1, 2, 3), 'opt3' => false);
        $action  = new $class($options);
        $this->assertSame(
            $options,
            $action->getOptions(),
            "Expected action options #1."
        );

        // pass options via setOptions
        $action = new $class;
        $action->setOptions($options);
        $this->assertSame(
            $options,
            $action->getOptions(),
            "Expected action options #2."
        );
    }

    /**
     * Test getOption() method.
     */
    public function testActionGetOption()
    {
        $class = Workflow_Module::getPluginLoader('action')->load('noop');

        // pass options via constructor
        $options = array('opt1' => 'a', 'opt2' => array(1, 2, 3), 'opt3' => false);
        $action  = new $class($options);
        $this->assertSame(
            false,
            $action->getOption('opt3'),
            "Expected action options #1."
        );
        $this->assertSame(
            'a',
            $action->getOption('opt1'),
            "Expected action options #2."
        );
        $this->assertSame(
            null,
            $action->getOption('unknown'),
            "Expected action options #3."
        );
    }

    /**
     * Test invoke() method.
     */
    public function testActionInvoke()
    {
        $class = Workflow_Module::getPluginLoader('action')->load('noop');

        $action = new $class;
        $this->assertSame(
            $action,
            $action->invoke(new Workflow_Model_Transition, new P4Cms_Content)
        );
    }   
}
