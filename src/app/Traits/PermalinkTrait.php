<?php
/**
 * Created by PhpStorm.
 * User: sopa
 * Date: 26/09/2017
 * Time: 3:30 PM
 */

namespace Accio\App\Traits;

use App\Models\Permalink;
use Illuminate\Support\Facades\Cache;
use Mockery\Exception;

trait PermalinkTrait
{
    /**
     * Get a permalink by name
     *
     * @param string $belongsTo App name
     * @param string $name Represents route name ex. login
     * @param string $defaultURL
     *
     * @return string Returns custom url if found, null instead
     *
     */
    public static function getByName($belongsTo, $name, $defaultURL = '')
    {
        //find by full name
        $permalinks = Permalink::getFromCache();
        $singlePermalink = false;

        if ($permalinks) {
            $singlePermalink = $permalinks->where('belongsTo', $belongsTo)->where("name", $name)->first();
            if ($singlePermalink && $singlePermalink->custom_url) {
                return $singlePermalink->custom_url;
            }
        }

        if(!$singlePermalink){
            if(!$defaultURL) {
                throw new \Exception("Permalink or default url not found in '$belongsTo' '$name' ");
            }
            return $defaultURL;
        }

        return $singlePermalink->default_url;
    }
}