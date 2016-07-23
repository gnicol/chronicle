<?php
/**
 * Test the opened model.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Content_OpenedTest extends TestCase
{
    protected   $_adapter   = null;
    
    /**
     * Set the default storage adapter to use.
     */
    public function setUp()
    {
        parent::setUp();

        $adapter = new P4Cms_Record_Adapter;
        $adapter->setConnection($this->p4)
                ->setBasePath("//depot");
        
        $this->_adapter = $adapter;
    }

    /**
     * Test the various set start time method
     */
    public function testSetStartTime()
    {
        $record = new P4Cms_Content_Opened;
        $record->setAdapter($this->_adapter)
               ->setId('1');

        $this->assertSame(array(), $record->getUsers());
        
        // set just the start time and save
        $record->setUserStartTime('userId')->save();
        $record = P4Cms_Content_Opened::fetch('1', $this->_adapter);
        
        // we don't expect it to show in getUsers as it hasn't ping'ed
        $this->assertSame(array(), $record->getUsers(), 'expected empty with no ping');
        $startTime = $record->getValue("userId-startTime");
        $this->assertTrue(
            $startTime == (int)$startTime && $startTime > time() - 5,
            'Expected a valid start time to be set in raw form'
        );

        // add in a ping time and verify all is well
        $record->setUserPingTime('userId')->save();
        $record   = P4Cms_Content_Opened::fetch('1', $this->_adapter);
        $users    = $record->getUsers();
        $startTime = $users['userId']['startTime'];
        $this->assertTrue(
            $startTime == (int)$startTime && $startTime > time() - 5,
            'Expected a valid start time to be set'
        );
        unset($users['userId']['pingTime']);
        unset($users['userId']['startTime']);
        $this->assertSame(
            array('userId' => array('editTime' => null)), 
            $users, 
            'expected matching data'
        );
    }

    /**
     * Test the various set ping time method
     */
    public function testSetPingTime()
    {
        $record = new P4Cms_Content_Opened;
        $record->setAdapter($this->_adapter)
               ->setId('1');

        $this->assertSame(array(), $record->getUsers());
        
        // set just the ping time and save
        $record->setUserPingTime('userId')->save();
        $record = P4Cms_Content_Opened::fetch('1', $this->_adapter);
        
        // we don't expect it to show in getUsers as it isn't started
        $this->assertSame(array(), $record->getUsers(), 'expected empty with just ping');
        $pingTime = $record->getValue("userId-pingTime");
        $this->assertTrue(
            $pingTime == (int)$pingTime && $pingTime > time() - 5,
            'Expected a valid ping time to be set in raw form'
        );

        // add in a start time and verify all is well
        $record->setUserStartTime('userId')->save();
        $record   = P4Cms_Content_Opened::fetch('1', $this->_adapter);
        $users    = $record->getUsers();
        $pingTime = $users['userId']['pingTime'];
        $this->assertTrue(
            $pingTime == (int)$pingTime && $pingTime > time() - 5,
            'Expected a valid ping time to be set'
        );
        unset($users['userId']['pingTime']);
        unset($users['userId']['startTime']);
        $this->assertSame(
            array('userId' => array('editTime' => null)), 
            $users, 
            'expected matching data'
        );
        
        // verify setting our ping time to an old value drops us
        $record->setUserPingTime('userId', time() - 5*60)->save();
        $record = P4Cms_Content_Opened::fetch('1', $this->_adapter);
        $this->assertSame(array(), $record->getUsers(), 'expected empty with old ping');
    }
    
    /**
     * Test the various set edit time method
     */
    public function testSetEditTime()
    {
        $record = new P4Cms_Content_Opened;
        $record->setAdapter($this->_adapter)
               ->setId('1');

        $this->assertSame(array(), $record->getUsers());
        
        // set just the edit time and save
        $record->setUserEditTime('userId')->save();
        $record = P4Cms_Content_Opened::fetch('1', $this->_adapter);
        
        // we don't expect it to show in getUsers as it isn't started
        $this->assertSame(array(), $record->getUsers(), 'expected empty with just edit');
        $editTime = $record->getValue("userId-editTime");
        $this->assertTrue(
            $editTime == (int)$editTime && $editTime > time() - 5,
            'Expected a valid edit time to be set in raw form'
        );

        // add in a start and ping time and verify all is well
        $record->setUserStartTime('userId')->setUserPingTime('userId')->save();
        $record   = P4Cms_Content_Opened::fetch('1', $this->_adapter);
        $users    = $record->getUsers();
        $editTime = $users['userId']['editTime'];
        $this->assertTrue(
            $editTime == (int)$editTime && $editTime > time() - 5,
            'Expected a valid edit time to be set'
        );
        unset($users['userId']['pingTime']);
        unset($users['userId']['editTime']);
        unset($users['userId']['startTime']);
        $this->assertSame(
            array('userId' => array()), 
            $users, 
            'expected matching data'
        );
    }
}