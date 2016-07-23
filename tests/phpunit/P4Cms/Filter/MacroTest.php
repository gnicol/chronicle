<?php
/**
 * Test the content model.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Filter_MacroTest extends TestCase
{
    /**
     * Test the constructor's ability to accept options.
     */
    public function testConstructor()
    {
        // try with no argument
        $filter = new P4Cms_Filter_Macro;
        $this->assertTrue($filter instanceof P4Cms_Filter_Macro);
        $this->assertSame(null, $filter->getContext(), 'Expected null context');

        // try with an argument
        $expected = array(
            'foo'       => 'bar',
            'object'    => new stdClass
        );
        $filter = new P4Cms_Filter_Macro($expected);
        $this->assertTrue($filter instanceof P4Cms_Filter_Macro);
        $this->assertSame($expected, $filter->getContext(), 'Expected array context');
    }

    /**
     * Test setContext.
     */
    public function testSetContext()
    {
        $tests = array(
            array(
                'label'         => __LINE__ .': null',
                'context'       => null,
            ),
            array(
                'label'         => __LINE__ .': empty array',
                'context'       => array(),
            ),
            array(
                'label'         => __LINE__ .': good array',
                'context'       => array(
                    'foo'       => 'bar',
                    'object'    => new stdClass,
                ),
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];

            $filter = new P4Cms_Filter_Macro;
            $filter->setContext($test['context']);
            $this->assertSame($test['context'], $filter->getContext(), "$label - Expected context");
        }
    }

    /**
     * Test the use of macros in content.
     */
    public function testFilter()
    {
        $testText = <<<EOD
I am a test of very fancy {{macros}}
{{macro2: arg}}shouldn't be captured{{/macro3}}
{{macro3 :arg1,arg2 }}

{{ if:test /}}{{if}}test body{{/if}}
{{literal}}
{{some crap}}
{{/literal}}

{{/unpaired}}
{{unhandled/}}
{{unhandled}}Test Text{{/unhandled}}
{{unhandled:arg,arg,arg}}
EOD;

        $expectedOutput = <<<EOD
I am a test of very fancy f4f7daf3f9e78ad608e8f52c78c1387a
715fbbd19f83cc022133d42d1a960edcshouldn't be captured{{/macro3}}
b1c45d273f407924da0cd840d9b2c10d

bfbb4b40515b460bd411c38dbab6fcd1cdb492492953490e0cb4992939a633bb
{{literal}}
{{some crap}}
{{/literal}}

{{/unpaired}}
{{unhandled/}}
{{unhandled}}Test Text{{/unhandled}}
{{unhandled:arg,arg,arg}}
EOD;

        $testText       = str_replace("\r\n", "\n", $testText);
        $expectedOutput = str_replace("\r\n", "\n", $expectedOutput);

        $expectedMacroCalls = array (
            'macros' => array(
                array (
                    'params'    => array(),
                    'body'      => NULL,
                    'content'   => 'P4Cms_Content',
                    'element'   => 'Zend_Form_Element',
                ),
            ),
            'macro2' => array(
                array (
                    'params'    => array('arg'),
                    'body'      => NULL,
                    'content'   => 'P4Cms_Content',
                    'element'   => 'Zend_Form_Element',
                ),
            ),
            'macro3' => array(
                array (
                    'params'    => array('arg1', 'arg2'),
                    'body'      => NULL,
                    'content'   => 'P4Cms_Content',
                    'element'   => 'Zend_Form_Element',
                ),
            ),
            'if' => array(
                array(
                    'params'    => array('test'),
                    'body'      => NULL,
                    'content'   => 'P4Cms_Content',
                    'element'   => 'Zend_Form_Element',
                ),
                array(
                    'params'    => array (),
                    'body'      => 'test body',
                    'content'   => 'P4Cms_Content',
                    'element'   => 'Zend_Form_Element',
                ),
            ),
        );

        // outside place to hold result of all calls
        $macroCalls = array();

        // for each of the possible macros (other than 'unhandled') sign up a
        // pub/sub subscriber that will log the call and return a hashed output
        array_map(
            function($macro) use (&$macroCalls)
            {
                P4Cms_PubSub::subscribe(
                    P4Cms_Filter_Macro::TOPIC . $macro,
                    function($params, $body, $context) use (&$macroCalls, $macro)
                    {
                        $content = get_class($context['content']);
                        $element = get_class($context['element']);
                        $input   = compact('params', 'body', 'content', 'element');

                        $macroCalls[$macro][] = $input;

                        return md5(var_export($input, true));
                    }
                );
            },
            array(
                'macros', 'macro2', 'macro3', 'if',  '/if',
                'literal', '/literal', 'unpaired', '/unpaired'
            )
        );

        $filter = new P4Cms_Filter_Macro;
        $filter->setContext(
            array(
                'content'   => new P4Cms_Content,
                'element'   => new Zend_Form_Element('test')
            )
        );
        $output = $filter->filter($testText);

        $this->assertSame(
            $expectedOutput,
            $output,
            'Expected expanded output to match'
        );

        $this->assertSame(
            $expectedMacroCalls,
            $macroCalls,
            'Expected matching macro calls'
        );
    }
}