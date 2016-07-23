<?php
/**
 * Extends Zend_Dojo_Form_Element_Editor to implement functionality
 * that allows the user to define extra plugins via the options.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Form_Element_Editor extends Zend_Dojo_Form_Element_Editor
{
    /**
     * Add a single editor extra plugin
     *
     * @param  string $plugin  plugin name
     * @return Zend_Dojo_Form_Element_Editor
     */
    public function addExtraPlugin($plugin)
    {
        $plugin    = (string) $plugin;
        if ($this->hasExtraPlugin($plugin)) {
            return $this;
        }

        $plugins   = $this->getExtraPlugins();
        $plugins[] = $plugin;
        $this->setDijitParam('extraPlugins', $plugins);

        return $this;
    }

    /**
     * Add multiple extra plugins
     *
     * @param  array $plugins  array of plugin names
     * @return Zend_Dojo_Form_Element_Editor
     */
    public function addExtraPlugins(array $plugins)
    {
        foreach ($plugins as $plugin) {
            $this->addExtraPlugin($plugin);
        }

        return $this;
    }

    /**
     * Overwrite many extra plugins at once
     *
     * @param  array $plugins  array of plugin names
     * @return Zend_Dojo_Form_Element_Editor
     */
    public function setExtraPlugins(array $plugins)
    {
        $this->clearExtraPlugins();
        $this->addExtraPlugins($plugins);

        return $this;
    }

    /**
     * Get all extra plugins
     *
     * @return array
     */
    public function getExtraPlugins()
    {
        if (!$this->hasDijitParam('extraPlugins')) {
            return array();
        }

        return $this->getDijitParam('extraPlugins');
    }

    /**
     * Is a given extra plugin registered?
     *
     * @param  string $plugin  plugin name
     * @return bool
     */
    public function hasExtraPlugin($plugin)
    {
        $plugins = $this->getExtraPlugins();

        return in_array((string) $plugin, $plugins);
    }

    /**
     * Remove a given extra plugin
     *
     * @param  string $plugin  plugin name
     * @return Zend_Dojo_Form_Element_Editor
     */
    public function removeExtraPlugin($plugin)
    {
        $plugins = $this->getExtraPlugins();
        if (false === ($index = array_search($plugin, $plugins))) {
            return $this;
        }
        unset($plugins[$index]);
        $this->setDijitParam('extraPlugins', $plugins);

        return $this;
    }

    /**
     * Clear all extra plugins
     *
     * @return Zend_Dojo_Form_Element_Editor
     */
    public function clearExtraPlugins()
    {
        return $this->removeDijitParam('extraPlugins');
    }
}
