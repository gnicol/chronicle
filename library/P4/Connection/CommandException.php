<?php
/**
 * Exception to be thrown when an error occurs running a Perforce
 * command. Holds the associated Connection instance and result object.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_Connection_CommandException extends P4_Exception
{
    private $_connection;
    private $_result;

    /**
     * Set the perforce Connection instance.
     *
     * @param   P4_Connection_Interface  $connection     the perforce Connection instance.
     */
    public  function setConnection(P4_Connection_Interface $connection)
    {
        $this->_connection = $connection;
    }

    /**
     * Get the perforce Connection instance if one is set.
     *
     * @return  P4_Connection_Interface     the perforce Connection instance.
     */
    public function getConnection()
    {
        if (isset($this->_connection)) {
            return $this->_connection;
        }
    }

    /**
     * Set the perforce result object.
     *
     * @param   P4_Result     $result     the perforce result object.
     */
    public  function setResult($result)
    {
        if ($result instanceof P4_Result) {
            $this->_result = $result;
        }
    }

    /**
     * Get the perforce result object if one is set.
     *
     * @return  P4_Result     the perforce result object.
     */
    public function getResult()
    {
        if (isset($this->_result)) {
            return $this->_result;
        }
    }
}
