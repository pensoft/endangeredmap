<?php namespace Pensoft\EndangeredMap\Controllers;

use Backend\Classes\Controller;
use Pensoft\EndangeredMap\Models\Species;
use Pensoft\EndangeredMap\Models\Status;
use Pensoft\EndangeredMap\Models\Acronym;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    /**
     * Search species with filters
     * GET api/endangered/search
     */
    public function search(Request $request)
    {
        $countries = $request->input('countries', []);
        $families = $request->input('families', []);
        $genera = $request->input('genera', []);
        $tribes = $request->input('tribes', []);
        $statuses = $request->input('statuses', []);
        $searchTerm = $request->input('search', '');

        $query = Species::query();

        if (!empty($countries) || !empty($statuses)) {
            $query->whereHas('statuses', function ($q) use ($countries, $statuses) {
                if (!empty($countries)) {
                    $q->whereIn('country', $countries);
                }
                if (!empty($statuses)) {
                    $q->whereIn('status', $statuses);
                }
            });
        }

        if (!empty($families)) {
            $query->whereIn('family', $families);
        }

        if (!empty($genera)) {
            $query->whereIn('genus', $genera);
        }

        if (!empty($tribes)) {
            $query->whereIn('tribe', $tribes);
        }

        if (!empty($searchTerm)) {
            $query->where('internal_name', 'like', "%{$searchTerm}%");
        }

        $species = $query->orderBy('internal_name')
                         ->pluck('internal_name')
                         ->toArray();

        return response()->json([
            'results' => count($species),
            'species' => $species
        ])->header('Access-Control-Allow-Origin', '*')
          ->header('Access-Control-Allow-Headers', '*')
          ->header('Access-Control-Allow-Methods', 'GET, OPTIONS');
    }

    /**
     * Get country-status data for a selected species
     * GET api/endangered/species
     */
    public function species(Request $request)
    {
        $speciesName = $request->input('q');

        if (!$speciesName) {
            return response()->json(['error' => 'Query parameter "q" is required'], 400)
                             ->header('Access-Control-Allow-Origin', '*')
                             ->header('Access-Control-Allow-Headers', '*')
                             ->header('Access-Control-Allow-Methods', 'GET, OPTIONS');
        }

        $species = Species::where('internal_name', $speciesName)->first();

        if (!$species) {
            return response()->json(['error' => 'Species not found'], 404)
                             ->header('Access-Control-Allow-Origin', '*')
                             ->header('Access-Control-Allow-Headers', '*')
                             ->header('Access-Control-Allow-Methods', 'GET, OPTIONS');
        }

        $acronyms = Acronym::pluck('meaning', 'acronym')->toArray();

        $statuses = $species->statuses->map(function ($status) use ($acronyms) {
            return [
                'country' => $status->country,
                'status' => $status->status,
                'meaning' => $acronyms[$status->status] ?? $status->status
            ];
        });

        return response()->json([
            'species' => [
                'internal_name' => $species->internal_name,
                'family' => $species->family,
                'genus' => $species->genus,
                'tribe' => $species->tribe,
            ],
            'statuses' => $statuses,
            'meta' => [
                'total_countries' => $statuses->count()
            ]
        ])->header('Access-Control-Allow-Origin', '*')
          ->header('Access-Control-Allow-Headers', '*')
          ->header('Access-Control-Allow-Methods', 'GET, OPTIONS');
    }

    /**
     * Get all status acronyms
     * GET api/endangered/acronyms
     */
    public function acronyms()
    {
        $acronyms = Acronym::orderBy('acronym')->get(['acronym', 'meaning'])->toArray();

        return response()->json([
            'acronyms' => $acronyms
        ])->header('Access-Control-Allow-Origin', '*')
          ->header('Access-Control-Allow-Headers', '*')
          ->header('Access-Control-Allow-Methods', 'GET, OPTIONS');
    }

    /**
     * CORS preflight handler
     */
    public function options()
    {
        return response()->json(null, 200)
                         ->header('Access-Control-Allow-Origin', '*')
                         ->header('Access-Control-Allow-Headers', '*')
                         ->header('Access-Control-Allow-Methods', 'GET, OPTIONS');
    }
}
