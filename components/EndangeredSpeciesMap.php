<?php namespace Pensoft\EndangeredMap\Components;

use Cms\Classes\ComponentBase;
use Pensoft\EndangeredMap\Models\Species;
use Pensoft\EndangeredMap\Models\Status;

class EndangeredSpeciesMap extends ComponentBase
{
    public function onRun()
    {
        $this->addJs('assets/js/leaflet.js');
        $this->addJs('assets/js/maptiler-sdk.umd.js');
        $this->addJs('assets/js/leaflet-maptilersdk.js');
        $this->addJs('assets/js/endangered-map.js');

        $this->addCss('assets/css/leaflet.css');
        $this->addCss('assets/css/maptiler-sdk.css');

        $speciesModel = new Species;
        $statusModel = new Status;

        $this->page['family_options'] = $speciesModel->getFamilyOptions();
        $this->page['genus_options'] = $speciesModel->getGenusOptions();
        $this->page['tribe_options'] = $speciesModel->getTribeOptions();
        $this->page['country_options'] = $statusModel->getCountryOptions();
        $this->page['status_options'] = $statusModel->getStatusOptions();
    }

    public function componentDetails()
    {
        return [
            'name' => 'Endangered Species Map',
            'description' => 'Displays a choropleth map of endangered species status across European countries.'
        ];
    }

    public function defineProperties()
    {
        return [];
    }
}
