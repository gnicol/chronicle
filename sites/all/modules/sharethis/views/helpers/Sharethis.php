<?php
/**
 * Displays ShareThis container with buttons.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Sharethis_View_Helper_Sharethis extends Zend_View_Helper_Abstract
{
    protected $_isFirstRun = true;

    /**
     * Render ShareThis buttons depending on options. Also ensures that 'insertJavascript'
     * flag passed to the view and singalizing that all javascript necessary for
     * ShareThis should be included, will have 'true' value only at the first run.
     *
     * @param   array   $options    optional - options to control:
     *                                  buttonStyle  - ShareThis buttons style
     *                                  services     - list of selected services
     *                                  publisherKey - key associated with ShareThis account
     *
     * @param   string  $template   optional - name of the template to render
     * @return  string  the rendered bar with ShareThis buttons
     */
    public function sharethis(array $options = null, $template = 'sharethis.phtml')
    {
        // normalize options against default values from module config
        $options = Sharethis_Form_Configure::getNormalizedOptions($options);

        // insert javascript only when the template is rendered for the first time
        $insertJavascript  = $this->_isFirstRun;
        $this->_isFirstRun = false;

        // render ShareThis via template in private scope
        // to avoid polluting the primary view object
        $view = $this->view;
        $view->addScriptPath(dirname(__DIR__) . '/scripts');
        return $view->partial(
            $template,
            array(
                'buttonStyle'       => $options['buttonStyle'],
                'services'          => $options['services'],
                'publisherKey'      => $options['publisherKey'],
                'insertJavascript'  => $insertJavascript
            )
        );
    }
}