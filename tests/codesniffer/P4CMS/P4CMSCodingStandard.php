<?php
/**
 * P4CMS Coding Standard.
 */
class PHP_CodeSniffer_Standards_P4CMS_P4CMSCodingStandard
    extends PHP_CodeSniffer_Standards_CodingStandard
{
    /**
     * Return a list of external sniffs to include with this standard.
     * The P4CMS standard uses most of the same sniffs as the Zend standard.
     *
     * @return  array   the external sniffs to include.
     */
    public function getIncludedSniffs()
    {
        return array(
            'Generic/Sniffs/Functions/OpeningFunctionBraceBsdAllmanSniff.php',
            'Generic/Sniffs/PHP/DisallowShortOpenTagSniff.php',
            'Generic/Sniffs/WhiteSpace/DisallowTabIndentSniff.php',
            'PEAR/Sniffs/Classes/ClassDeclarationSniff.php',
            'PEAR/Sniffs/ControlStructures/ControlSignatureSniff.php',
            'PEAR/Sniffs/Files/LineEndingsSniff.php',
            'PEAR/Sniffs/Functions/ValidDefaultValueSniff.php',
            'PEAR/Sniffs/WhiteSpace/ScopeClosingBraceSniff.php',
            'Squiz/Sniffs/Functions/GlobalFunctionSniff.php',
            'Zend/Sniffs/Debug/CodeAnalyzerSniff.php',
            'Zend/Sniffs/Files/ClosingTagSniff.php',
        );
    }

    /**
     * List of sniffs to exclude.
     * 
     * @return  array   the sniffs to exclude.
     */
    public function getExcludedSniffs()
    {
        return array(__DIR__ . '/Sniffs/Commenting/FileCommentSniff.php');
    }
}
?>
