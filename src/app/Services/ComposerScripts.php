<?php
/**
 * Created by PhpStorm.
 * User: sopa
 * Date: 26/04/2018
 * Time: 12:22 AM
 */

namespace Accio\App\Services;


use Illuminate\Support\Facades\Artisan;

class ComposerScripts
{
    /**
     * Handle the post-root-package-install Composer script.
     * After composer install is finished
     */
    public static function postPackageInstall(){
    }

    /**
     * Handle the post-create-project-cmd Composer script.
     * After the project is created
     */
    public static function postCreateProject(){
        // Install Accio
        Artisan::call('php artisan app:install');
    }

}