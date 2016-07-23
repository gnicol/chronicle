<?php
/**
 * Base class for workflow actions. Workflow actions allow for 
 * automated tasks when a record under workflow changes state
 * (for example, sending email notifications).
 * 
 * This abstract class provides basic handling of options
 * (courtesy of the plugin abstract).
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
abstract class Workflow_ActionAbstract
    extends     Workflow_PluginAbstract
    implements  Workflow_ActionInterface
{
}