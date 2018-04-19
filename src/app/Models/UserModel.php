<?php

namespace Accio\App\Models;

use App\Models\Language;
use App\Models\User;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Accio\App\Traits;
use Accio\Support\Facades\Meta;

class UserModel extends Authenticatable
{
    use Traits\UserTrait, Notifiable, Traits\TranslatableTrait;

    /** @var array $fillable fields that can be filled in CRUD*/
    protected $fillable = [
        'firstName','lastName','email', 'password','phone','address','country','groupIDs', 'isActive','profileImageID','about','slug','gravatar'
    ];
    //TODO me ja shtu "slug" userave (emri-mbiemri) sepse po na duhet ne front-end

    /** @var array $fillable Hidden fields that can be seen in CRUD*/
    protected $hidden = [
        'password', 'remember_token',
    ];
    /** @var array column data-types **/
    protected $casts = [
        'about' => 'object',
        'groupIDs' => 'object'
    ];

    /** @var array translatable json columns **/
    protected $translatableColumns = [
        'about' => "string"
    ];

    /** @var string $primaryKey the primary key */
    public $primaryKey = "userID";

    /**
     * Lang key that points to the multi language label in translate file
     * @var string
     */
    public static $label = "User.label";

    /** @var array $defaultPermissions default permissions that will be listed in settings of permissions*/
    public static $defaultPermissions = ['create','read', 'update', 'delete'];

    /** @var integer $rowsPerPage how many rows to show in the pagination*/
    public static $rowsPerPage = 15;

    /** @var array $advancedSearchFields Which fields are searchable By "advanced search" in admin area  */
    public static $advancedSearchFields = [
        [
            'name'      => 'Name',
            'type'      => 'string',
            'db-column' => 'firstName'
        ],
        [
            'name'      => 'Lastname',
            'type'      => 'string',
            'db-column' => 'lastName'
        ],
        [
            'name'      => 'Email',
            'type'      => 'email',
            'db-column' => 'email'
        ],
        [
            'name'      => 'Phone',
            'type'      => 'string',
            'db-column' => 'phone'
        ],
        [
            'name'      => 'Address',
            'type'      => 'string',
            'db-column' => 'address'
        ],
        [
            'name'      => 'Country',
            'type'      => 'string',
            'db-column' => 'country'
        ],
        [
            'name'      => 'Active',
            'type'      => 'boolean',
            'db-column' => 'isActive'
        ]
    ];

    /**
     * @inheritdoc
     * */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        Event::fire('user:construct', [$this]);
    }

    //TODO sun po e gjej a perdoret najkund qe me e fshi
    public function profileImage(){
        return $this->hasOne('App\Models\Media','mediaID', 'profileImageID');
    }

    /**
     * Get Users
     * If User  are available in cache, they are stored from cache, otherwise a query takes place
     *
     * @return array
     */
    public static function getFromCache(){
        if(!Cache::has('users')){
            $getUsers = User::with("profileImage")->get();
            Cache::forever('users',$getUsers);

            return $getUsers;
        }
        return Cache::get('users');;
    }


    /**
     * Handle callback of insert, update, delete
     * */
    protected static function boot(){
        parent::boot();

        self::saving(function($user){
            Event::fire('user:saving', [$user]);
        });

        self::saved(function($user){
            Event::fire('user:saved', [$user]);
            self::_saved($user);
        });

        self::creating(function($user){
            Event::fire('user:creating', [$user]);
        });

        self::created(function($user){
            Event::fire('user:created', [$user]);
        });

        self::updating(function($user){
            Event::fire('user:updating', [$user]);
        });

        self::updated(function($user){
            Event::fire('user:updated', [$user]);
        });

        self::deleting(function($user){
            Event::fire('user:deleting', [$user]);
        });

        self::deleted(function($user){
            Event::fire('user:deleted', [$user]);
            self::_deleted($user);
        });
    }


    /**
     * Generate the URL to a user
     *
     *
     * @return string
     */
    public function getHrefAttribute(){
        return $this->href();
    }


    /**
     * Generate a custom URL to a user
     * @param string $routeName
     * @param array $customAttributes
     *
     * @return string
     */
    public function href($routeName = '', $customAttributes = []){
        if(!$routeName){
            $routeName = 'user.single';
        }
        $getRoute = Route::getRoutes()->getByName($routeName);
        if($getRoute) {
            $routeParams = Route::getRoutes()->getByName($routeName)->parameterNames();

            // translating language
            if($this->getTranslateLanguage()){
                $languageSlug = $this->getTranslateLanguage();
            }
            else{
                $languageSlug = App::getLocale();
            }

            //set only requested params
            $params = [];
            foreach($routeParams as $name){
                switch ($name){
                    case 'authorSlug';
                        $params['authorSlug'] = $this->slug;
                        break;

                    case 'lang';
                        // don't show language slug on default language
                        if(config('project.hideDefaultLanguageInURL') && $languageSlug !=  Language::getDefault('slug')) {
                            $params['lang'] = $languageSlug;
                        }
                        break;
                }
            }

            if($customAttributes){
                $params = array_merge($customAttributes, $params);
            }

            return route($routeName,$params);
        }else{
            throw new \Exception("Route $routeName not found");
        }

    }

    /**
     * Perform certain actions after a user is saved
     *
     * @param object $user Saved menulink
     * */
    private static function _saved($user){
        //delete existing cache
        Cache::forget('users');
    }

    /**
     * Perform certain actions after a user is deleted
     *
     * @param object $user Deleted user
     * */
    private static function _deleted($user){
        //delete existing cache
        Cache::forget('users');
    }


    /**
     * Full name of the user
     *
     * @return string
     *
     */
    public function getFullNameAttribute(){
        return $this->firstName." ".$this->lastName;
    }

    /**
     * Destruct model instance
     */
    public function __destruct()
    {
        Event::fire('user:destruct', [$this]);
    }


    /**
     * Get posts of users
     */
    public function posts()
    {
        return $this->hasMany('App\Models\Post', 'createdByUserID');
    }

    /**
     * Define single user's SEO Meta data
     *
     * @return array
     */
    public function metaData(){
        Meta::setTitle($this->fullName)
            ->set("description", $this->about)
            ->set("og:type", "profile", "property")
            ->set("og:title", $this->fullName, "property")
            ->set("og:description", $this->about, "property")
            ->set("og:url",$this->href, "property")
            ->setImageOG($this->profileImage)
            ->setProfileOG($this)
            ->setHrefLangData($this)
            ->setCanonical($this->href)
            ->setWildcards([
                '{firstName}' => $this->firstName,
                '{lastName}' => $this->lastName,
                '{siteTitle}' => settings('siteTitle')
            ]);
    }
}