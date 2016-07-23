<?php
/**
 * This is a test implementation of the Spec_PluralAbstract.
 * It is used to thoroughly exercise the base plural spec functionality so latter implementors
 * can focus on testing only their own additions/modifications.
 *
 * This class happens to represent the 'job' type as this is the cleanest looking plural-spec.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_Spec_PluralMock extends P4_Spec_PluralAbstract
{
    protected static    $_specType  = 'job';
    protected static    $_idField   = 'Job';

    /**
     * This function provides the tests access to any protected functions.
     *
     * @param   string  $function   Name of function to be called on this object
     * @param   array|string    $params     Paramater(s) to pass, optional
     * @return  mixed   Return result of called function, False on error
     */
    public function callProtectedFunc($function, $params = array())
    {
        if (!is_array($params)) {
            $params = array($params);
        }
        return call_user_func_array(array($this, $function), $params);
    }

    /**
     * This function provides the tests access to any protected static functions.
     *
     * @param   string  $function   Name of function to be called on this object
     * @param   array|string    $params     Paramater(s) to pass, optional
     * @return  mixed   Return result of called function, False on error
     */
    public static function callProtectedStaticFunc($function, $params = array())
    {
        if (!is_array($params)) {
            $params = array($params);
        }
        return call_user_func_array('static::'.$function, $params);
    }

    /**
     * This function provides the tests set capabilities on protected static variables.
     *
     * @param   string  $name   Name of variable to set/update on this object
     * @param   mixed   $value  New value to use
     */
    public static function setProtectedStaticVar($name, $value)
    {
        static::${$name} = $value;
    }

    /**
     * Determine if the given job id exists.
     *
     * @param   string                      $id             the id to check for.
     * @param   P4_Connection_Interface     $connection     optional - a specific connection to use.
     * @return  bool    true if the given id matches an existing job.
     */
    public static function exists($id, P4_Connection_Interface $connection = null)
    {
        // if no connection given, use default.
        $connection = $connection ?: static::getDefaultConnection();

        $result = $connection->run("jobs", array("-e", "Job=$id"));
        return (bool) count($result->getData());
    }
}
