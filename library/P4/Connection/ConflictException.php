<?php
/**
 * Exception to be thrown when a resolve error occurs.
 * Holds the associated Connection instance and result object.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_Connection_ConflictException extends P4_Connection_CommandException
{
    /**
     * Returns a P4_Change object for the changelist the conflict files live in.
     */
    public function getChange()
    {
        preg_match(
            '/submit -c ([0-9]+)/',
            implode($this->getResult()->getErrors()),
            $matches
        );
        
        return P4_Change::fetch($matches[1], $this->getConnection());
    }
}
