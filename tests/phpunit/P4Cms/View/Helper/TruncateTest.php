<?php
/**
 * Test methods for the P4Cms Truncate View Helper.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_View_Helper_TruncateTest extends TestCase
{
    /**
     * Verify non-nested options get properly output
     */
    public function testTruncate()
    {
        $helper = new P4Cms_View_Helper_Truncate;
        $helper->setView(new Zend_View);
     
        $tests = array(
            array(
                'label'    => __LINE__,
                'input'    => 'The quick brown fox jumps over the lazy dog',
                'output'   => 'The quick brown fox jumps over the lazy dog',
                'escape'   => false,
                'limit'    => 50,
                'trailing' => null
            ),
            array(
                'label'    => __LINE__,
                'input'    => 'The quick brown fox jumps over the lazy dog',
                'output'   => 'The quick brown fox jumps over the lazy dog',
                'escape'   => false,
                'limit'    => 50,
                'trailing' => '...'
            ),
            array(
                'label'    => __LINE__,
                'input'    => 'The quick brown fox jumps over the lazy dog',
                'output'   => 'The quick brown fox jumps over the lazy...',
                'escape'   => false,
                'limit'    => 40,
                'trailing' => '...'
            ),
            array(
                'label'    => __LINE__,
                'input'    => 'The quick brown fox jumps over the lazy dog',
                'output'   => 'The quick brown fox jumps over the lazy...',
                'escape'   => false,
                'limit'    => 41,
                'trailing' => '...'
            ),
            array(
                'label'    => __LINE__,
                'input'    => 'The quick brown fox jumps over the lazy dog',
                'output'   => 'The quick brown fox jumps over the lazy',
                'escape'   => false,
                'limit'    => 40,
                'trailing' => null
            ),
            array(
                'label'    => __LINE__,
                'input'    => 'The quick brown fox jumps over the lazy dog',
                'output'   => 'The quick brown fox jumps over the lazy dog',
                'escape'   => false,
                'limit'    => 40,
                'trailing' => ' dog'
            ),
            array(
                'label'    => __LINE__,
                'input'    => '     The quick brown fox jumps over the lazy dog',
                'output'   => 'The quick brown fox jumps over the lazy dog',
                'escape'   => false,
                'limit'    => 43,
                'trailing' => null
            ),
            array(
                'label'    => __LINE__,
                'input'    => 'The-quick-brown-fox-jumps-over-the-lazy-dog',
                'output'   => 'The-quick-brown-fox-',
                'escape'   => false,
                'limit'    => 20,
                'trailing' => null
            ),
            array(
                'label'    => __LINE__,
                'input'    => 'Escape test 1 < 3 < 5 < 7 < 9.',
                'output'   => 'Escape test 1 < 3 < 5 < 7 < 9.',
                'escape'   => false,
                'limit'    => 100,
                'trailing' => '...'
            ),
            array(
                'label'    => __LINE__,
                'input'    => 'Escape test 1 > x < 5 < 7 < 9.',
                'output'   => 'Escape test 1 &gt; x &lt; 5...',
                'escape'   => true,
                'limit'    => 21,
                'trailing' => '...'
            ),
            array(
                'label'    => __LINE__,
                'input'    => '(1) & <script /> xx',
                'output'   => '(1) &amp; &lt;script /&gt;&amp;..',
                'escape'   => true,
                'limit'    => 17,
                'trailing' => '&..'
            )
        );

        foreach ($tests as $test) {
            $this->assertSame(
                $test['output'],
                $helper->truncate($test['input'], $test['limit'], $test['trailing'], $test['escape']),
                $test['label']
            );
        }
    }
}
