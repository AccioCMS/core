<?php

namespace Accio\App\Models;

use Accio\App\Traits\BootEventsTrait;
use Accio\App\Traits\CollectionTrait;
use App\Models\Media;
use App\Models\Settings;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class SettingsModel extends Model
{

    use
        Cachable,
        LogsActivity,
        BootEventsTrait,
        CollectionTrait;

    /**
     * Fields that can be filled.
     *
     * @var array $fillable
     */
    protected $fillable = ['settingsKey','value'];

    /**
     * The primary key of the table.
     *
     * @var string $primaryKey
     */
    public $primaryKey = "settingsID";

    /**
     * Default number of rows per page to be shown in admin panel.
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
     *
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
     * @var bool
     */
    protected static $logFillable = true;

    /**
     * @var bool
     */
    protected static $logOnlyDirty = true;

    /**
     * Get Project Logo.
     *
     * @return mixed
     */
    public static function logo()
    {
        return Media::find(settings('logo'));
    }

    /**
     * Get all settings as a list
     *
     * @return array
     * @throws \Exception
     */
    public static function getAllSettings()
    {
        $settings = Settings::all();

        $settingsList = [];
        foreach($settings as $setting){
            $settingsList[$setting->settingsKey] = $setting->value;
        }

        return $settingsList;
    }

    /**
     * Get a setting
     *
     * @param  $key
     * @throws \Exception
     */
    public static function getSetting($key)
    {
        $setting = Settings::all()->where('settingsKey', $key)->first();
        if($setting) {
            return $setting->value;
        }
        return;
    }

    /**
     * Add or update an item in settings.
     *
     * @param  $key
     * @param  $value
     * @return object
     */
    public static function setSetting($key, $value)
    {
        $result = Settings::updateOrCreate(['settingsKey' => $key], ['value' => $value]);
        return $result;
    }
}
