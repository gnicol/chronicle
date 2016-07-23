<?php
if (class_exists('Generic_Sniffs_Files_LineLengthSniff', true) === false) {
    throw new PHP_CodeSniffer_Exception('Class Generic_Sniffs_Files_LineLengthSniff not found');
}

/**
 * Set line length limit to 120.
 */
class P4CMS_Sniffs_Files_LineLengthSniff extends Generic_Sniffs_Files_LineLengthSniff
{
    protected $lineLimit            = 120;
    protected $absoluteLineLimit    = 120;
}
