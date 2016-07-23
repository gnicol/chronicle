<?php
/**
 * Test methods for the Record Filter class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Record_FilterTest extends TestCase
{
    /**
     * Test creation of filters with good specifications.
     */
    public function testGoodFilter()
    {
        $tests = array(
            array(
                'label'         => __LINE__ .': simple match',
                'filter'        => P4Cms_Record_Filter::create()->add('field', 'value'),
                'expression'    => 'attr-field~=\\^value\\$'
            ),
            array(
                'label'         => __LINE__ .': match containing wildcards',
                'filter'        => P4Cms_Record_Filter::create()->add('field', '*value...'),
                'expression'    => 'attr-field~=\\^\\\*value\\.\\.\\.\\$'
            ),
            array(
                'label'         => __LINE__ .': match containing regex characters',
                'filter'        => P4Cms_Record_Filter::create()->add('field', '$v?(a)l[u]e|^'),
                'expression'    => 'attr-field~=\\^\\\\\$v\\\?\\\\\(a\\\\\)l\\\\\[u\\\\\]e\\\\\|\\\\\^\\$'
            ),
            array(
                'label'         => __LINE__ .': match containing newline/return characters',
                'filter'        => P4Cms_Record_Filter::create()->add('field', "va\nl\rue"),
                'expression'    => "attr-field~=\^va\\\\\\\n" . "l\\\\\\\r" .'ue\\$'
            ),
            array(
                'label'         => __LINE__ .': multi-field match',
                'filter'        => P4Cms_Record_Filter::create()
                                        ->add('field', 'value')
                                        ->add('foo', 'bar'),
                'expression'    => 'attr-field~=\\^value\\$ & attr-foo~=\\^bar\\$'
            ),
            array(
                'label'         => __LINE__ .': multi-value match',
                'filter'        => P4Cms_Record_Filter::create()
                                        ->add('field', 'value')
                                        ->add('foo', array('bar', 'bof')),
                'expression'    => 'attr-field~=\\^value\\$ & (attr-foo~=\\^bar\\$ | attr-foo~=\\^bof\\$)'
            ),
            array(
                'label'         => __LINE__ .': multi-value negated match',
                'filter'        => P4Cms_Record_Filter::create()
                                        ->add('field', 'value')
                                        ->add('foo', array('bar', 'bof'), '!='),
                'expression'    => 'attr-field~=\\^value\\$ &^ (attr-foo~=\\^bar\\$ | attr-foo~=\\^bof\\$)'
            ),
            array(
                'label'         => __LINE__ .': inverted match',
                'filter'        => P4Cms_Record_Filter::create()->add(
                    'field', 'value', P4Cms_Record_Filter::COMPARE_NOT_EQUAL
                ),
                'expression'    => '^attr-field~=\\^value\\$'
            ),
            array(
                'label'         => __LINE__ .': case-insensitive match',
                'filter'        => P4Cms_Record_Filter::create()->add(
                    'field', 'value', P4Cms_Record_Filter::COMPARE_EQUAL, null, true
                ),
                'expression'    => 'attr-field~=\\^[Vv][Aa][Ll][Uu][Ee]\\$'
            ),
            array(
                'label'         => __LINE__ .': regex match',
                'filter'        => P4Cms_Record_Filter::create()->add(
                    'field', 'value', P4Cms_Record_Filter::COMPARE_REGEX
                ),
                'expression'    => 'attr-field~=value'
            ),
            array(
                'label'         => __LINE__ .': regex match, case-sensitive',
                'filter'        => P4Cms_Record_Filter::create()->add(
                    'field', 'value', P4Cms_Record_Filter::COMPARE_REGEX, null, false
                ),
                'expression'    => 'attr-field~=value'
            ),
            array(
                'label'         => __LINE__ .': regex match, with wildcards',
                'filter'        => P4Cms_Record_Filter::create()->add(
                    'field', '.*value...', P4Cms_Record_Filter::COMPARE_REGEX, null, false
                ),
                'expression'    => 'attr-field~=.*value...'
            ),
            array(
                'label'         => __LINE__ .': inverted regex match',
                'filter'        => P4Cms_Record_Filter::create()->add(
                    'field', 'value', P4Cms_Record_Filter::COMPARE_NOT_REGEX
                ),
                'expression'    => '^attr-field~=value'
            ),
            array(
                'label'         => __LINE__ .': regex match alternatives',
                'filter'        => P4Cms_Record_Filter::create()->add(
                    'field', '(V|v)alue', P4Cms_Record_Filter::COMPARE_REGEX, null, false
                ),
                'expression'    => 'attr-field~=\\(V\\|v\\)alue'
            ),
            array(
                'label'         => __LINE__ .': regex match square brackets',
                'filter'        => P4Cms_Record_Filter::create()->add(
                    'field', '^[Vv]alue$', P4Cms_Record_Filter::COMPARE_REGEX, null, false
                ),
                'expression'    => 'attr-field~=\\^[Vv]alue\\$'
            ),
            array(
                'label'         => __LINE__ .': regex match square brackets, nocase',
                'filter'        => P4Cms_Record_Filter::create()->add(
                    'field', 'va[lX]ue', P4Cms_Record_Filter::COMPARE_REGEX, null, true
                ),
                'expression'    => 'attr-field~=[Vv][Aa][LlXx][Uu][Ee]'
            ),
            array(
                'label'         => __LINE__ .': regex match square brackets, nocase, with escapes',
                'filter'        => P4Cms_Record_Filter::create()->add(
                    'field', 'va[lX\\]\\\\]ue', P4Cms_Record_Filter::COMPARE_REGEX, null, true
                ),
                'expression'    => 'attr-field~=[Vv][Aa][LlXx\\\\]\\\\\\\\][Uu][Ee]'
            ),
            array(
                'label'         => __LINE__ .': regex match question mark',
                'filter'        => P4Cms_Record_Filter::create()->add(
                    'field', '^v?alue$', P4Cms_Record_Filter::COMPARE_REGEX, null, false
                ),
                'expression'    => 'attr-field~=\^v?alue\$'
            ),
            array(
                'label'         => __LINE__ .': empty string match',
                'filter'        => P4Cms_Record_Filter::create()->add('field', ''),
                'expression'    => 'attr-field~=\\^\\$'
            ),
            array(
                'label'         => __LINE__ .': null match',
                'filter'        => P4Cms_Record_Filter::create()->add('field', null),
                'expression'    => 'attr-field~=\\^\\$'
            ),
            array(
                'label'         => __LINE__ .': simple contains match',
                'filter'        => P4Cms_Record_Filter::create()->add(
                    'field', 'foo', P4Cms_Record_Filter::COMPARE_CONTAINS
                ),
                'expression'    => 'attr-field~=foo'
            ),
            array(
                'label'         => __LINE__ .': case-insensitive contains match',
                'filter'        => P4Cms_Record_Filter::create()->add(
                    'field', 'foo', P4Cms_Record_Filter::COMPARE_CONTAINS, null, true
                ),
                'expression'    => 'attr-field~=[Ff][Oo][Oo]'
            ),
            array(
                'label'         => __LINE__ .': contains match with special chars',
                'filter'        => P4Cms_Record_Filter::create()->add(
                    'field', 'foo bar-', P4Cms_Record_Filter::COMPARE_CONTAINS
                ),
                'expression'    => 'attr-field~=foo\ bar\-'
            ),
        );

        foreach ($tests as $test) {
            $this->assertSame(
                $test['expression'],
                $test['filter']->getExpression(),
                $test['label']
            );
        }
    }

    /**
     * Test filter construction with an array of options.
     */
    public function testSimpleArrayConstruction()
    {
        $simpleFilterConfig = array(
            'fields'     => array('title' => 'News', 'body' => 'today'),
            'fstat'      => array('headAction' => 'edit', 'headType' => 'xtext'),
            'search'     => array('fields' => array('title', 'body'), 'query' => 'search keywords'),
            'categories' => array('foo', 'bar')
        );

        $simpleArrayFilter = new P4Cms_Record_Filter($simpleFilterConfig);
        $simpleManualFilter = P4Cms_Record_Filter::create()
            ->add('title', 'News')
            ->add('body', 'today')
            ->addFstat('headAction', 'edit')
            ->addFstat('headType', 'xtext')
            ->addSearch(array('title', 'body'), 'search keywords')
            ->setOption('categories', array('foo', 'bar'));
        
        $this->assertSame(
            $simpleManualFilter->getExpression(),
            $simpleArrayFilter->getExpression(),
            __LINE__ .': P4Cms_Record_Filter constructed with an array of options'
        );
        
        $this->assertSame(
            array('foo', 'bar'),
            $simpleArrayFilter->getOption('categories'),
            __LINE__ .': extra options stored in filter'
        );
    }

    /**
     * Test filter construction with an array of options.
     */
    public function testComplexArrayConstruction() 
    {
        $complexFilterConfig = array(
            'fields' => array(
                array(
                    'field'           => 'title',
                    'value'           => 'News',
                    'comparison'      => P4Cms_Record_Filter::COMPARE_EQUAL,
                    'connective'      => P4Cms_Record_Filter::CONNECTIVE_OR,
                    'caseInsensitive' => true),
                array(
                    'field'           => 'body',
                    'value'           => 'today',
                    'comparison'      => P4Cms_Record_Filter::COMPARE_CONTAINS,
                    'connective'      => P4Cms_Record_Filter::CONNECTIVE_OR)),
            'fstat' => array(
                array(
                    'field'           => 'headAction',
                    'value'           => 'edit',
                    'comparison'      => P4Cms_Record_Filter::COMPARE_EQUAL,
                    'connective'      => P4Cms_Record_Filter::CONNECTIVE_OR,
                    'caseInsensitive' => false),
                array(
                    'field'           => 'headType',
                    'value'           => 'xtext',
                    'comparison'      => P4Cms_Record_Filter::COMPARE_EQUAL,
                    'connective'      => P4Cms_Record_Filter::CONNECTIVE_AND,
                    'caseInsensitive' => false)),
            'search'     => array('fields' => array('title', 'body'), 'query' => 'search keywords'),
            'categories' => array('foo', 'bar')
        );
        
        $complexArrayFilter = new P4Cms_Record_Filter($complexFilterConfig);
        $complexManualFilter = P4Cms_Record_Filter::create()
            ->add('title', 'News', P4Cms_Record_Filter::COMPARE_EQUAL, P4Cms_Record_Filter::CONNECTIVE_OR, true)
            ->add('body', 'today', P4Cms_Record_Filter::COMPARE_CONTAINS, P4Cms_Record_Filter::CONNECTIVE_OR)
            ->addFstat(
                'headAction',
                'edit',
                P4Cms_Record_Filter::COMPARE_EQUAL,
                P4Cms_Record_Filter::CONNECTIVE_OR,
                false
            )
            ->addFstat(
                'headType',
                'xtext',
                P4Cms_Record_Filter::COMPARE_EQUAL,
                P4Cms_Record_Filter::CONNECTIVE_AND,
                false
            )
            ->addSearch(array('title', 'body'), 'search keywords')
            ->setOption('categories', array('foo', 'bar'));

        $this->assertSame(
            $complexManualFilter->getExpression(),
            $complexArrayFilter->getExpression(),
            __LINE__ .': P4Cms_Record_Filter constructed with an array of options'
        );

        $this->assertSame(
            array('foo', 'bar'),
            $complexArrayFilter->getOption('categories'),
            __LINE__ .': extra options stored in filter'
        );
    }

    /**
     * Test filter construction with a string.
     */
    public function testStringConstruction()
    {
        $stringFilter = new P4Cms_Record_Filter(P4Cms_Record_Filter::FALSE_EXPRESSION);
        $this->assertSame(
            P4Cms_Record_Filter::FALSE_EXPRESSION,
            $stringFilter->getExpression(),
            __LINE__ .': P4Cms_Record_Filter constructed with a the false expression'
        );
        
        $stringFilter = new P4Cms_Record_Filter('foobar');
        $this->assertSame(
            'foobar',
            $stringFilter->getExpression(),
            __LINE__ .': P4Cms_Record_Filter constructed with a string'
        );
    }
    
    /**
     * Test building a filter object with a missing field option.
     */
    public function testMissingFields()
    {
        $arrayFilterConfig = array(
            'fields' => array(
                array(
                    'field' => '',
                    'value' => 'News')),
            'fstat' => array(),
            'search'     => array()
        );
        
        $this->setExpectedException(
            'InvalidArgumentException',
            'Cannot add condition. Field must be a non-empty string.'
        );
        $filter = new P4Cms_Record_Filter($arrayFilterConfig);
    }
    
    /**
     * Test storing and retrieving custom filter options that don't correspond to 
     * built-in options.
     */
    public function testFilterOptions()
    {
        $optionFilter = new P4Cms_Record_Filter();
        $newFilter = $optionFilter->setOption('test', array('foo', 'bar'));
        
        $this->assertInstanceOf(
            'P4Cms_Record_Filter',
            $newFilter,
            __LINE__ .': P4Cms_Record_Filter object returned from setOption()'
        );
        
        $this->assertSame(
            array('foo', 'bar'),
            $newFilter->getOption('test'),
            __LINE__ .': test options stored in filter'
        );
        
        $this->assertNull(
            $newFilter->getOption('does not exist'),
            __LINE__ .': null returned for option that does not exist.'
        );
    }

    /**
     * Test behaviour of filters with invalid specifications.
     */
    public function testBadFilter()
    {
        $tests = array(
            array(
                'label'             => __LINE__ .': null field',
                'field'             => null,
                'value'             => '',
                'comparison'        => '',
                'connective'        => '',
                'caseInsensitive'   => '',
                'error'             => array(
                    'InvalidArgumentException' => 'Cannot add condition. Field must be a non-empty string.'
                ),
            ),
            array(
                'label'             => __LINE__ .': empty field',
                'field'             => '',
                'value'             => '',
                'comparison'        => '',
                'connective'        => '',
                'caseInsensitive'   => '',
                'error'             => array(
                    'InvalidArgumentException' => 'Cannot add condition. Field must be a non-empty string.'
                ),
            ),
            array(
                'label'             => __LINE__ .': non-string field',
                'field'             => array(),
                'value'             => '',
                'comparison'        => '',
                'connective'        => '',
                'caseInsensitive'   => '',
                'error'             => array(
                    'InvalidArgumentException' => 'Cannot add condition. Field must be a non-empty string.'
                ),
            ),
            array(
                'label'             => __LINE__ .': empty array value',
                'field'             => 'field',
                'value'             => array(),
                'comparison'        => '',
                'connective'        => '',
                'caseInsensitive'   => '',
                'error'             => array(
                    'InvalidArgumentException' => 'Cannot add condition.'
                        . ' Value must be null, a string or an array of strings.'
                ),
            ),
            array(
                'label'             => __LINE__ .': array value containing non-string',
                'field'             => 'field',
                'value'             => array(array()),
                'comparison'        => P4Cms_Record_Filter::COMPARE_EQUAL,
                'connective'        => P4Cms_Record_Filter::CONNECTIVE_AND,
                'caseInsensitive'   => '',
                'error'             => array(
                    'InvalidArgumentException' => 'Cannot add condition.'
                        . ' Value array must contain only strings.'
                ),
            ),
            array(
                'label'             => __LINE__ .': object value',
                'field'             => 'field',
                'value'             => new stdClass,
                'comparison'        => '',
                'connective'        => '',
                'caseInsensitive'   => '',
                'error'             => array(
                    'InvalidArgumentException' => 'Cannot add condition.'
                        . ' Value must be null, a string or an array of strings.'
                ),
            ),
            array(
                'label'             => __LINE__ .': empty comparison',
                'field'             => 'field',
                'value'             => 'value',
                'comparison'        => '',
                'connective'        => '',
                'caseInsensitive'   => '',
                'error'             => array(
                    'InvalidArgumentException' => 'Cannot add condition. Invalid comparison operator specified.'
                ),
            ),
            array(
                'label'             => __LINE__ .': empty connective',
                'field'             => 'field',
                'value'             => 'value',
                'comparison'        => P4Cms_Record_Filter::COMPARE_EQUAL,
                'connective'        => '',
                'caseInsensitive'   => '',
                'error'             => array(
                    'InvalidArgumentException' => 'Cannot add condition. Invalid connective specified.'
                ),
            ),
        );

        foreach ($tests as $test) {
            extract($test);

            try {
                P4Cms_Record_Filter::create()->add($field, $value, $comparison, $connective, $caseInsensitive);
                $this->fail($label.' Unexpected success');
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $this->fail($e->getMessage());
            } catch (PHPUnit_Framework_ExpectationFailedError $e) {
                $this->fail($e->getMessage());
            } catch (Exception $e) {
                if (!$test['error']) {
                    $this->fail("$label - Unexpected exception (". get_class($e) .') :'. $e->getMessage());
                } else {
                    list($class, $error) = each($test['error']);
                    $this->assertEquals(
                        $class,
                        get_class($e),
                        "$label - expected exception class: ". $e->getMessage()
                    );
                    $this->assertEquals(
                        $error,
                        $e->getMessage(),
                        "$label - expected exception message"
                    );
                }
            }
        }
    }

    /**
     * Test behaviour of subfilters with invalid specifications.
     */
    public function testBadSubFilter()
    {
        $tests = array(
            array(
                'label'         => __LINE__ .': invalid filter type',
                'filter'        => '',
                'connective'    => ''
            ),
            array(
                'label'         => __LINE__ .': invalid connective',
                'filter'        => P4Cms_Record_Filter::create(),
                'connective'    => ''
            ),
        );

        foreach ($tests as $test) {
            extract($test);

            try {
                P4Cms_Record_Filter::create()->addSubFilter($filter, $connective);

                $this->fail($label.' Expected exception');
            } catch (InvalidArgumentException $e) {
                $this->assertTrue(true, $label);
            }
        }
    }
}
