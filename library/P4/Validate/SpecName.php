<?php
/**
 * Validates string for suitability as a Perforce spec name.
 * Extends key-name validator to disallow positional specifiers.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_Validate_SpecName extends P4_Validate_KeyName
{
    protected   $_allowPositional = false;
}
