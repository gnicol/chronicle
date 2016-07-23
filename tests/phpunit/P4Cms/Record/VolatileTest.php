<?php
/**
 * Test methods for the volatile record class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Record_VolatileTest extends TestCase
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
     * Test save + fetch + delete
     */
    public function testSaveFetchDelete()
    {
        $record = new P4Cms_Record_Volatile;
        $record->setAdapter($this->_adapter)
               ->setId('test-record')
               ->setValue('foo', 'bar')
               ->setValue('biz', 'baz')
               ->save();
        
        $record = P4Cms_Record_Volatile::fetch('test-record', $this->_adapter);
        $this->assertSame(
            array('biz' => 'baz', 'foo' => 'bar'),
            $record->getValues()
        );
        
        $record->delete();
        $this->assertFalse(P4Cms_Record_Volatile::exists('test-record', $this->_adapter));
    }
    
    /**
     * Test fetch non-existant
     * 
     * @expectedException P4Cms_Record_NotFoundException
     */
    public function testFetchNonExistant()
    {
        P4Cms_Record_Volatile::fetch('test-record', $this->_adapter);
    }
    
    /**
     * Test basic operation when masquerading as another client.
     */
    public function testMasquerading()
    {
        // set adapter storage path to current client root to test how depot 
        // paths are composed (path should be converted to depot syntax)
        $this->_adapter->setBasePath('//' . $this->p4->getClient());
        
        // make a client to masquerade as.
        $client = new P4_Client;
        $client->setId('other-client')
               ->setView(array('//depot/... //other-client/...'))
               ->setRoot(TEST_DATA_PATH)
               ->setHost('woozle.wobble.com')
               ->setOwner('otheruser')
               ->save();

        // should not be able to see record pre-save.
        $this->assertFalse(P4Cms_Record_Volatile::exists('test-record', $this->_adapter));
        $this->assertFalse(P4Cms_Record_Volatile::exists('test-record', $this->_adapter, $client));
        
        $record = new P4Cms_Record_Volatile;
        $record->setAdapter($this->_adapter)
               ->setClientMasquerade($client)
               ->setId('test-record')
               ->setValue('foo', 'bar')
               ->setValue('biz', 'baz')
               ->save(); 

        $record = P4Cms_Record_Volatile::fetch('test-record', $this->_adapter, $client);
        $this->assertSame(
            array('biz' => 'baz', 'foo' => 'bar'),
            $record->getValues()
        );
        
        // should not be able to see record without masquerading
        $this->assertFalse(P4Cms_Record_Volatile::exists('test-record', $this->_adapter));
    }
}