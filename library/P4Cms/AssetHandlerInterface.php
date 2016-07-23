<?php
/**
 * Provide a common interface for Asset Handlers. In a single server
 * configuration assets can simply be stored under the resources folder.
 * When running multiple web servers the assets need to be put into a 
 * shared data store (such as S3) to allow all webservers access.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
interface P4Cms_AssetHandlerInterface
{
    /**
     * Check if the given ID exists in storage.
     * 
     * @param   string  $id     The ID to test
     * @return  bool    true if exists, false otherwise
     */
    public function exists($id);
    
    /**
     * Store the passed data using indicated ID.
     * Will clobber any existing entry with the same ID.
     * 
     * @param   string  $id     The ID to store under
     * @param   string  $data   The data to store
     * @return  bool    True if put was successful, false otherwise
     */
    public function put($id, $data);
    
    /**
     * Provide a URI for the indicated asset ID.
     * 
     * @param   string  $id     The ID to get a URI for
     * @return  string|bool     The uri used for the specified asset or false
     */
    public function uri($id);
    
    /**
     * Used to determine if the asset handler will be storing
     * assets offsite or not. Assets such as CSS need to know 
     * this so they can decide if they need to include the 
     * site's url when referencing images.
     * 
     * @return  bool    true if assets stored offsite, false otherwise
     */
    public function isOffsite();
}
