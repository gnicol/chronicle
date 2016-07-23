<?php
/**
 * Test methods for the html to text filter.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Filter_HtmlToTextTest extends TestCase
{
    /**
     * Test HTML to text conversion.
     */
    public function testFilter()
    {
        $tests = array(
            array(
                'label' => __LINE__ .' - null html',
                'html'  => null,
                'text'  => null,
            ),
            array(
                'label' => __LINE__ .' - no html',
                'html'  => '',
                'text'  => null,
            ),
            array(
                'label' => __LINE__ .' - just text',
                'html'  => 'the quick brown fox jumped over the lazy dog.',
                'text'  => 'the quick brown fox jumped over the lazy dog.',
            ),
            array(
                'label' => __LINE__ .' - simple HTML',
                'html'  => '<p><strong>This</strong> is<br/>a test&iexcl;</p>',
                'text'  => "This is\na test¡",
            ),
            array(
                'label' => __LINE__ .' - simple HTML, space after keyword',
                'html'  => '<p><strong>This</strong> is<br  />a test&iexcl;</p>',
                'text'  => "This is\na test¡",
            ),
            array(
                'label' => __LINE__ .' - simple HTML, space before and after keyword',
                'html'  => '<p><strong>This</strong> is<  br  />a test&iexcl;</p>',
                'text'  => "This is\na test¡",
            ),
            array(
                'label' => __LINE__ .' - simple HTML, space before and after keyword plus attribute',
                'html'  => '<p><strong>This</strong> is<  br class="foo"  />a test&iexcl;</p>',
                'text'  => "This is\na test¡",
            ),
            array(
                'label' => __LINE__ .' - simple HTML, two line-breaks',
                'html'  => '<p><strong>This</strong> is<br/><br/>a test&iexcl;</p>',
                'text'  => "This is\n\na test¡",
            ),
            array(
                'label' => __LINE__ .' - simple HTML, three line-breaks to stay at two',
                'html'  => '<p><strong>This</strong> is<br/><br/><br/>a test&iexcl;</p>',
                'text'  => "This is\n\na test¡",
            ),
            array(
                'label' => __LINE__ .' - simple HTML, keep entities',
                'html'  => '<p><strong>This</strong> is<br/>a test&iexcl;</p>',
                'text'  => <<<'EOT'
This is
a test&iexcl;
EOT
,
                'keepEntities'  => true,
            ),
            array(
                'label' => __LINE__ .' - simple HTML, with headings',
                'html'  => 'Yabba dabba<h1>My heading1</h1>the body',
                'text'  => "Yabba dabba\n\n\nMY HEADING1:\n\nthe body",
            ),
            array(
                'label' => __LINE__ .' - simple HTML, with multiple headings',
                'html'  => 'Yabba dabba<h1>My heading1</h1>the body<h2>Another heading</h2>more body',
                'text'  => "Yabba dabba\n\n\nMY HEADING1:\n\nthe body\n\n\nANOTHER HEADING:\n\nmore body",
            ),
            array(
                'label' => __LINE__ .' - simple HTML, with headings, space before',
                'html'  => 'Yabba dabba<  h4>My heading2</h4>the body',
                'text'  => "Yabba dabba\n\n\nMY HEADING2:\n\nthe body",
            ),
            array(
                'label' => __LINE__ .' - simple HTML, with headings, space before and after',
                'html'  => 'Yabba dabba<  h4 >My heading3</h1  >the body',
                'text'  => "Yabba dabba\n\n\nMY HEADING3:\n\nthe body",
            ),
            array(
                'label' => __LINE__ .' - simple HTML, with multiple script blocks',
                'html'  => 'This test should <script>myscript</script> not <script>another</script> fail',
                'text'  => 'This test should not fail',
            ),
            array(
                'label' => __LINE__ .' - a link',
                'html'  => 'Please click <a href="http://perforce.com/">here</a>.',
                'text'  => 'Please click here [http://perforce.com/].',
            ),
            array(
                'label' => __LINE__ .' - a link, keep links',
                'html'  => 'Please click <a href="http://perforce.com/">here</a>.',
                'text'  => 'Please click <a href="http://perforce.com/">here</a>.',
                'keepLinks' => true,
            ),
            array(
                'label' => __LINE__ .' - HTML with pre',
                'html'  => <<<'EOH'
<p>
    Here is some sample code:
</p>

<pre>
    $count = 0;
    foreach ($list as $item) {
        $item->number($count++);
    }
</pre>

EOH
,
                'text'  => <<<'EOT'
Here is some sample code:

     $count = 0;
     foreach ($list as $item) {
         $item->number($count++);
     }
EOT
,
            ),
        );

        $filter = new P4Cms_Filter_HtmlToText;
        foreach ($tests as $test) {
            $filter->setOptions(
                array(
                    'keepLinks' => (array_key_exists('keepLinks', $test)
                        ? $test['keepLinks']
                        : false),
                    'keepEntities' => (array_key_exists('keepEntities', $test)
                        ? $test['keepEntities']
                        : false)
                )
            );

            if (is_string($test['text'])) {
                $test['text'] = str_replace("\r\n", "\n", $test['text']);
            }
            
            $this->assertSame(
                $test['text'],
                $filter->filter($test['html']),
                $test['label'] .': Expected text'
            );
        }
    }
}
