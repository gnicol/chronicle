<?php
/**
 * Test methods for the category title to id filter.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Filter_DefaultStripTagsTest extends TestCase
{
    /**
     * Test setting filter options.
     */
    public function testConstructor()
    {
        // verify that default allowed tags are set
        $filter             = new P4Cms_Filter_DefaultStripTags;
        $defaultTags        = $filter->getTagsAllowed();
        $defaultAttributes  = $filter->getAttributesAllowed();
        $this->assertTrue(
            count($defaultTags) > 0,
            "Expected non-empty allowed tags after default instantiation."
        );

        // verify that tags passed in options are appended
        // (i.e. default allowed tags are not clobbered)
        $options = array(
            'allowTags' => array(
                'tagA',
                'tagB' => array(
                    'attrB1',
                    'attrB2',
                    'attrB3'
                )
            )
        );
        
        $filter = new P4Cms_Filter_DefaultStripTags($options);
        $this->assertSame(
            count($defaultTags) + 2,
            count($filter->getTagsAllowed()),
            "Expected number of allowed tags."
        );
        $this->assertTrue(
            array_key_exists('taga', $filter->getTagsAllowed()),
            "Expected existence of extra tag A."
        );
        $this->assertTrue(
            array_key_exists('tagb', $filter->getTagsAllowed()),
            "Expected existence of extra tag B."
        );
        
        // verify that attributes passed in options are appended
        // (i.e. default allowed attributes are not clobbered)
        $options['allowAttribs'] = array(
            'Attr1',
            'Attr2',
            'Attr3'
        );
        
        $filter = new P4Cms_Filter_DefaultStripTags($options);
        $this->assertSame(
            count($defaultAttributes) + 3,
            count($filter->getAttributesAllowed()),
            "Expected number of allowed attributes."
        );
        $this->assertTrue(
            array_key_exists('attr1', $filter->getAttributesAllowed()),
            "Expected existence of attribute attr1."
        );
        $this->assertTrue(
            array_key_exists('attr2', $filter->getAttributesAllowed()),
            "Expected existence of attribute attr2."
        );
        $this->assertTrue(
            array_key_exists('attr3', $filter->getAttributesAllowed()),
            "Expected existence of attribute attr3."
        );
    }

    /**
     * Test removing tags
     */
    public function testRemoveTags()
    {
        // cache default attributes/tags
        $filter                     = new P4Cms_Filter_DefaultStripTags;
        $defaultAllowedTags         = $filter->getTagsAllowed();
        $defaultAllowedAttributes   = $filter->getAttributesAllowed();

        // add tags 1,2
        $filter->setTagsAllowed(
            array(
                'testTag1',
                'testTag2'
            )
        );
        
        // verify tags are there
        $this->assertTrue(
            array_key_exists('testtag1', $filter->getTagsAllowed()),
            "Expected existence of testTag1."
        );
        $this->assertTrue(
            array_key_exists('testtag2', $filter->getTagsAllowed()),
            "Expected existence of testTag2."
        );

        // remove testTag2
        $filter->removeTags('testTag2');
        
        $this->assertTrue(
            array_key_exists('testtag1', $filter->getTagsAllowed()),
            "Expected existence of testTag1."
        );
        $this->assertFalse(
            array_key_exists('testtag2', $filter->getTagsAllowed()),
            "Unexpected existence of testTag2."
        );

        // add tags 3 & 4
        $filter->setTagsAllowed(array('testTag3', 'testTag4'));
        $this->assertTrue(
            array_key_exists('testtag1', $filter->getTagsAllowed()),
            "Expected existence of testTag1."
        );
        $this->assertTrue(
            array_key_exists('testtag3', $filter->getTagsAllowed()),
            "Expected existence of testTag3."
        );
        $this->assertTrue(
            array_key_exists('testtag4', $filter->getTagsAllowed()),
            "Expected existence of testTag4."
        );
        
        // remove tags 1 & 4
        $filter->removeTags(array('testTag1', 'testTAG4'));

        $this->assertFalse(
            array_key_exists('testtag1', $filter->getTagsAllowed()),
            "Unexpected existence of testTag1."
        );
        $this->assertFalse(
            array_key_exists('testtag4', $filter->getTagsAllowed()),
            "Unexpected existence of testTag4."
        );
        $this->assertTrue(
            array_key_exists('testtag3', $filter->getTagsAllowed()),
            "Expected existence of testTag3."
        );

        // remove tag 3 and verify original allowed tags were not affected
        $filter->removeTags('testtag3');

        $this->assertSame(
            $defaultAllowedTags,
            $filter->getTagsAllowed(),
            "Expected default tags were not affected."
        );
        
        $this->assertSame(
            $defaultAllowedAttributes,
            $filter->getAttributesAllowed(),
            "Expected default attributes were not affected."
        );
    }

    /**
     * Test removing attributes
     */
    public function testRemovingAttributes()
    {
        // cache default attributes/tags
        $filter                     = new P4Cms_Filter_DefaultStripTags;
        $defaultAllowedAttributes   = $filter->getAttributesAllowed();

        // add custom attributes
        $filter->setAttributesAllowed(
            array(
                'testAttr1',
                'testAttr2',
                'attr2'
            )
        );
        
        // add custom tags with attributes
        $filter->setTagsAllowed(
            array(
                'testTag1' => array(
                    'attr1',
                    'attr2',  // pointless, however possible
                    'attr3',
                    'attr4'
                ),
                'testTag2',
                'testTag3' => array(
                    'attr1',
                    'attr5'
                )
            )
        );

        $filter->removeAttributes(array('testTag1' => array('attr1', 'attr4'), 'testTag3' => 'attr5'));

        $tagsAllowed = $filter->getTagsAllowed();
        $this->assertFalse(
            array_key_exists('attr1', $tagsAllowed['testtag1']),
            "Unexpected existence of attr1 in testtag1."
        );
        $this->assertFalse(
            array_key_exists('attr4', $tagsAllowed['testtag1']),
            "Unexpected existence of attr4 in testtag1."
        );
        $this->assertFalse(
            array_key_exists('attr5', $tagsAllowed['testtag3']),
            "Unexpected existence of attr5 in testtag3."
        );
        $this->assertTrue(
            array_key_exists('attr1', $tagsAllowed['testtag3']),
            "Unexpected existence of attr1 in testtag3."
        );

        $filter->setTagsAllowed(
            array(
                'testTag1' => array(
                    'attr1',
                    'attr2', 
                    'attr3',
                    'attr4'
                ),
                'testTag2',
                'testTag3' => array(
                    'attr1',
                    'attr5'
                )
            )
        );

        // remove attr3
        $filter->removeAttributes('attr3');

        // verify attribute is gone from allowed attributes
        $this->assertFalse(
            array_key_exists('attr3', $filter->getAttributesAllowed()),
            "Unexpected existence of attr3."
        );

        // verify attribute is gone from allowed tag attributes
        $tagsAllowed = $filter->getTagsAllowed();
        $this->assertFalse(
            array_key_exists('attr3', $tagsAllowed['testtag1']),
            "Unexpected existence of attr3 in testtag1."
        );

        // remove attr by using array notation
        $filter->removeAttributes(array('attr4', 'attr5', 'no-attr'));        
        $tagsAllowed = $filter->getTagsAllowed();
        $this->assertFalse(
            array_key_exists('attr4', $tagsAllowed['testtag1']),
            "Unexpected existence of attr4 in testtag1."
        );
        $this->assertFalse(
            array_key_exists('attr5', $tagsAllowed['testtag3']),
            "Unexpected existence of attr5 in testtag3."
        );

        // remove attr1 only from testtag1
        $filter->removeAttributes(array('testTag1' => 'attr1'));

        $tagsAllowed = $filter->getTagsAllowed();
        $this->assertFalse(
            array_key_exists('attr1', $tagsAllowed['testtag1']),
            "Unexpected existence of attr1 in testtag1."
        );        
        $this->assertTrue(
            array_key_exists('attr1', $tagsAllowed['testtag3']),
            "Unexpected existence of attr1 in testtag3."
        );

        // remove attr2
        $filter->removeAttributes(array('attr2'));
        $this->assertFalse(
            array_key_exists('attr2', $filter->getAttributesAllowed()),
            "Unexpected existence of attr2."
        );
        $tagsAllowed = $filter->getTagsAllowed();
        $this->assertFalse(
            array_key_exists('attr2', $tagsAllowed['testtag1']),
            "Unexpected existence of attr2 in testtag1."
        );

        // remove testAttr1 and verify default attributes were not affected
        $filter->removeAttributes('testAttr1');
        $this->assertSame(
            count($defaultAllowedAttributes) + 1, // default + testAttr2
            count($filter->getAttributesAllowed()),
            "Expected number of attributes allowed"
        );
    }
}
