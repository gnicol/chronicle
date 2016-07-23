<?php
/**
 * Test filter functionality of P4Cms model iterator.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Model_IteratorTest extends TestCase
{
    /**
     *  Test suite for testing iterator filter
     */
    public function testFilter()
    {
        $data = array(
            // key                   bar            foo             baz
            'YELLOW'        => array('Flavor',      'newer',        3130),
            'CYAN'          => array('Fluke',       'dark',         303),
            'BLACK'         => array('Flute',       'warm',         '0300'),
            'BROWN'         => array('Goflu',       'warmer',       13),
            'BEIGE'         => array('Goflugo',     'large',        30001),
            'RED'           => array('Earth worm',  'never',        1234567),
            'FLUORESCENT'   => array('Bee',         'best',         778),
            'LIGHT RED'     => array('Darkness',    'fluent',       250),
            'DARK RED'      => array('Worm',        'brave',        500213),
        );

        // prepare items for P4Cms_Model_Iterator
        $items = array();
        foreach ($data as $key => $record) {
            $item = new P4Cms_Model_Implementation;
            $item->setId($key);
            $item->setBar($record[0]);
            $item->setFoo($record[1]);
            $item->setBaz($record[2]);
            $items[] = $item;
        }

        $filterTests = array(
            // extreme cases
            array(
                'label'             => __LINE__ . ': blank parameters - return empty array',
                'filterFields'      => '',
                'filterPatterns'    => '',
                'filterOptions'     => array(),
                'resultItems'       => array()
            ),
            array(
                'label'             => __LINE__ . ': search everywhere for non-existant pattern',
                'filterFields'      => null,
                'filterPatterns'    => array('ggggh', 'zzzzz', 'qqqqq', '&^$#^&$%#'),
                'filterOptions'     => array(),
                'resultItems'       => array()
            ),
            array(
                'label'             => __LINE__ . ': search in non-existant fields',
                'filterFields'      => array('nonexist1', 'nonexist2'),
                'filterPatterns'    => array('Fluke', 'Flute'),
                'filterOptions'     => array(),
                'resultItems'       => array()
            ),
            array(
                'label'             => __LINE__ . ': search for pattern matches any string',
                'filterFields'      => null,
                'filterPatterns'    => '/.*/',
                'filterOptions'     => P4Cms_Model_Iterator::FILTER_REGEX,
                'resultItems'       => array_keys($data)
            ),
            array(
                'label'             => __LINE__ . ': test OR behavior when more patterns/fields are passed',
                'filterFields'      => array('non-exist', 'bar'),
                'filterPatterns'    => array('/^xxx/', '/.*/'),
                'filterOptions'     => array('UGGGH', P4Cms_Model_Iterator::FILTER_REGEX),
                'resultItems'       => array_keys($data)
            ),

            // test default behavior - case sensitive, literal comparison
            array(
                'label'             => __LINE__ . ': default options',
                'filterFields'      => 'bar',
                'filterPatterns'    => 'fluke',
                'filterOptions'     => array(),
                'resultItems'       => array()
            ),
            array(
                'label'             => __LINE__ . ': default options',
                'filterFields'      => 'bar',
                'filterPatterns'    => 'Darknes',
                'filterOptions'     => array(),
                'resultItems'       => array()
            ),
            array(
                'label'             => __LINE__ . ': default options',
                'filterFields'      => 'foo',
                'filterPatterns'    => 'Darkness',
                'filterOptions'     => array(),
                'resultItems'       => array()
            ),
            array(
                'label'             => __LINE__ . ': default options',
                'filterFields'      => array('foo', 'baz', 'key'),
                'filterPatterns'    => 'Darkness',
                'filterOptions'     => array(),
                'resultItems'       => array()
            ),

            // test parameter types with default options
            array(
                'label'             => __LINE__ . ': field as string, value as string',
                'filterFields'      => 'bar',
                'filterPatterns'    => 'Fluke',
                'filterOptions'     => array(),
                'resultItems'       => array('CYAN')
            ),
            array(
                'label'             => __LINE__ . ': field as array, value as string',
                'filterFields'      => array('bar'),
                'filterPatterns'    => 'Fluke',
                'filterOptions'     => array(),
                'resultItems'       => array('CYAN')
            ),
            array(
                'label'             => __LINE__ . ': field as string, value as array',
                'filterFields'      => 'bar',
                'filterPatterns'    => array('Fluke'),
                'filterOptions'     => array(),
                'resultItems'       => array('CYAN')
            ),
            array(
                'label'             => __LINE__ . ': field as array, value as array',
                'filterFields'      => array('bar'),
                'filterPatterns'    => array('Fluke'),
                'filterOptions'     => array(),
                'resultItems'       => array('CYAN')
            ),
            array(
                'label'             => __LINE__ . ': field as null - should be substitued by all model fields',
                'filterFields'      => null,
                'filterPatterns'    => array('Fluke'),
                'filterOptions'     => array(),
                'resultItems'       => array('CYAN')
            ),

            // test options
            array(
                'label'             => __LINE__ . ': field as null - should be substitued by all model fields',
                'filterFields'      => null,
                'filterPatterns'    => array('AR'),
                'filterOptions'     => P4Cms_Model_Iterator::FILTER_CONTAINS,
                'resultItems'       => array('DARK RED')
            ),
            array(
                'label'             => __LINE__ . ': field as null - should be substitued by all model fields',
                'filterFields'      => null,
                'filterPatterns'    => array('AR'),
                'filterOptions'     => array(
                    P4Cms_Model_Iterator::FILTER_CONTAINS,
                    P4Cms_Model_Iterator::FILTER_NO_CASE
                ),
                'resultItems'       => array('CYAN', 'BLACK', 'BROWN', 'BEIGE', 'RED', 'LIGHT RED', 'DARK RED')
            ),
            array(
                'label'             => __LINE__ . ': search for 2 patterns in 2 fields',
                'filterFields'      => array('key', 'foo'),
                'filterPatterns'    => array('flu', 'rm'),
                'filterOptions'     => array(
                    P4Cms_Model_Iterator::FILTER_CONTAINS,
                    P4Cms_Model_Iterator::FILTER_NO_CASE
                ),
                'resultItems'       => array('BLACK', 'BROWN', 'LIGHT RED', 'FLUORESCENT')
            ),
            array(
                'label'             => __LINE__ . ': search for baz values don\'t contain zero',
                'filterFields'      => 'baz',
                'filterPatterns'    => '/^[^0]+$/',
                'filterOptions'     => P4Cms_Model_Iterator::FILTER_REGEX,
                'resultItems'       => array('BROWN', 'RED', 'FLUORESCENT')
            ),
            array(
                'label'             => __LINE__ . ': remove all items contain zero in any field',
                'filterFields'      => null,
                'filterPatterns'    => '/0/',
                'filterOptions'     => array(
                    P4Cms_Model_Iterator::FILTER_REGEX,
                    P4Cms_Model_Iterator::FILTER_INVERSE,
                ),
                'resultItems'       => array('BROWN', 'RED', 'FLUORESCENT')
            ),
            array(
                'label'             => __LINE__ . ': select items contain digit in any field',
                'filterFields'      => null,
                'filterPatterns'    => '/\d/',
                'filterOptions'     => P4Cms_Model_Iterator::FILTER_REGEX,
                'resultItems'       => array_keys($data)
            ),
        );

        // append tests with reverse behavior to test FILTER_INVERSE option
        foreach ($filterTests as $test) {

            $resultItemsInverse  = array_diff(array_keys($data), $test['resultItems']);
            $testInverse         = $test;

            $testInverse['label']         .= ' (inversed)';
            $testInverse['resultItems']   = $resultItemsInverse;
            $testInverse['filterOptions'] = (array) $testInverse['filterOptions'];

            if (in_array(P4Cms_Model_Iterator::FILTER_INVERSE, $testInverse['filterOptions'])) {
                $testInverse['filterOptions'] = array_diff(
                    $testInverse['filterOptions'],
                    array(P4Cms_Model_Iterator::FILTER_INVERSE)
                );
            } else {
                $testInverse['filterOptions'][] = P4Cms_Model_Iterator::FILTER_INVERSE;
            }

            $filterTests[] = $testInverse;
        }

        // batch test
        foreach ($filterTests as $test) {

            // prepare iterator
            $iterator = new P4Cms_Model_Iterator($items);

            // check that iterator was populated with all data
            $this->assertSame(
                count($data),
                $iterator->count(),
                "Expected number of iterator items"
            );

            // filter items
            $iterator->filter($test['filterFields'], $test['filterPatterns'], $test['filterOptions']);

            // check if number of iterator items match expected result
            $this->assertSame(
                count($test['resultItems']),
                $iterator->count(),
                $test['label'] . ": Number of items doesn't match, expected: "
                . count($test['resultItems']) . " - actual: " . $iterator->count()
            );

            // check items in array iterator
            $itemKeys = $iterator->invoke('getId');
            foreach ($test['resultItems'] as $itemKey) {
                $this->assertTrue(
                    in_array($itemKey, $itemKeys),
                    $test['label'] . " - Expected key $itemKey not found in filtered result."
                );
            }
        }

        // test joining filters
        $iterator = new P4Cms_Model_Iterator($items);

        // first filter
        $iterator->filter(
            'baz',
            '/^3/',
            P4Cms_Model_Iterator::FILTER_REGEX
        );
        $this->assertSame(
            array('YELLOW', 'CYAN', 'BEIGE'),
            $iterator->invoke('getId'),
            'Expected values after first pass'
        );

        // second filter
        $result = $iterator->filter(
            null,
            'flu',
            array(P4Cms_Model_Iterator::FILTER_CONTAINS, P4Cms_Model_Iterator::FILTER_NO_CASE)
        );
        $this->assertSame(
            array('CYAN', 'BEIGE'),
            $iterator->invoke('getId'),
            'Expected values after second pass'
        );

        // do the same by joining filters inline
        $iterator = new P4Cms_Model_Iterator($items);

        $iterator->filter(
            'baz',
            '/^3/',
            P4Cms_Model_Iterator::FILTER_REGEX
        )
        ->filter(
            null,
            'flu',
            array(
                P4Cms_Model_Iterator::FILTER_CONTAINS,
                P4Cms_Model_Iterator::FILTER_NO_CASE
            )
        );
        $this->assertSame(
            $result->toArray(),
            $iterator->toArray(),
            'Expected identical result if filters are joined inline'
        );

        // filters order in multi-filtering is independent
        $iterator = new P4Cms_Model_Iterator($items);

        $iterator->filter(
            null,
            'flu',
            array(
                P4Cms_Model_Iterator::FILTER_CONTAINS,
                P4Cms_Model_Iterator::FILTER_NO_CASE
            )
        )
        ->filter(
            'baz',
            '/^3/',
            P4Cms_Model_Iterator::FILTER_REGEX
        );
        $this->assertSame(
            $result->toArray(),
            $iterator->toArray(),
            'Expected identical result if filters order is reversed'
        );
    }

    /**
     * Test iterator filtering with callbacks.
     */
    public function testFilterByCallback()
    {
        $data = array(
            // key                   bar            foo             baz
            'YELLOW'        => array('Flavor',      'newer',        3130),
            'CYAN'          => array('Fluke',       'dark',         303),
            'BLACK'         => array('Flute',       'warm',         '0300'),
            'BROWN'         => array('Goflu',       'warmer',       13),
            'BEIGE'         => array('Goflugo',     'large',        30001),
            'RED'           => array('Earth worm',  'never',        1234567),
            'FLUORESCENT'   => array('Bee',         'best',         778),
            'LIGHT RED'     => array('Darkness',    'fluent',       250),
            'DARK RED'      => array('Worm',        'brave',        500213),
        );

        // prepare items for P4Cms_Model_Iterator
        $items = array();
        foreach ($data as $key => $record) {
            $item = new P4Cms_Model_Implementation;
            $item->setId($key);
            $item->setBar($record[0]);
            $item->setFoo($record[1]);
            $item->setBaz($record[2]);
            $items[] = $item;
        }

        $filterTests = array(
            // extreme cases
            array(
                'label'             => __LINE__ . ': remove all items',
                'callback'          =>
                    function($model)
                    {
                        return false;
                    },
                'params'            => null,
                'filterOptions'     => array(),
                'resultItems'       => array()
            ),
            array(
                'label'             => __LINE__ . ': keep all items',
                'callback'          =>
                    function($model)
                    {
                        return true;
                    },
                'params'            => null,
                'filterOptions'     => array(),
                'resultItems'       => array_keys($data)
            ),

            array(
                'label'             => __LINE__ . ': keep items with baz > 1000',
                'callback'          =>
                    function($model)
                    {
                        return ((int) $model->getBaz() > 1000);
                    },
                'params'            => null,
                'filterOptions'     => array(),
                'resultItems'       => array('YELLOW', 'BEIGE', 'RED', 'DARK RED')
            ),
            array(
                'label'             => __LINE__ . ': string optional param test',
                'callback'          =>
                    function($model, $string)
                    {
                        return substr($model->getFoo(), 0, 1) == $string;
                    },
                'params'            => 'w',
                'filterOptions'     => array(),
                'resultItems'       => array('BLACK', 'BROWN')
            ),
            array(
                'label'             => __LINE__ . ': mixed optional param test',
                'callback'          =>
                    function($model, $params)
                    {
                        return strlen($model->getBar()) >= $params['min']
                               && strlen($model->getBar()) <= $params['max'];
                    },
                'params'            => array('min' => 5, 'max' => 6),
                'filterOptions'     => array(),
                'resultItems'       => array('YELLOW', 'CYAN', 'BLACK', 'BROWN')
            ),
        );

        // append tests with reverse behavior to test FILTER_INVERSE option
        foreach ($filterTests as $test) {

            $resultItemsInverse  = array_diff(array_keys($data), $test['resultItems']);
            $testInverse         = $test;

            $testInverse['label']         .= ' (inversed)';
            $testInverse['resultItems']   = $resultItemsInverse;
            $testInverse['filterOptions'] = (array) $testInverse['filterOptions'];

            if (in_array(P4Cms_Model_Iterator::FILTER_INVERSE, $testInverse['filterOptions'])) {
                $testInverse['filterOptions'] = array_diff(
                    $testInverse['filterOptions'],
                    array(P4Cms_Model_Iterator::FILTER_INVERSE)
                );
            } else {
                $testInverse['filterOptions'][] = P4Cms_Model_Iterator::FILTER_INVERSE;
            }

            $filterTests[] = $testInverse;
        }

        // batch test
        foreach ($filterTests as $test) {

            // prepare iterator
            $iterator = new P4Cms_Model_Iterator($items);

            // check that iterator was populated with all data
            $this->assertSame(
                count($data),
                $iterator->count(),
                "Expected number of iterator items"
            );

            // filter items
            $iterator->filterByCallback($test['callback'], $test['params'], $test['filterOptions']);

            // check if number of iterator items match expected result
            $this->assertSame(
                count($test['resultItems']),
                $iterator->count(),
                $test['label'] . ": Number of items doesn't match, expected: "
                . count($test['resultItems']) . " - actual: " . $iterator->count()
            );

            // check items in array iterator
            $itemKeys = $iterator->invoke('getId');
            foreach ($test['resultItems'] as $itemKey) {
                $this->assertTrue(
                    in_array($itemKey, $itemKeys),
                    $test['label'] . " - Expected key $itemKey not found in filtered result."
                );
            }
        }
    }

}
