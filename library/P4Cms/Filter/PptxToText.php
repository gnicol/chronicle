<?php
/**
 * Filter to convert a Microsoft PowerPoint 2007 document to text.
 *
 * This implementation uses Zend_Search_Lucene_Docuemtn_Pptx to extract
 * text contents from a PowerPoint document (supports PowerPoint 2007
 * format only.)
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Filter_PptxToText implements Zend_Filter_Interface
{
    /**
     * Extract text contents from a PowerPoint format.
     *
     * @param   string  $pptx           the Powerpoint contents to be filtered.
     * @return  string                  the plain text output.
     */
    public function filter($pptx)
    {
        // shortcut if we have an empty string
        if (!$pptx) {
            return;
        }

        // write contents to a tmp file
        $tempFile = tempnam(sys_get_temp_dir(), 'powerpoint');
        file_put_contents($tempFile, $pptx);

        $document = Zend_Search_Lucene_Document_Pptx::loadPptxFile($tempFile);

        // remove the temp file
        unlink($tempFile);
        
        return $document->getFieldValue('body');
    }
}
