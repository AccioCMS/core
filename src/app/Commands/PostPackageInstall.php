<?php
/**
 * Created by PhpStorm.
 * User: sopa
 * Date: 23/02/2018
 * Time: 8:43 AM
 */

namespace Accio\App\Commands;

use Illuminate\Console\Command;
use Accio\App\Services\Requirements;
use Accio\App\Traits\OutputStyles;

class PostPackageInstall extends Command
{
    use OutputStyles;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accio::PostPackageInstall';


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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

    }

}
