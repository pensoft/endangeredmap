<?php namespace Pensoft\EndangeredMap;

use Backend;
use System\Classes\PluginBase;

/**
 * EndangeredMap Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'EndangeredMap',
            'description' => 'Interactive map displaying endangered species data.',
            'author'      => 'Pensoft',
            'icon'        => 'icon-map-marker'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConsoleCommand('endangeredmap.import', 'Pensoft\EndangeredMap\Console\ImportSpeciesData');
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {
        include __DIR__.'/routes.php';
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            'Pensoft\EndangeredMap\Components\EndangeredSpeciesMap' => 'endangeredSpeciesMap'
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return [];
    }
}
