<?php
/**
 * Filter to convert a Microsoft Excel 2007 document to text.
 *
 * This implementation uses Zend_Search_Lucene_Docuemtn_Xlsx to extract
 * text contents from an Excel document (supports Excel 2007 format only.)
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Filter_XlsxToText implements Zend_Filter_Interface
{
    /**
     * Extract text contents from an Excel format.
     *
     * @param   string  $xlsx           the Excel contents to be filtered.
     * @return  string                  the plain text output.
     */
    public function filter($xlsx)
    {
        // shortcut if we have an empty string
        if (!strlen($xlsx)) {
            return;
        }

        // write contents to a tmp file
        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        file_put_contents($tempFile, $xlsx);

        $document = Zend_Search_Lucene_Document_Xlsx::loadXlsxFile($tempFile);

        // remove the temp file
        unlink($tempFile);
        
        return $document->getFieldValue('body');
    }
}
