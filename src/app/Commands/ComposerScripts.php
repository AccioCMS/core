<?php
/**
 * Created by PhpStorm.
 * User: sopa
 * Date: 26/04/2018
 * Time: 12:22 AM
 */

namespace Accio\App\Services;

use Illuminate\Console\Command;

class ComposerScripts extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:requirements';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Server compatibility ';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    
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
        
    }

}