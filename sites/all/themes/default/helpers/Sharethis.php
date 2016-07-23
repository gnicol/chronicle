<?php
/**
 * Extend ShareThis view helper to produce markup suitable for this theme.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Theme_Helper_Sharethis extends Sharethis_View_Helper_Sharethis
{
    /**
     * Override parent method to produce markup for small buttons and move
     * the buttons container into the page footer (if available).
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
        // override options to always produce small buttons
        $options                = (array) $options;
        $options['buttonStyle'] = 'small';

        $html = parent::sharethis($options, $template);

        // add javascript to move sharethis container to the page footer
        // we assume that the share this buttons are a peer of the book
        // this is the case when share this is used with content
        $html .= '<script>'
               . '  dojo.subscribe("p4cms.mobile.Book.startup", function(book) {'
               . '      var sharethis = dojo.query(".sharethis-container", book.domNode.parentNode)[0];'
               . '      if (sharethis) {'
               . '          dojo.place(sharethis, book.footer);'
               . '      }'
               . '  });'
               . '</script>';

        return $html;
    }
}