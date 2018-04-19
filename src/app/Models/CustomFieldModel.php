<?php
/**
 * Custom Fields Model
 *
 * It handles Custom Fields mainly used for posts
 * @author Jetmir Haxhisefa <jetmir.haxhisefa@manaferra.com>
 * @version 1.0
 */

namespace Accio\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Accio\App\Traits;

class CustomFieldModel extends Model{

    use Traits\CustomFieldTrait;

    public static $snakeAttributes = false;

    /**
     * Fields that can be filled in CRUD
     *
     * @var array $fillable
     */
    protected $fillable = ['customFieldID','customFieldGroupID','parentID','label','slug','placeholder','type','note','defaultValue',
        'optionsValues','conditions','order','isRequired','isTranslatable','isMultiple','isDisabled','isReadOnly','isActive','wrapperStyle','fieldStyle',
        'typeAttributes','layout','created_at','updated_at'];

    /**
     * Type of the columns
     * @var array
     */
    protected $casts = [
        'label' => 'object',
        'placeholder' => 'object',
        'note' => 'object',
        'conditions' => 'array',
        'properties' => 'object',
        'wrapperStyle' => 'object',
        'fieldStyle' => 'object',
        'isMultiple' => 'boolean',
    ];

    /**
     * The primary key of the table
     *
     * @var string $primaryKey
     */
    public $primaryKey = "customFieldID";

    /**
     * Default number of rows per page to be shown in admin panel
     *
     * @var integer $rowsPerPage
     */
    public static $rowsPerPage = 25;

    /**
     * The table associated with the model.
     *
     * @var string $table
     */
    public $table = "custom_fields";

    /**
     * Lang key that points to the multi language label in translate file
     * @var string
     */
    public static $label = "CustomFields.label";

    /**
     * Default permissions that will be listed in settings of permissions
     *
     * @var array $defaultPermissions
     */
    public static $defaultPermissions = ['create','read', 'update', 'delete'];


    /**
     * @var array used to return the media as it required in the frontend (VUE.js) for each custom field and sub field
     */
    private $media = [];


    /**
     * @var array returns the values of the custom field
     */
    private $customFieldValues = [];


    /**
     * @inheritdoc
     * */
    public function __construct(array $attributes = []){
        parent::__construct($attributes);
        Event::fire('customField:construct', [$this]);
    }

    /**
     * @return array
     */
    public function getMedia(): array{
        return $this->media;
    }

    /**
     * @return array
     */
    public function getCustomFieldValues(): array{
        return $this->customFieldValues;
    }

    public function getOptionsValuesAttribute($value){
        $valArr = explode(',',$value);
        $result = [];
        $result['string'] = $value;
        if(is_array($valArr)){
            foreach($valArr as $item){
                $tmpArr = explode(':',trim($item));
                if(isset($tmpArr[0]) && isset($tmpArr[1])){
                    $result['object'][trim($tmpArr[0])] = trim($tmpArr[1]);
                }
            }
        }
        return $result;
    }

    /**
     * Listen to crud events
     * */
    protected static function boot(){
        parent::boot();

        self::saving(function($customField){
            Event::fire('customField:saving', [$customField]);
        });

        self::saved(function($customField){
            Event::fire('customField:saved', [$customField]);
        });

        self::creating(function($customField){
            Event::fire('customField:creating', [$customField]);
        });

        self::created(function($customField){
            Event::fire('customField:created', [$customField]);
        });

        self::updating(function($customField){
            Event::fire('customField:updating', [$customField]);
        });

        self::updated(function($customField){
            Event::fire('customField:updated', [$customField]);
        });

        self::deleting(function($customField){
            Event::fire('customField:deleting', [$customField]);
        });

        self::deleted(function($customField){
            Event::fire('customField:deleted', [$customField]);
        });
    }

    /**
     * Destruct model instance
     */
    public function __destruct()
    {
        Event::fire('customField:destruct', [$this]);
    }
}
