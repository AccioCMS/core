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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Lang;
use Input;
use Request;
use Illuminate\Database\Eloquent\Model;
use Accio\App\Traits;
use Spatie\Activitylog\Traits\LogsActivity;

class LanguageModel extends Model{

    use
      Traits\LanguageTrait,
      LogsActivity,
      Traits\CacheTrait,
      Traits\BootEventsTrait;

    /**
     * Fields that can be filled in CRUD
     *
     * @var array $fillable
     */
    protected $fillable = ['createdByUserID','name','nativeName','slug','isDefault','isVisible'];

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
     * @var bool
     */
    protected static $logFillable = true;

    /**
     * @var bool
     */
    protected static $logOnlyDirty = true;

    /**
     * @inheritdoc
     * */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        Event::fire('language:construct', [$this]);
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
