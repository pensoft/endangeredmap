<?php namespace Pensoft\EndangeredMap\Models;

use Model;
use Pensoft\EndangeredMap\Models\Acronym;

/**
 * Status Model
 */
class Status extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table associated with the model
     */
    public $table = 'pensoft_endangeredmap_statuses';

    /**
     * Status code merges: primary code => [variant DB codes]
     * When filtering by a primary code, all variants are matched.
     */
    const STATUS_GROUPS = [
        'P'  => ['P', 'I', 'included'],
        'EN' => ['EN', 'E'],
        'VU' => ['VU', 'V'],
        'T'  => ['T', 'THREATENED', 'THREATENED WITH EXTINCTION'],
        'R'  => ['R', 'RARE'],
        'NA' => ['NA', 'NE'],
    ];

    /**
     * Display labels for known primary status codes
     */
    const STATUS_LABELS = [
        'P'     => 'Present',
        'A'     => 'Absent',
        'RE'    => 'Regionally Extinct',
        'PE'    => 'Possibly Extinct',
        'CR'    => 'Critically Endangered',
        'EN'    => 'Endangered',
        'VU'    => 'Vulnerable',
        'T'     => 'Threatened',
        'NT'    => 'Near Threatened',
        'R'     => 'Rare',
        'LC'    => 'Least Concern',
        'NN'    => 'Non-Native',
        'DD'    => 'Data Deficient',
        'DD/LC' => 'Data Deficient & Least Concern',
        'NA'    => 'Not Assessed',
    ];

    /**
     * Desired display order by label name
     */
    const LABEL_ORDER = [
        'Present',
        'Absent',
        'Regionally Extinct',
        'Possibly Extinct',
        'Critically Endangered',
        'Endangered',
        'Highly Threatened',
        'Vulnerable',
        'Threatened',
        'Near Threatened',
        'Extremely Rare',
        'Very Rare',
        'Rare',
        'Least Concern',
        'Not Threatened',
        'Non-Native',
        'Data Deficient',
        'Data Deficient & Least Concern',
        'Not Assessed',
    ];

    /**
     * @var array guarded attributes aren't mass assignable
     */
    protected $guarded = ['*'];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [];

    /**
     * @var array rules for validation
     */
    public $rules = [];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [];

    /**
     * @var array jsonable attribute names that are json encoded and decoded from the database
     */
    protected $jsonable = [];

    /**
     * @var array appends attributes to the API representation of the model (ex. toArray())
     */
    protected $appends = [];

    /**
     * @var array hidden attributes removed from the API representation of the model (ex. toArray())
     */
    protected $hidden = [];

    /**
     * @var array dates attributes that should be mutated to dates
     */
    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * @var array hasOne and other relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [
        'species' => ['Pensoft\EndangeredMap\Models\Species']
    ];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    /**
     * Get distinct country options
     */
    public function getCountryOptions()
    {
        return static::distinct()
            ->orderBy('country')
            ->pluck('country', 'country')
            ->toArray();
    }

    /**
     * Get status options with full names, merged groups, in specified order.
     * Returns [primaryCode => label] for use in filter dropdowns.
     */
    public function getStatusOptions()
    {
        $dbStatuses = static::distinct()->pluck('status')->toArray();
        $acronyms = Acronym::pluck('meaning', 'acronym')->toArray();

        // Build reverse map: variant DB code (lowercased) -> primary code
        $reverseMap = [];
        foreach (self::STATUS_GROUPS as $primary => $variants) {
            foreach ($variants as $variant) {
                $reverseMap[strtolower($variant)] = $primary;
            }
        }

        // Determine active primary codes and their labels
        $options = [];
        $seen = [];

        foreach ($dbStatuses as $code) {
            $primary = $reverseMap[strtolower($code)] ?? $code;

            if (isset($seen[$primary])) {
                continue;
            }
            $seen[$primary] = true;

            $label = self::STATUS_LABELS[$primary]
                ?? $acronyms[$primary]
                ?? $acronyms[$code]
                ?? $primary;

            $options[$primary] = $label;
        }

        // Sort by LABEL_ORDER position
        $labelOrder = array_flip(self::LABEL_ORDER);

        uksort($options, function ($a, $b) use ($options, $labelOrder) {
            $posA = $labelOrder[$options[$a]] ?? 999;
            $posB = $labelOrder[$options[$b]] ?? 999;
            return $posA - $posB;
        });

        return $options;
    }

    /**
     * Expand primary status codes into all variant DB codes for querying.
     */
    public static function expandStatusCodes($codes)
    {
        $expanded = [];
        foreach ($codes as $code) {
            if (isset(self::STATUS_GROUPS[$code])) {
                $expanded = array_merge($expanded, self::STATUS_GROUPS[$code]);
            } else {
                $expanded[] = $code;
            }
        }
        return array_unique($expanded);
    }
}
