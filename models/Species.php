<?php namespace Pensoft\EndangeredMap\Models;

use Model;

/**
 * Species Model
 */
class Species extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table associated with the model
     */
    public $table = 'pensoft_endangeredmap_species';

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
    public $hasMany = [
        'statuses' => ['Pensoft\EndangeredMap\Models\Status']
    ];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    /**
     * Get distinct family options
     */
    public function getFamilyOptions()
    {
        return static::whereNotNull('family')
            ->distinct()
            ->orderBy('family')
            ->pluck('family', 'family')
            ->toArray();
    }

    /**
     * Get distinct genus options
     */
    public function getGenusOptions()
    {
        return static::whereNotNull('genus')
            ->distinct()
            ->orderBy('genus')
            ->pluck('genus', 'genus')
            ->toArray();
    }

    /**
     * Get distinct subfamily options
     */
    public function getSubfamilyOptions()
    {
        return static::whereNotNull('subfamily')
            ->distinct()
            ->orderBy('subfamily')
            ->pluck('subfamily', 'subfamily')
            ->toArray();
    }

    /**
     * Get distinct tribe options
     */
    public function getTribeOptions()
    {
        return static::whereNotNull('tribe')
            ->distinct()
            ->orderBy('tribe')
            ->pluck('tribe', 'tribe')
            ->toArray();
    }
}
