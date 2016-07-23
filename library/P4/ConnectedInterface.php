<?php
/**
 * Provides a common interface for connected models.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
interface P4_ConnectedInterface
{
    /**
     * Instantiate the model and set the connection to use.
     *
     * @param   P4_Connection_Interface $connection  optional - a connection to use for this instance.
     */
    public function __construct(P4_Connection_Interface $connection = null);

    /**
     * Set the Perforce connection to use when
     * issuing Perforce commands for this instance.
     *
     * @param   P4_Connection_Interface $connection     the connection to use for this instance.
     * @return  P4_ModelAbstract        provides fluent interface.
     */
    public function setConnection(P4_Connection_Interface $connection);

    /**
     * Get the Perforce connection used by this model.
     *
     * @return  P4_Connection_Interface  the connection instance used by this model.
     */
    public function getConnection();

    /**
     * Get the default Perforce connection to use.
     *
     * @return  P4_Connection_Interface  the default connection.
     */
    public static function getDefaultConnection();

    /**
     * Determine if this model has a connection to Perforce.
     *
     * @return  bool  true if the model has a connection to Perforce.
     */
    public function hasConnection();

    /**
     * Clear this model's connection. This is primarily for testing purposes.
     */
    public function clearConnection();
}
