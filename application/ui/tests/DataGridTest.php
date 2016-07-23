<?php
/**
 * Test methods for the P4Cms DataGrid View Helper.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Ui_Test_DataGridTest extends ModuleTest
{
    // minimum options required by DataGrid helper to render
    protected $_minimumOptions = array(
        'url'       => '/',
        'columns'   => array()
    );

    protected $_view;

    /**
     * Extend parent to preset view from MVC instance.
     */
    public function setUp()
    {
        parent::setUp();
        $this->_view = Zend_Layout::getMvcInstance()->getView();
    }

    /**
     * Verify returned value by the helper if parameters are/aren't set.
     */
    public function testInstance()
    {
        // helper should return instance if called without parameters
        $helper = $this->_view->dataGrid();
        $this->assertTrue(
            $helper instanceof Ui_View_Helper_DataGrid,
            "Expected helper returns instance if no parameteres provided."
        );

        // if parameteres are set, it should return data grid markup
        $result = $this->_view->dataGrid('namespace', $this->_minimumOptions);
        $this->assertTrue(
            is_string($result),
            "Expected helper returns string if parameteres are provided."
        );
    }

    /**
     * Verify (set|get)Options functionality.
     */
    public function testGetSetOptions()
    {
        $testOptions = array_merge(
            $this->_minimumOptions,
            array(
                'opt1'  => 123,
                'opt2'  => 'abc',
                'opt3'  => array(),
                'opt4'  => new stdClass,
                'opt5'  => false
            )
        );

        $helper = $this->_view->dataGrid();
        $helper->setOptions($testOptions);

        $this->assertSame(
            $testOptions,
            $helper->getOptions(),
            "Expected options returned by helper."
        );

        $this->assertTrue(
            $helper->getOption('opt4') instanceof stdClass,
            "Expected opt4 option value."
        );

        // verify url option must be set
        $helper->setOptions(array())
               ->setNamespace('namespace');
        try {
            $helper->render();
            $this->fail("Unexpected possibility to render helper without url option set.");
        } catch (Exception $e) {
            $this->assertSame(
                "You must set an url.",
                $e->getMessage(),
                "Expected exception message when url option not set."
            );
        }

        // verify column option must be set
        $helper->setOption('url', '/foo');
        try {
            $helper->render();
            $this->fail("Unexpected possibility to render helper without columns option set.");
        } catch (Exception $e) {
            $this->assertSame(
                "You must set columns in options.",
                $e->getMessage(),
                "Expected exception message when url option not set."
            );
        }
    }

    /**
     * Verify (set|get)Attrib functionality.
     */
    public function testGetSetAttribs()
    {
        $testOptions = array_merge(
            $this->_minimumOptions,
            array(
                'opt1'  => 123,
                'opt2'  => array(),
            )
        );

        $helper = $this->_view->dataGrid();
        $helper->setOptions($testOptions);

        $this->assertSame(
            null,
            $helper->getAttrib('test'),
            "Expected attribute returned by helper #1."
        );

        // add attribute
        $attr1 = new stdClass();
        $helper->setAttrib('attr1', $attr1);

        $this->assertSame(
            null,
            $helper->getAttrib('test'),
            "Expected attribute returned by helper #2."
        );
        $this->assertSame(
            $attr1,
            $helper->getAttrib('attr1'),
            "Expected attribute returned by helper #3."
        );

        // add attributes via setOptions
        $attribs = array(
            'attr2' => array(2, 3, 5, 7, 11),
            'attr3' => null
        );
        $options = array_merge(
            $this->_minimumOptions,
            array(
                'attribs' => $attribs,
                'foo'     => 'bar'
            )
        );
        $helper->setOptions($options);

        $this->assertSame(
            array(2, 3, 5, 7, 11),
            $helper->getAttrib('attr2'),
            "Expected attributes returned by helper #4."
        );
        $this->assertSame(
            null,
            $helper->getAttrib('attr1'),
            "Expected attributes returned by helper #5."
        );
    }

    /**
     * Verify data grid markup is rendered properly.
     */
    public function testRender()
    {
        $output = $this->_view->dataGrid(
            'grid.namespace',
            array(
                'gridLabel'     => 'test',
                'url'           => '/',
                'columns'       => array(
                    'col_1'     => array(
                        'label' => 'Column 1',
                        'order' => 10
                    ),
                    'col_2',
                    'col_3'
                ),
                'pageSize'      => 13,
                'attribs'       => array(
                    'disableSort'   => "['a', 'b', 'c']"
                )
            )
        );

        $expected = '<div class="data-grid test-grid">' . "\n"
                  . '<div url="/" jsId="grid.namespace.store" dojoType="dojox.data.QueryReadStore"></div>' . "\n"
                  . '<table rowsPerPage="13" dynamicHeight="1" selectionMode="none" disableFocus="1"'
                  . ' disableSort="[\'a\', \'b\', \'c\']" jsId="grid.namespace.instance" store="grid.namespace.store"'
                  . ' rowCount="13" keepRows="130" dojoType="p4cms.ui.grid.DataGrid">' . "\n"
                  . '<thead>' . "\n"
                  . '<tr>' . "\n"
                  . '<th field="col_2" width="" fixedWidth="" minWidth="">Col_2</th>' . "\n"
                  . '<th field="col_3" width="" fixedWidth="" minWidth="">Col_3</th>' . "\n"
                  . '<th field="col_1" width="" fixedWidth="" minWidth="">Column 1</th>' . "\n"
                  . '</tr>' . "\n"
                  . '</thead>' . "\n"
                  . '</table>' . "\n"
                  . '<div gridId="grid.namespace.instance" dojoType="p4cms.ui.grid.Footer">' . "\n"
                  . '</div>' . "\n"
                  . '</div>' . "\n";

        $this->assertSame($expected, $output, 'Expected matching output.');
    }

    /**
     * Verify scaleColumns() method functionality.
     */
    public function testScaleColumns()
    {
        $tests = array(
            array(
                'columns'   => array(
                    array(
                        'width'     => '10%'
                    ),
                    array(
                        'width'     => '20%'
                    ),
                    array(
                        'width'     => '30%'
                    ),
                ),
                'expectedWidths'  => array(50, 20, 30)
            ),
            array(
                'columns'   => array(
                    array(
                        'width'     => '40%'
                    ),
                    array(
                        'width'     => '60%'
                    ),
                    array(
                        'width'     => '80%'
                    ),
                ),
                'expectedWidths'  => array(23, 33, 44)
            ),
            array(
                'columns'   => array(
                    array(
                        'width'     => '40%',
                        'minWidth'  => '30%'
                    ),
                    array(
                        'width'     => '60%'
                    ),
                    array(
                        'width'     => '80%'
                    ),
                ),
                'expectedWidths'  => array(30, 30, 40)
            ),
            array(
                'columns'   => array(
                    array(
                        'fixedWidth'    => '20%'
                    ),
                    array(
                        'width'         => '40%'
                    ),
                    array(
                        'width'         => '40%'
                    ),
                    array(
                        'fixedWidth'    => '40%'
                    ),
                ),
                'expectedWidths'  => array(20, 20, 20, 40)
            ),
            array(
                'columns'   => array(
                    array(
                        'fixedWidth'    => '10%'
                    ),
                    array(
                        'minWidth'      => '30%'
                    ),
                    array(
                        'width'         => '50%'
                    ),
                    array(
                        'width'         => '60%'
                    ),
                ),
                'expectedWidths'  => array(10, 30, 28, 32)
            ),
            array(
                'columns'   => array(
                    array(
                        'fixedWidth'    => '25%'
                    ),
                    array(
                        'minWidth'      => '25%',
                    ),
                    array(
                        'width'         => '30%'
                    )
                ),
                'expectedWidths'  => array(25, 45, 30)
            ),
            array(
                'columns'   => array(
                    array(
                        'fixedWidth'    => '25%'
                    ),
                    array(
                        'minWidth'      => '65%',
                    ),
                    array(
                        'width'         => '30%'
                    )
                ),
                'expectedWidths'  => array(25, 65, 10)
            )
        );

        foreach ($tests as $test) {
            $output   = $this->_view->dataGrid(
                'test',
                array(
                    'url'           => '/',
                    'columns'       => $test['columns']
                )
            );

            // extract widths of columns from rendered output
            preg_match_all('/<th[\s\w\d="%]*width="(\d+)%" /', $output, $matches);

            // convert found width values in matches to integers
            foreach ($matches[1] as &$value) {
                $value = (int) $value;
            }

            // compare rendered column sized with expected values
            $this->assertSame($matches[1], $test['expectedWidths'], 'Expected column widths.');
        }
    }

    /**
     * Verify render topic is published and changes are captured.
     */
    public function testRenderTopic()
    {
        // subscribe to the topic
        P4Cms_PubSub::subscribe('test.grid.render',
            function(Ui_View_Helper_DataGrid $helper)
            {
                // set 'renderTopic' option
                $renderTopic = (int) $helper->getOption('renderTopic');
                $helper->setOption('renderTopic', ++$renderTopic);
            }
        );

        // set helper properties and render
        $helper = $this->_view->dataGrid();
        $helper->setNamespace('test.grid')
               ->setOptions($this->_minimumOptions);

        $helper->render();
        // ensure topic was published once
        $this->assertSame(
            1,
            $helper->getOption('renderTopic'),
            'Expected value of renderTopic option #1.'
        );

        // set helper properties via parameters passed to render method
        $helper->render('test.grid', $this->_minimumOptions);
        // ensure topic was published once (options are reset when setOptions() is called)
        $this->assertSame(
            1,
            $helper->getOption('renderTopic'),
            'Expected value of renderTopic option #2.'
        );
    }

    /**
     * Verify functionality of dojoData() method.
     */
    public function testDojoData()
    {
        $helper = $this->_view->dataGrid();
        $helper->setNamespace('test.grid');

        // prepare data for paginator
        $dataArray = array(
            array(
                'id'    => 1,
                'foo'   => 1,
                'bar'   => 2,
                'baz'   => 3
            ),
            array(
                'id'    => 2,
                'foo'   => 'a',
                'bar'   => 'b',
                'baz'   => 'c'
            ),
            array(
                'id'    => 3,
                'foo'   => 100,
                'bar'   => '200',
                'baz'   => false
            )
        );

        $adapter   = new Zend_Paginator_Adapter_Array($dataArray);
        $paginator = new Zend_Paginator($adapter);

        // capture output when dojoData output is printed out
        ob_start();
        print $helper->dojoData($paginator);
        $data      = ob_get_clean();
        $expected  = '{"identifier":"id","items":['
                   . '{"id":1,"foo":1,"bar":2,"baz":3},'
                   . '{"id":2,"foo":"a","bar":"b","baz":"c"},'
                   . '{"id":3,"foo":100,"bar":"200","baz":false}'
                   . '],"numRows":3}';

        $this->assertSame($expected, $data, "Expected dojo data output.");

        // verify rendering with itemCallback function present
        $itemCallback = function(array $model, Ui_View_Helper_DataGrid $helper)
        {
            // reverse items order
            return array_reverse($model);
        };

        ob_start();
        print $helper->dojoData($paginator, $itemCallback);
        $data      = ob_get_clean();
        $expected  = '{"identifier":"id","items":['
                   . '{"baz":3,"bar":2,"foo":1,"id":1},'
                   . '{"baz":"c","bar":"b","foo":"a","id":2},'
                   . '{"baz":false,"bar":"200","foo":100,"id":3}'
                   . '],"numRows":3}';
        $this->assertSame($expected, $data, "Expected dojo data output.");
    }

    /**
     * Verify data.item topic is published and changes are captured.
     */
    public function testDataItemTopic()
    {
        $helper = $this->_view->dataGrid();
        $helper->setNamespace('test.grid');

        // prepare data for paginator
        $dataArray = array(
            array(
                'foo'   => 1,
                'bar'   => 2,
            ),
            array(
                'foo'   => 'a',
                'bar'   => 'b',
            ),
            array(
                'foo'   => 100,
                'bar'   => true,
            )
        );

        // subscribe to the topic
        P4Cms_PubSub::subscribe('test.grid.data.item',
            function(array $item, array $model, Ui_View_Helper_DataGrid $helper)
            {
                // add new item to $item array
                $item['added'] = (string) $model['foo'] . '+' . (string) $model['bar'];
                return $item;
            }
        );

        $adapter   = new Zend_Paginator_Adapter_Array($dataArray);
        $paginator = new Zend_Paginator($adapter);

        ob_start();
        print $helper->dojoData($paginator, null, 'foo');
        $data      = ob_get_clean();
        $expected  = '{"identifier":"foo","items":['
                   . '{"foo":1,"bar":2,"added":"1+2"},'
                   . '{"foo":"a","bar":"b","added":"a+b"},'
                   . '{"foo":100,"bar":true,"added":"100+1"}'
                   . '],"numRows":3}';
        $this->assertSame($expected, $data, "Expected dojo data output.");
    }

    /**
     * Verify data topic is published and changes are captured.
     */
    public function testDataTopic()
    {
        $helper = $this->_view->dataGrid();
        $helper->setNamespace('test.grid');

        // prepare data for paginator
        $dataArray = array(
            array(
                'foo'   => 1,
                'bar'   => 2
            ),
            array(
                'foo'   => 'a',
                'bar'   => 'b'
            ),
            array(
                'foo'   => 100,
                'bar'   => true
            )
        );

        // subscribe to the topic
        P4Cms_PubSub::subscribe('test.grid.data',
            function(Zend_Dojo_Data $data, Ui_View_Helper_DataGrid $helper)
            {
                // add new item
                $data->addItem(
                    array(
                        'foo'   => 'addedFoo',
                        'bar'   => 'addedBar'
                    )
                );
            }
        );

        $adapter   = new Zend_Paginator_Adapter_Array($dataArray);
        $paginator = new Zend_Paginator($adapter);

        ob_start();
        print $helper->dojoData($paginator, null, 'foo');
        $data      = ob_get_clean();
        $expected  = '{"identifier":"foo","items":['
                   . '{"foo":1,"bar":2},'
                   . '{"foo":"a","bar":"b"},'
                   . '{"foo":100,"bar":true},'
                   . '{"foo":"addedFoo","bar":"addedBar"}'
                   . '],"numRows":3}';
        $this->assertSame($expected, $data, "Expected dojo data output.");
    }

    /**
     * Verify option attributes handling (ensure attributes are merged with defaults
     * and array-value attributes are encoded).
     */
    public function testAttributes()
    {
        $options = array_merge(
            $this->_minimumOptions,
            array(
                'gridLabel'     => 'test',
                'attribs'       => array(
                    'attr1'     => array('a', 'b', 'c'),
                    'attr2'     => 'foo',
                    'attr3'     => array('x' => 1, 'y' => 2)
                )
            )
        );

        $output   = $this->_view->dataGrid('namespace', $options);
        $quot     = "&quot;";
        $expected = '<div class="data-grid test-grid">' . "\n"
                  . '<div url="/" jsId="namespace.store" dojoType="dojox.data.QueryReadStore"></div>' . "\n"
                  . '<table rowsPerPage="25" dynamicHeight="1" selectionMode="none" disableFocus="1"'
                  . ' disableSort="[&quot;_item&quot;]"'
                  . ' attr1="[&quot;a&quot;,&quot;b&quot;,&quot;c&quot;]"'
                  . ' attr2="foo"'
                  . ' attr3="{&quot;x&quot;:1,&quot;y&quot;:2}"'
                  . ' jsId="namespace.instance" store="namespace.store"'
                  . ' rowCount="25" keepRows="250" dojoType="p4cms.ui.grid.DataGrid">' . "\n"
                  . '<thead>' . "\n"
                  . '<tr>' . "\n"
                  . '</tr>' . "\n"
                  . '</thead>' . "\n"
                  . '</table>' . "\n"
                  . '<div gridId="namespace.instance" dojoType="p4cms.ui.grid.Footer">' . "\n"
                  . '</div>' . "\n"
                  . '</div>' . "\n";

        $this->assertSame($expected, $output, 'Expected matching output.');
    }

    /**
     * Test addColumn() method functionality.
     */
    public function testAddColumn()
    {
        $helper = $this->_view->dataGrid();

        // verify no columns are present
        $this->assertSame(
            null,
            $helper->getOption('columns'),
            "Expected columns option value #1."
        );

        // add foo column without attributes
        $helper->addColumn('foo');

        $expected = array(
            'foo'   => array()
        );
        $this->assertSame(
            $expected,
            $helper->getOption('columns'),
            "Expected columns option value #2."
        );

        // add bar column with attributes
        $helper->addColumn('bar', array('a' => 'x'));
        $expected['bar'] = array('a' => 'x');
        $this->assertSame(
            $expected,
            $helper->getOption('columns'),
            "Expected columns option value #2."
        );

        // add baz column and with disabled sorting option
        $helper->addColumn('baz', array(), false);
        $expected['baz'] = array();
        $this->assertSame(
            $expected,
            $helper->getOption('columns'),
            "Expected columns option value #3."
        );

        // verify baz is in disableSort list
        $attribs = $helper->getOption('attribs');
        $this->assertTrue(
            in_array('baz', $attribs['disableSort']),
            "Expected baz column is not sortable"
        );

        // attempt to add already existing column should throw an exception
        try {
            $helper->addColumn('bar');
            $this->fail("Unexpected possibility to add an already existing column.");
        } catch (Zend_Dojo_View_Exception $e) {
            $this->assertSame(
                "Cannot add column: field bar already exists.",
                $e->getMessage(),
                "Expected exception message"
            );
        }
    }

    /**
     * Test adding buttons to the footer via addButton() method.
     */
    public function testAddButton()
    {
        $helper = $this->_view->dataGrid();
        $helper->addButton('a');
        $helper->addButton('c', array('x', 'y', 'z'));
        $helper->addButton('b');

        $expectedFooterOption = array(
            'buttons' => array(
                'a' => array(),
                'c' => array('x', 'y', 'z'),
                'b' => array()
            )
        );

        $this->assertSame(
            $expectedFooterOption,
            $helper->getOption('footer'),
            "Expected grid footer option."
        );
    }

    /**
     * Verify that buttons will be rendered in the given order.
     */
    public function testButtonsOrder()
    {
        $helper = $this->_view->dataGrid();
        $tests  = array(
            array(
                'buttons' => array(
                    'a' => null,
                    'b' => null,
                    'c' => null
                ),
                'expectedOrder' => array('a', 'b', 'c')
            ),
            array(
                'buttons' => array(
                    'a' => 100,
                    'b' => null,
                    'c' => 10
                ),
                'expectedOrder' => array('b', 'c', 'a')
            ),
            array(
                'buttons' => array(
                    'a' => 100,
                    'b' => 10,
                    'c' => 1
                ),
                'expectedOrder' => array('c', 'b', 'a')
            ),
            array(
                'buttons' => array(
                    'a' => -100,
                    'b' => -10,
                    'c' => -1
                ),
                'expectedOrder' => array('a', 'b', 'c')
            ),
            array(
                'buttons' => array(
                    'a' => 70,
                    'b' => 30,
                    'c' => -50,
                    'd' => 50,
                    'e' => -20,
                    'f' => 20
                ),
                'expectedOrder' => array('c', 'e', 'f', 'b', 'd', 'a')
            ),
            array(
                'buttons' => array(
                    'but1' => 50,
                    'but2' => 10,
                    'but3' => 100,
                    'but4' => 1,
                    'but5' => 20
                ),
                'expectedOrder' => array('but4', 'but2', 'but5', 'but1', 'but3')
            )
        );

        // run tests
        foreach ($tests as $test) {
            $helper->setOptions($this->_minimumOptions);

            // add buttons
            foreach ($test['buttons'] as $label => $order) {
                $options = $order !== null ? array('order' => $order) : array();
                $helper->addButton($label, $options);
            }

            // extract buttons from the rendered output
            $output = $helper->render('test');
            preg_match_all('/<button[^>]+>(\w+)<\/button>/', $output, $matches);

            $this->assertSame(
                $test['expectedOrder'],
                $matches[1],
                "Expected buttons order for " . print_r($test['buttons'], true)
            );
        }
    }
}
