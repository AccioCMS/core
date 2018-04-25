<?php
/**
 * Created by PhpStorm.
 * User: sopa
 * Date: 26/04/2018
 * Time: 12:22 AM
 */

namespace Accio\App\Services;


use Illuminate\Support\Facades\Artisan;

class ComposerScripts extends Co
{
    /**
     * After composer install is finished
     */
    public static function postPackageInstall(){
        $static = new static();
        $static->replaceExampleEnvWithEnv();
    }

    /**
     * After the project is created
     */
    public static function postCreateProject(){
        // Install Accio
        Artisan::call('php artisan app:install');
    }

    private  function replaceExampleEnvWithEnv(){
        // rename
        copy(base_path('.env.example'), base_path('.env'));

        // remove .env.example
        !unlink(base_path('.env.example'));
    }
}