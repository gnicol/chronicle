<?php
/**
 * View helper that returns an instance of the named enabled module model.
 * 
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_View_Helper_DojoIoResponse extends Zend_View_Helper_Abstract
{
    /**
     * Create response text for dojo.io.iframe request.
     * 
     * @param  mixed    $data       data to send to dojo.io.iframe
     * @param  string   $handleAs   Encoding method one of: text, html, xml, json, javascript
     *                              NOTE: Presently xml is not supported.
     * @return string|void
     */
    public function dojoIoResponse($data, $handleAs = 'json')
    {
        $formats = array('json', 'html', 'text', 'javascript');
        if (!in_array($handleAs, $formats)) {
            throw new InvalidArgumentException(
                'Invalid handleAs format. Expected one of: ' . implode(', ', $formats)
            );
        }

        // automatically encode as json.
        if ($handleAs == 'json') {
            $data = Zend_Json::encode($data);
        }

        return '<html><head>'
             . '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'
             . '</head><body><textarea>'
             . $this->view->escape($data)
             . '</textarea></body></html>';
    }
}
