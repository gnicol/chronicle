<?php
/**
 * Test the url path filter (normalizer).
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Url_Test_UrlPathFilterTest extends ModuleTest
{
    /**
     * Activate url module.
     */
    public function setUp()
    {
        parent::setUp();
        P4Cms_Module::fetch('Url')->enable()->load();
    }

    /**
     * Test various url path inputs to ensure they are
     * normalized consistently.
     */
    public function testFilter()
    {
        $tests = array(
            'test-path'         => 'test-path',
            'test+path'         => 'test+path',
            'special-!@$&*()'   => 'special-!@$&*()',
            'test path'         => 'test%20path',
            'bad-encode%zz'     => 'bad-encode%25zz',
            'foobar-#?'         => 'foobar-%23%3f',
            'biz/bang'          => 'biz/bang',
            'fizzle%'           => 'fizzle%25',
            '/woozle '          => 'woozle'
        );
        
        $filter = new Url_Filter_UrlPath;
        foreach ($tests as $input => $output) {
            $this->assertSame($output, $filter->filter($output));
        }
        
        // ensure filter can be run multiple times without double encoding
        $input    = "/path with spaces/";
        $expected = "path%20with%20spaces";
        $output   = $filter->filter($input);
        
        $this->assertSame($expected, $output);
        $this->assertSame($expected, $filter->filter($output));
        
        // null in, null out.
        $this->assertSame(null, $filter->filter(null));
    }

    /**
     * Test filtering with an invalid value. 
     */
    public function testFilterWithInvalid()
    {
        $filter = new Url_Filter_UrlPath;
        try {
            $filtered = $filter->filter(new stdClass);
            $this->fail('Unexpected success filtering a non-string value');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(
                'Cannot normalize url path. Value must be a string.',
                $e->getMessage(),
                'Expected exception message.'
            );
        } catch (PHPUnit_FrameWork_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (Exception $e) {
            $this->fail('Unexpected exception ('. get_class($e) .') - '. $e->getMessage());
        }
    }
}
