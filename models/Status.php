<?php namespace Pensoft\EndangeredMap\Models;

use Model;

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
     * Get distinct status options
     */
    public function getStatusOptions()
    {
        return static::distinct()
            ->orderBy('status')
            ->pluck('status', 'status')
            ->toArray();
    }
}
