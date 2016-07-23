<?php
/**
 * Dojo editor view helper customized for use with p4cms content.
 *  - Registers additional plugin modules.
 *  - Provides a static 'plugin registry'.
 *  - Adds support for specifying extra plugins.
 *  - Enhances onchange to proxy to an arbitrary element.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_View_Helper_Editor extends Zend_Dojo_View_Helper_Editor
{

    protected static $_pluginRegistry = array();

    protected        $_pluginsModules = array(
        'foreColor'                 => 'p4cms.content.editor.plugins.TextColor',
        'hiliteColor'               => 'p4cms.content.editor.plugins.TextColor',
        'fontName'                  => 'p4cms.content.editor.plugins.FontChoice',
        'fontSize'                  => 'p4cms.content.editor.plugins.FontChoice',
        'formatBlock'               => 'p4cms.content.editor.plugins.FontChoice',
        'link'                      => 'p4cms.content.editor.plugins.Link',
        'image'                     => 'p4cms.content.editor.plugins.Image',
        'viewsource'                => 'p4cms.content.editor.plugins.ViewSource',
        'paste'                     => 'p4cms.content.editor.plugins.Paste',
        'branchifyurls'             => 'p4cms.content.editor.plugins.BranchifyUrls',
        'prettyprint'               => 'p4cms.content.editor.plugins.PrettyPrint',

        'toggleDir '                => 'dijit._editor.plugins.ToggleDir',
        'fullscreen'                => 'dijit._editor.plugins.FullScreen',
        'print'                     => 'dijit._editor.plugins.Print',
        'newpage'                   => 'dijit._editor.plugins.NewPage',

        'pagebreak'                 => 'dojox.editor.plugins.PageBreak',
        'showblocknodes'            => 'dojox.editor.plugins.ShowBlockNodes',
        'preview'                   => 'dojox.editor.plugins.Preview',
        'save'                      => 'dojox.editor.plugins.Save',
        '||'                        => 'dojox.editor.plugins.ToolbarLineBreak',
        'toolbarlinebreak'          => 'dojox.editor.plugins.ToolbarLineBreak',
        'normalizeindentoutdent'    => 'dojox.editor.plugins.NormalizeIndentOutdent',
        'breadcrumb'                => 'dojox.editor.plugins.Breadcrumb',
        'findreplace'               => 'dojox.editor.plugins.FindReplace',
        'pastefromword'             => 'dojox.editor.plugins.PasteFromWord',
        'insertanchor'              => 'dojox.editor.plugins.InsertAnchor',
        'collapsibletoolbar'        => 'dojox.editor.plugins.CollapsibleToolbar',
        'blockquote'                => 'dojox.editor.plugins.Blockquote',
        'normalizestyle'            => 'dojox.editor.plugins.NormalizeStyle'
    );

    /**
     * @param string Dijit type
     */
    protected $_dijit = 'p4cms.content.Editor';

    /**
     * @var string Dijit module to load
     */
    protected $_module = 'p4cms.content.Editor';

    /**
     * JSON-encoded parameters
     * @var array
     */
    protected $_jsonParams = array('captureEvents', 'events', 'plugins', 'extraPlugins');

    /**
     * Extend default editor generation to include onChange proxying of content to hidden
     * text area (normally it is only proxied onSubmit).
     *
     * Additionally, will ensure any extraPlugins have been required in to function.
     *
     * @param   string  $id       Zend provides no documentation for this param.
     * @param   string  $value    Zend provides no documentation for this param.
     * @param   array   $params   Zend provides no documentation for this param.
     * @param   array   $attribs  Zend provides no documentation for this param.
     * @return  string
     */
    public function editor($id, $value = null, $params = array(), $attribs = array())
    {
        // Step 0: add any 'default' plugins if user didn't specify 'plugins' setting
        if (!isset($params['plugins']) || empty($params['plugins'])) {
            // normalize extraPlugins to be present and an array
            if (!isset($params['extraPlugins']) || !is_array($params['extraPlugins'])) {
                $params['extraPlugins'] = array();
            }

            // scan all registered plugins and add in non-present 'default' entries
            foreach (static::$_pluginRegistry as $shortName => $options) {
                if (!isset($options['default'])) {
                    continue;
                }

                if (in_array($shortName, $params['extraPlugins'])) {
                    continue;
                }

                // if we have a 'long name' and it is present skip
                if (isset($options['plugin'])
                    && in_array($options['plugin'], $params['extraPlugins'])) {
                    continue;
                }

                $params['extraPlugins'][] = $shortName;
            }
        }

        // Step 1: ensure 'extraPlugins' get required in
        if (isset($params['extraPlugins'])) {
            foreach ($this->_getRequiredModules($params['extraPlugins']) as $module) {
                $this->dojo->requireModule($module);
            }
        }

        // Step 2: adjust onChange handling
        $hiddenName = $id;
        if (array_key_exists('id', $attribs)) {
            $hiddenId = $attribs['id'];
        } else {
            $hiddenId = $hiddenName;
        }
        $hiddenId = $this->_normalizeId($hiddenId);

        $attribs['proxyId'] = $hiddenId;

        // return parent with the fallback textarea stripped; it blows up content panes with a dupe ID
        // we use substr in order to avoid the PHP pcre_backtrack_limit constraint on preg_replace
        $html  = parent::editor($id, $value, $params, $attribs);
        $start = strpos($html, '<noscript>');
        while ($start !== false) {
            $end   = strpos($html, '</noscript>', $start);

            // exit the loop early if there is not an end tag
            if ($end === false) {
                break;
            }

            // remove anything in the tag (including tag names).
            $html  = substr_replace($html, "", $start, $end - $start + 11);

            // search for the start of the next tag
            $start = strpos($html, '<noscript>', $start);
        }

        return $html;
    }

    /**
     * Add a plugin to the registry. Options expects an array that contains
     * one or more of the below settings:
     * 'plugin'  => plugin class name,  e.g. 'dijit._editor.plugins.ViewSource'
     * 'default' => true/false          the plugin will be enabled by default if true
     *
     * @param   string  $shortName  The user friendly plugin name, e.g. 'viewsource'
     * @param   array   $options    The array of option(s), see above for details
     */
    public static function registerPlugin($shortName, $options)
    {
        if (!is_array($options)) {
            throw new InvalidArgumentException('Expected options to be an array');
        }
        if (!is_string($shortName)) {
            throw new InvalidArgumentException('Expected shortName to be a string');
        }

        static::$_pluginRegistry[$shortName] = $options;
    }

    /**
     * Clears all registered plugins and their related options.
     */
    public static function clearPluginRegistry()
    {
        static::$_pluginRegistry = array();
    }

    /**
     * Generates the list of required modules to include, if any is needed.
     *
     * @param array $plugins plugins to include
     * @return array
     */
    protected function _getRequiredModules(array $plugins)
    {
        $modules = array();
        foreach ($plugins as $commandName) {
            if (isset($this->_pluginsModules[$commandName])) {
                $modules[] = $this->_pluginsModules[$commandName];
            } elseif (isset(static::$_pluginRegistry[$commandName])
                      && isset(static::$_pluginRegistry[$commandName]['plugin'])
            ) {
                $modules[] = static::$_pluginRegistry[$commandName]['plugin'];
            } else {
                // do not include short-name plugins if they are not
                // contained in plugins registry/modules lists
                // @todo consider to get rid of this block at all
                // and do nothing in this case (like the parent method)
                if (strpos($commandName, '.') !== false) {
                    $modules[] = $commandName;
                }
            }
        }

        return array_unique($modules);
    }

    /**
     * Extend parent to remove dependency on zend.findParentForm; no
     * other functional change.
     *
     * @param  string $hiddenId The hidden Dojo form ID
     * @param  string $editorId The editor Dojo form ID
     * @return void
     */
    protected function _createEditorOnSubmit($hiddenId, $editorId)
    {
        $this->dojo->onLoadCaptureStart();
        echo <<<EOJ
function() {
    var form = dojo.byId('$hiddenId');
    while (form.nodeName.toLowerCase() != 'form') {
        form = form.parentNode;
    }

    dojo.connect(form, 'submit', function(e) {
        var value = dijit.byId('$editorId').getValue(false);
        if(dojo.isFF) {
            value = value.replace(/<br _moz_editor_bogus_node="TRUE" \/>/, '');
        }
        dojo.byId('$hiddenId').value = value;
    });
}
EOJ;
        $this->dojo->onLoadCaptureEnd();
    }
}
