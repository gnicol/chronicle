<?php
/**
 * Filter to convert a Microsoft Word 2007 document to text.
 *
 * This implementation uses Zend_Search_Lucene_Docuemtn_Docx to extract
 * text contents from a word document (supports Word 2007 format only.)
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Filter_DocxToText implements Zend_Filter_Interface
{
    /**
     * Extract text contents from a Word format.
     *
     * @param   string  $docx           the Docx to be filtered.
     * @return  string                  the plain text output.
     * @throws  Zend_Search_Lucene_Document_Exception
     */
    public function filter($docx)
    {
        // shortcut if we have an empty string
        if (!strlen($docx)) {
            return;
        }

        // write contents to a tmp file
        $tempFile = tempnam(sys_get_temp_dir(), 'word');
        file_put_contents($tempFile, $docx);

        $document = Zend_Search_Lucene_Document_Docx::loadDocxFile($tempFile);

        // remove the temp file
        unlink($tempFile);

        return $document->getFieldValue('body');
    }
}
