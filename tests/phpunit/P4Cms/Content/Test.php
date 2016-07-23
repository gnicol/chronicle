<?php
/**
 * Test the content model.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Content_Test extends TestCase
{
    /**
     * Set core modules path so that site load can find modules.
     * Load test sites and set the sites path to the test sites.
     */
    public function setUp()
    {
        parent::setUp();
        P4Cms_Module::reset();
        P4Cms_Module::setCoreModulesPath(TEST_ASSETS_PATH . '/core-modules');
        P4Cms_Module::addPackagesPath(TEST_ASSETS_PATH . '/sites/test/modules');

        $adapter = new P4Cms_Record_Adapter;
        $adapter->setConnection($this->p4)
                ->setBasePath("//depot");
        P4Cms_Record::setDefaultAdapter($adapter);

        P4_Connection::setDefaultConnection($this->p4);
    }

    /**
     * Cleanup.
     */
    public function tearDown()
    {
        P4Cms_Module::reset();
        P4Cms_Record::clearDefaultAdapter();
        P4_Connection::clearDefaultConnection();
        P4Cms_Content::setUriCallback(null);
        P4Cms_Content::clearTypeCache();

        parent::tearDown();
    }

    /**
     * Test hasValidContentType().
     */
    public function _testHasValidContentType()
    {
        $entry = new P4Cms_Content;

        $this->assertFalse($entry->hasValidContentType(), 'Expected new object to fail');

        $entry->setContentType('blahadasd');
        $this->assertFalse($entry->hasValidContentType(), 'Expected made up type to be false');

        P4Cms_Content_Type::store('testType');
        $entry = new P4Cms_Content;
        $entry->setContentType('testType');
        $this->assertTrue($entry->hasValidContentType(), 'Expected valid type to work');

        P4Cms_Content_Type::fetch('testType')->delete();
        $this->assertTrue($entry->hasValidContentType(), 'Expected delete type to be valid');
    }

    /**
     * Test the behaviour of bad uri callbacks.
     */
    public function _testBadUriCallback()
    {
        $tests = array(
            array(
                'title' => __LINE__ . ' int',
                'value' => 12
            ),
            array(
                'title' => __LINE__ . ' object',
                'value' => new Exception
            ),
            array(
                'title' => __LINE__ . ' bool',
                'value' => true
            ),
            array(
                'title' => __LINE__ . ' random string',
                'value' => 'asdagafadfadasasdafew'
            ),
        );

        foreach ($tests as $test) {
            extract($test);

            try {
                P4Cms_Content::setUriCallback($value);
                $this->fail($title . ': expected failure');
            } catch (InvalidArgumentException $e) {

            }
        }
    }

    /**
     * Test getting/setting the owner of a content entry.
     */
    public function _testGetSetOwner()
    {
        $content = new P4Cms_Content;
        $content->setId('test');

        $this->assertSame(
            null,
            $content->getOwner(),
            "Expected null value for owner if no owner has been set."
        );

        // set owner
        $content->setOwner('foo')->save();

        $this->assertSame(
            'foo',
            P4Cms_Content::fetch('test')->getOwner(),
            "Expected 'foo' owner of the content entry."
        );

        // add a rev as another user
        $user = new P4Cms_User;
        $user->setId('joe')
             ->setEmail('bar@domain.com')
             ->setFullName('Joe Blow')
             ->save();

        $p4 = clone $this->p4;
        $p4->setUser('joe');
        $adapter = P4Cms_Record::getDefaultAdapter();
        $adapter->setConnection($p4);

        $content = P4Cms_Content::store(
            array('id' => 'test', 'contentOwner' => 'foo'), $adapter
        );

        $this->assertSame(
            'foo',
            $content->getOwner(),
            "Expected 'foo' as content entry owner."
        );

        $content = P4Cms_Content::store(
            array('id' => 'test'), $adapter
        );

        $this->assertSame(
            null,
            $content->getOwner(),
            "Expected no owner."
        );

        // set owner by P4Cms_User instance
        $content = P4Cms_Content::store(
            array('id' => 'test', 'contentOwner' => $user)
        );
        $this->assertSame(
            'joe',
            $content->getOwner(),
            "Expected 'joe' owner."
        );

        // test exception
        try {
            $content->setOwner(array('user' => 'bar'));
            $this->fail("Unexpected success setting invalid owner of content");
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true);
        }

        return;
    }

    /**
     * Test getting the excerpt.
     */
    public function testGetExcerpt()
    {
        // prepare content to test
        $contentWithNoExcerpt = new P4Cms_Content;
        $contentWithNoExcerpt->setContentType('press-release')
                             ->setValue('title',    'A basic title')
                             ->setValue('contact',  'Contact me here.')
                             ->setValue(
                                'body',
                                'The&nbsp;basic <b class="emphasis">page</b> content.'
                                . "\nHere is an extra line to pad out the length a bit."
                                . "\nHere is another extra line to pad out the length a bit."
                             );

        $contentWithAnExcerpt = new P4Cms_Content;
        $contentWithAnExcerpt->setContentType('blog-post')
                             ->setValue('title',    'The blog post title')
                             ->setValue(
                                'body',
                                'The body of the blog post.'
                                . "\nHere is an extra line to pad out the length a bit."
                                . "\nHere is another extra line to pad out the length a bit."
                                . "\nHere is another extra extra line to pad out the length a bit."
                             )
                             ->setValue('excerpt',  'My noticeably wee excerpt.');

        $contentWithNoBody = new P4Cms_Content;
        $contentWithNoBody->setContentType('image');

        // as we test with an entity whose text representation could be a UTF8 character,
        // we compute its text value here so the test will work on platforms with differing config.
        $filter = new P4Cms_Filter_HtmlEntityDecode;
        $textNbsp = $filter->filter('&nbsp;');

        // tests with no arguments at all
        $this->assertSame(
            "The{$textNbsp}basic page content."
            . " Here is an extra line to pad out the length a bit."
            . " Here is another extra",
            $contentWithNoExcerpt->getExcerpt(),
            'Expected excerpt with no args/no excerpt content.'
        );
        $this->assertSame(
            'My noticeably wee excerpt.',
            $contentWithAnExcerpt->getExcerpt(),
            'Expected excerpt with no args/an excerpt content.'
        );
        $this->assertSame(
            null,
            $contentWithNoBody->getExcerpt(),
            'Expected excerpt with no args/no body content.'
        );

        // setup the argument-passing cases
        $tests = array(
            // initial tests
            array(
                'label'     => __LINE__ .':  no Excerpt, 0 length, no options',
                'content'   => $contentWithNoExcerpt,
                'length'    => 0,
                'options'   => array(),
                'expected'  => "The{$textNbsp}basic page content."
                            .  " Here is an extra line to pad out the length a bit."
                            .  " Here is another extra line to pad out the length a bit."
            ),
            array(
                'label'     => __LINE__ .':  Excerpt, 0 length, no options',
                'content'   => $contentWithAnExcerpt,
                'length'    => 0,
                'options'   => array(),
                'expected'  => "My noticeably wee excerpt."
            ),

            // length tests
            array(
                'label'     => __LINE__ .':  no Excerpt, 10 length, no options',
                'content'   => $contentWithNoExcerpt,
                'length'    => 10,
                'options'   => array(),
                'expected'  => "The{$textNbsp}basic"
            ),
            array(
                'label'     => __LINE__ .':  Excerpt, 10 length, no options',
                'content'   => $contentWithAnExcerpt,
                'length'    => 10,
                'options'   => array(),
                'expected'  => "My"
            ),

            // filterHtml tests
            array(
                'label'     => __LINE__ .':  no Excerpt, 10 length, filterHtml true',
                'content'   => $contentWithNoExcerpt,
                'length'    => 10,
                'options'   => array(
                    'filterHtml'    => true,
                ),
                'expected'  => "The{$textNbsp}basic"
            ),
            array(
                'label'     => __LINE__ .':  no Excerpt, 10 length, filterHtml false',
                'content'   => $contentWithNoExcerpt,
                'length'    => 10,
                'options'   => array(
                    'filterHtml'    => false,
                ),
                'expected'  => "The&nbsp;b"
            ),

            // fullExcerpt tests
            array(
                'label'     => __LINE__ .':  Excerpt, 10 length, fullExcerpt true',
                'content'   => $contentWithAnExcerpt,
                'length'    => 10,
                'options'   => array(
                    'fullExcerpt'   => true,
                ),
                'expected'  => "My noticeably wee excerpt."
            ),
            array(
                'label'     => __LINE__ .':  Excerpt, 10 length, fullExcerpt false',
                'content'   => $contentWithAnExcerpt,
                'length'    => 10,
                'options'   => array(
                    'fullExcerpt'   => false,
                ),
                'expected'  => "My"
            ),

            // keepEntities tests
            array(
                'label'     => __LINE__ .':  no Excerpt, 10 length, keepEntities true',
                'content'   => $contentWithNoExcerpt,
                'length'    => 10,
                'options'   => array(
                    'keepEntities'  => true,
                ),
                'expected'  => "The&nbsp;b"
            ),
            array(
                'label'     => __LINE__ .':  no Excerpt, 10 length, keepEntities false',
                'content'   => $contentWithNoExcerpt,
                'length'    => 10,
                'options'   => array(
                    'keepEntities'  => false,
                ),
                'expected'  => "The{$textNbsp}basic"
            ),

            // excerptField tests
            array(
                'label'     => __LINE__ .':  no Excerpt, 10 length, excerptField contact',
                'content'   => $contentWithNoExcerpt,
                'length'    => 10,
                'options'   => array(
                    'excerptField'  => 'contact',
                ),
                'expected'  => "Contact me"
            ),
            array(
                'label'     => __LINE__ .':  no Excerpt, 10 length, excerptField bogus',
                'content'   => $contentWithNoExcerpt,
                'length'    => 10,
                'options'   => array(
                    'excerptField'  => 'bogus',
                ),
                'expected'  => "The{$textNbsp}basic"
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            $excerpt = $test['content']->getExcerpt($test['length'], $test['options']);
            $this->assertSame($test['expected'], $excerpt, "$label - expected excerpt");
        }
    }

    /**
     * Test macro expansion
     */
    public function _testMacroExpansion()
    {
        // prepare a macro handler
        P4Cms_PubSub::subscribe(
            P4Cms_Filter_Macro::TOPIC . 'contentExpansionTest',
            function($params, $body, $context)
            {
                return "expanded";
            }
        );

        // prepare a content type that has macro expansion enabled
        $type = new P4Cms_Content_Type;
        $type->setId('macros-on')
             ->setElements(
                <<<EOE
[element]
type = text
options.label = 'Macros On'
options.macros.enabled = true
EOE
             )
             ->save();
        // create a content object with a macro
        $content = new P4Cms_Content;
        $content->setContentType($type)
                ->setValue('element', 'The test {{contentExpansionTest}} macro.');

        // test expanded value
        $value = $content->getExpandedValue('element');
        $this->assertSame('The test expanded macro.', $value, 'The expected value.');
    }
}