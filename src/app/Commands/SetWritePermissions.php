<?php
/**
 * Created by PhpStorm.
 * User: sopa
 * Date: 16/05/2018
 * Time: 4:15 PM
 */

namespace Accio\App\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Symfony\Component\Process\Process;

class SetWritePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set:permissions {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set write permissions to directories';

    /**
     * List of writable directories
     * @var array
     */
    private $writableDirectories = [
      'storage/tmp',
      'storage/logs',
      'storage/framework',
      'storage/framework/cache',
      'storage/framework/sessions',
      'storage/framework/views',
      'bootstrap/cache',
      'public/uploads',
    ];

    /**
     * @var Process
     */
    private $process;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->process = new Process($this);;
    }

    public function handle()
    {
        $this->setPermissions();
    }

    /**
     * Set permissions to writable directories if in non-production environment
     *
     * @return $this
     */
    private function setPermissions()
    {
        if (!App::environment('production') || $this->option('force')){
            $this->comment("Setting write permissions");
            foreach ($this->writableDirectories as $path) {
                $this->process->setCommandLine('chmod  0777 ' . base_path($path));
                $this->process->setTimeout(null);
                $this->process->run();

                if (!$this->process->isSuccessful()) {
                    $this->console->error("Could not set write permissions to " . $path);
                    break;
                }
            }

            $this->comment("Permissions set successfully");

            return $this;
        }else{
            $this->error("Write permissions can not be set in production environment. use --force option if you are really sure you want to proceed!");
        }
    }

}