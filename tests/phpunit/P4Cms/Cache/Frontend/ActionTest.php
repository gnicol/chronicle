<?php
/**
 * Test methods for action cache frontent class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Cache_Frontend_ActionTest extends TestCase
{
    /**
     * Test the ignored session variable accessor/mutators
     */
    public function testIgnoredSessionVariables()
    {
        $cache = new P4Cms_Cache_Frontend_Action;

        $cache->setIgnoredSessionVariables(array());

        $this->assertSame(
            array(),
            $cache->getIgnoredSessionVariables(),
            'Expected matching state after set to empty'
        );

        $cache->addIgnoredSessionVariable('test')
              ->addIgnoredSessionVariable('test2')
              ->addIgnoredSessionVariable('test');

        $this->assertSame(
            array('test', 'test2'),
            $cache->getIgnoredSessionVariables(),
            'Expected matching values after adds'
        );

        $cache->setIgnoredSessionVariables(array('test3', 'test4'));

        $this->assertSame(
            array('test3', 'test4'),
            $cache->getIgnoredSessionVariables(),
            'Expected matching values after set'
        );
    }

    /**
     * Verifies we get an exception for bad input on session variable
     *
     * @expectedException InvalidArgumentException
     */
    public function testAddIgnoredSessionVariablesBadInput()
    {
        $cache = new P4Cms_Cache_Frontend_Action;
        $cache->addIgnoredSessionVariable('Bad$Symbol');
    }

    /**
     * Verifies we get an exception for bad input on session variable
     *
     * @expectedException InvalidArgumentException
     */
    public function testSetIgnoredSessionVariablesBadInput()
    {
        $cache = new P4Cms_Cache_Frontend_Action;
        $cache->setIgnoredSessionVariables(array('Bad$Symbol'));
    }

    /**
     * Test the tags accessor/mutators
     */
    public function testTags()
    {
        $cache = new P4Cms_Cache_Frontend_Action;

        $this->assertSame(
            array(),
            $cache->getTags(),
            'Expected matching start state'
        );

        $cache->addTag('test')
              ->addTags(array('test2', 'test'));

        $this->assertSame(
            array('test', 'test2'),
            $cache->getTags(),
            'Expected matching values after adds'
        );
    }

    /**
     * Test the base url accessor/mutator
     */
    public function testBaseUrl()
    {
        $cache = new P4Cms_Cache_Frontend_Action;
        
        $this->assertSame(
            null,
            $cache->getBaseUrl(),
            'Expected matching start state'
        );

        $cache->setBaseUrl('foo/');
        $this->assertSame(
            'foo/',
            $cache->getBaseUrl(),
            'Expected matching value after set'
        );

        $cache->setBaseUrl(null);
        $this->assertSame(
            null,
            $cache->getBaseUrl(),
            'Expected matching value after second set'
        );
    }

    /**
     * Test bad input on base url
     *
     * @expectedException InvalidArgumentException
     */
    public function testBadSetBaseUrl()
    {
        $cache = new P4Cms_Cache_Frontend_Action;
        $cache->setBaseUrl(12);
    }
}