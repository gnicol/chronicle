<?php
/**
 * Filter to convert PDF to text.
 *
 * This implementation requires a PDF text extractor provided
 * by XPDF -- pdftotext.  You may download the package from
 * their website at:
 *
 *     http://www.foolabs.com/xpdf/Download.html
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Filter_PdfToText implements Zend_Filter_Interface
{
    // the PDF text extractor command
    private $_command = "pdftotext";

    /**
     * Get the executable for PDF to text conversion.
     *
     * @return string  the command
     */
    public function getExecutable()
    {
        return $this->_command;
    }

    /**
     * Set the executable for PDF to text conversion used by this filter.
     *
     * @param string $command          the executable (may include path)
     * 
     * @return P4Cms_Filter_PdfToText  the filter itself for fluent flow.
     */
    public function setExecutable($command)
    {
        if (!is_string($command) || (trim($command) == '')) {
            throw new Zend_Filter_Exception(
                'The $command argument should be a non-empty string.');
        }

        $command = trim($command);

        // make sure the executible really exists by running it.
        $status = $this->checkExecutable($command);
        
        if (!$status) {
            throw new Zend_Filter_Exception(
                "Failed to run the command: $command."
            );
        }

        $this->_command = $command;

        return $this;
    }

    /**
     * Check if the specified PDF to text extractor command is available.
     * Special handling is used for Windows systems as the same error code
     * is returned if the executable does not exist or if the exectuable call
     * fails.
     * 
     * @param string|NULL $command  the pdftotext extractor command
     * @return boolean              TRUE,  if the command is available
     *                              FALSE, otherwise 
     */
    public function checkExecutable($command = NULL)
    {
        if (!$command) {
            $command = $this->_command;
        }

        // default to not executable
        $status = FALSE;

        // save original command for later comparison on Windows systems
        $originalCommand = $command;
        
        // get the version of pdftotext and redirect errors
        $command = $command . ' -v 2>&1';

        exec($command, $output, $exitCode);
        
        // it exists if we get any of the following exit codes
        switch ($exitCode) {
            case 0:
            case 1:
                // for Windows, check to see if the command failed or does not exist
                if (defined('PHP_WINDOWS_VERSION_MAJOR') 
                    && (strpos($output[0], "'$originalCommand' is not recognized") !== false)) {
                    return $status;
                }
            case 2:
            case 3:
            case 99:
                $status = TRUE;
                break;
            default:
                break;
        }

        return $status;
    }

    /**
     * Extract text contents from a PDF format.
     *
     * @param   string|Zend_Pdf  $pdf  the PDF to be filtered.
     *
     * @return  string                 the plain text output.
     */
    public function filter($pdf)
    {
        if ($pdf instanceof Zend_Pdf) {
            $pdf = $pdf->render();
        }

        // write contents to a tmp file
        $tempFile = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($tempFile, $pdf);

        $command = $this->_command . ' ' . escapeshellcmd($tempFile) . ' 2>&1';
        // execute pdftotext
        exec(
            $command,
            $output,
            $exitCode
        );

        // read in the result text file, if succeeds
        switch ($exitCode) {
            case 0:
                $pdfContents = file_get_contents($tempFile . '.txt');
                unlink($tempFile . '.txt');
                break;
            case 126:
                // no permission to run the command
                throw new Zend_Filter_Exception(
                        "Do not have permission to run the command to convert"
                      . " PDF to text."
                );
                break;
            case 127:
                // cannot find the command
                throw new Zend_Filter_Exception(
                    "Cannot locate the command to convert PDF to text."
                );
                break;
            default:
                // if any error happens, throw an exception
                throw new Zend_Filter_Exception(
                    "Failed converting the PDF file to text."
                );
        }

        unlink($tempFile);
        return $pdfContents;
    }
}
