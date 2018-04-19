<?php

namespace Accio\App\Models;

use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SettingsModel extends Model{

    /**
     * Fields that can be filled
     *
     * @var array $fillable
     */
    protected $fillable = ['settingsKey','value'];

    /**
     * The primary key of the table
     *
     * @var string $primaryKey
     */
    public $primaryKey = "settingsID";

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
    public $table = "settings";

    /**
     * Lang key that points to the multi language label of a
     * @var string
     */
    public static $label = "settings.label";

    /**
     * Default permission that will be listed in settings of permissions
     *
     * @var array $defaultPermissions
     */
    public static $defaultPermissions = ['create','read', 'update', 'delete'];

    /**
     * Get settings from cache. Cache is generated if not found
     *
     * @return object|null  Returns requested cache if found, null instead
     */
    public static function getFromCache(){
        if(!Cache::has('settings')){
            $getData  = self::all()->keyBy('settingsKey');
            Cache::forever('settings',$getData);
            return $getData;
        }
        return Cache::get('settings');
    }

    /**
     * Handle callback of insert, update, delete
     * */
    protected static function boot(){
        parent::boot();
        // watch for saving queries
        self::saved(function($settings){
            self::_saved($settings);
        });
        // watch for deletion queries
        self::deleted(function($settings){
            self::_deleted($settings);
        });
    }

    /**
     * Perform certain actions after a setting is saved
     *
     * @param object $setting Saved setting
     * */
    private static function _saved($setting){
        Cache::forget('settings');
    }

    /**
     * Perform certain actions after a setting is deleted
     *
     * @param object $setting Deleted setting
     * */
    private static function _deleted($setting){
        Cache::forget('settings');
    }

    /**
     * Get Project Logo
     * @return HasOne
     */
    public static function logo()
    {
        return Media::find(settings('logo'));
    }


    /**
     * Get all settings as a list
     * @@return array
     */
    public static function getAllSettings(){
        $settings = self::getFromCache();

        $settingsList = [];
        foreach($settings as $setting){
            $settingsList[$setting->settingsKey] = $setting->value;
        }

        return $settingsList;
    }

    /**
     * Get a setting
     * @param string $key
     */
    public static function getSetting($key){
        if(self::getFromCache()) {
            $setting = self::getFromCache()->where('settingsKey', $key);

            if ($setting->count()) {
                return $setting->first()->value;
            }
        }
        return;
    }

    /**
     * Add or update an item in settings
     * @param $key
     * @param $value
     * @return object
     */
    public static function setSetting($key, $value){
        $result = self::updateOrCreate(['settingsKey' => $key], ['value' => $value]);
        return $result;
    }
}