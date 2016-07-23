<?php
/**
 * Test varous rollback situations on the record.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Record_RollbackTest extends TestCase
{
    /**
     * Set the default storage adapter to use.
     */
    public function setUp()
    {
        parent::setUp();

        $adapter = new P4Cms_Record_Adapter;
        $adapter->setConnection($this->p4)
                ->setBasePath("//depot");
        P4Cms_Record::setDefaultAdapter($adapter);
    }

    /**
     * Test a basic rollback
     */
    public function testRollback()
    {
        $record = new P4Cms_Record_Implementation;
        $record->setId(1)
               ->setTitle('test-title0')
               ->setContent('test-content0')
               ->save();

        $this->assertSame(
            '1',
            $record->getId(),
            'expected matching Id'
        );

        for ($i=1; $i <= 10; $i++) {
            $record->setId(1)
                   ->setTitle('test-title'.$i)
                   ->setContent('test-content'.$i)
                   ->save();
        }

        $this->assertSame(
            11,
            count($record->toP4File()->getChanges()),
            'expected matching number of changes'
        );

        $this->assertSame(
            'test-title1',
            P4Cms_Record_Implementation::fetch('1#2')->getTitle(),
            'expected matching title pre rollback check'
        );
        $this->assertSame(
            'test-content1',
            P4Cms_Record_Implementation::fetch('1#2')->getContent(),
            'expected matching content pre rollback check'
        );

        $record = P4Cms_Record_Implementation::fetch('1#2');

        $this->assertSame(
            '1',
            $record->getId(),
            'expected mathing ID pre rollback'
        );

        $record->save('rollin rollin rollin');

        $this->assertSame(
            '1',
            $record->getId(),
            'expected updated record post rollback'
        );
        $this->assertSame(
            'test-title1',
            P4Cms_Record_Implementation::fetch('1')->getTitle(),
            'expected matching title post rollback'
        );
        $this->assertSame(
            'test-content1',
            P4Cms_Record_Implementation::fetch('1')->getContent(),
            'expected matching content post rollback'
        );
    }

    /**
     * Test rolling back to a non-deleted version when head is deleted.
     */
    public function testRollbackWithDeletedHead()
    {
        $record = new P4Cms_Record_Implementation;
        $record->setId(1)
               ->setTitle('title1')
               ->setContent('test-content1')
               ->save();
        $record->setTitle('title2')
               ->setContent('test-content2')
               ->save();
        $record->delete();

        $record = P4Cms_Record_Implementation::fetch('1#1');
        $record->save();

        $record = P4Cms_Record_Implementation::fetch('1');

        $this->assertSame(
            'title1',
            $record->getTitle(),
            'expected matching title post rollback'
        );
        $this->assertSame(
            'test-content1',
            $record->getContent(),
            'expected matching content post rollback'
        );
    }

    /**
     * Test rolling back to a deleted version when head is deleted.
     */
    public function testRollbackToDeletedWithDeletedHead()
    {
        $record = new P4Cms_Record_Implementation;
        $record->setId(1)
               ->setTitle('title1')
               ->setContent('test-content1')
               ->save();
        $record->setTitle('title2')
               ->setContent('test-content2')
               ->save();
        $record->delete();
        $record->setTitle('title4')
               ->setContent('test-content4')
               ->save();
        $record->delete();

        $record = P4Cms_Record_Implementation::fetch('1#3', array('includeDeleted' => true));
        $record->save();

        $record = P4Cms_Record_Implementation::fetch('1');

        $this->assertSame(
            'title2',
            $record->getTitle(),
            'expected matching title post rollback'
        );
        $this->assertSame(
            'test-content2',
            $record->getContent(),
            'expected matching content post rollback'
        );
    }

    /**
     * Test rolling back to a deleted version when head is non-deleted.
     */
    public function testRollbackToDeletedWithGoodHead()
    {
        $record = new P4Cms_Record_Implementation;
        $record->setId(1)
               ->setTitle('title1')
               ->setContent('test-content1')
               ->save();
        $record->delete();
        $record->setTitle('title2')
               ->setContent('test-content2')
               ->save();

        $record = P4Cms_Record_Implementation::fetch('1#2', array('includeDeleted' => true));
        $record->save();

        $record = P4Cms_Record_Implementation::fetch('1');
        $this->assertSame(
            'title1',
            $record->getTitle(),
            'expected matching title post rollback'
        );
        $this->assertSame(
            'test-content1',
            $record->getContent(),
            'expected matching content post rollback'
        );
    }
}