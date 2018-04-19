<?php

/**
 * Language Model
 *
 * It handles Languages management
 * @author Jetmir Haxhisefa <jetmir.haxhisefa@manaferra.com>
 * @author Faton Sopa <faton.sopa@manaferra.com>
 * @version 1.0
 */

namespace Accio\App\Models;

use App\Models\Language;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Input;
use Request;
use Illuminate\Database\Eloquent\Model;
use Accio\App\Traits;

class LanguageModel extends Model{

    use Traits\LanguageTrait;

    /**
     * Fields that can be filled in CRUD
     *
     * @var array $fillable
     */
    protected $fillable = ['name','nativeName','slug','isDefault','isVisible'];

    /**
     * The primary key of the table
     *
     * @var string $primaryKey
     */
    public $primaryKey = "languageID";

    /**
     * The table associated with the model.
     *
     * @var string $table
     */
    public $table = "languages";


    /**
     * Default number of rows per page to be shown in admin panel
     *
     * @var integer $rowsPerPage
     */
    public static $rowsPerPage = 100;

    /**
     * Lang key that points to the multi language label in translate file
     * @var string
     */
    public static $label = "language.label";

    /**
     * Default permission that will be listed in settings of permissions.
     *
     * @var array $defaultPermissions
     */
    public static $defaultPermissions = ['create','read', 'update', 'delete'];

    /**
     * Custom permission that will be listed in settings of permissions
     *
     * @var array $customPermissions
     */
    public static $customPermissions = [
        'id' => [
            'type' => 'select',
            'label' => 'Language',
            'value' => [
                'model' => 'Language',
                'select' => ['name'],
                'order'=>[
                    [
                        'field'=>'name',
                        'type'=>'ASC',
                    ],
                ],
                'limit'=> 5
            ]
        ],
    ];

    /**
     * @inheritdoc
     * */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        Event::fire('language:construct', [$this]);
    }

    /**
     * Get language from cache. Cache is generated if not found
     *
     * @return object|null  Returns requested cache if found, null instead
     */
    public static function getFromCache(){
        if(!Cache::has('languages')){
            $languagesList = self::all()->keyBy('slug');
            Cache::forever('languages',$languagesList);

            return $languagesList;
        }
        return Cache::get('languages');
    }

    /**
     * Listen to crud events
     * */
    protected static function boot(){
        parent::boot();

        self::saving(function($language){
            Event::fire('language:saving', [$language]);
        });

        self::saved(function($language){
            Event::fire('language:saved', [$language]);
            self::_saved($language);
        });

        self::creating(function($language){
            Event::fire('language:creating', [$language]);
        });

        self::created(function($language){
            Event::fire('language:created', [$language]);
        });

        self::updating(function($language){
            Event::fire('language:updating', [$language]);
        });

        self::updated(function($language){
            Event::fire('language:updated', [$language]);
        });

        self::deleting(function($language){
            Event::fire('language:deleting', [$language]);
        });

        self::deleted(function($language){
            Event::fire('language:deleted', [$language]);
            self::_deleted($language);
        });
    }

    /**
     * Perform certain actions after a language is saved
     *
     * @param object $language Saved language
     * */
    private static function _saved($language){
        Cache::forget('languages');
    }

    /**
     * Perform certain actions after a category is deleted
     *
     * @param object $language Deleted language
     * */
    private static function _deleted($language){
        Cache::forget('languages');
    }

    /**
     * Destruct model instance
     */
    public function __destruct()
    {
        Event::fire('language:destruct', [$this]);
    }

    /**
     * Get visible langauges from caches
     * @return mixed
     */
    public static function getVisibleList(){
        if(Language::getFromCache()) {
            return Language::getFromCache()->where('isVisible', true);
        }
        return;
    }

}
