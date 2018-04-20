<?php

namespace Accio\App\Commands;

use Illuminate\Console\Command;

class DeploySetPermissions extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set permissions on deploy';

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
        $this->chown();
        $this->permissions();
    }

    /**
     * Set permissions
     *
     * @throws \Exception
     */
    private function permissions(){
        // Set chmod directories
        $chmodPermissions = config('deploy.permissions.chmod');
        if($chmodPermissions ){
            $this->comment("\nSetting chmod permissions");

            foreach($chmodPermissions  as $permission){
                if($permission['path'] && file_exists($permission['path'])) {
                    $command = "";

                    // add slash on end
                    $endsWith = substr($permission['path'],-1);
                    if($endsWith !== '/'){
                        $permission['path'] .= '/';
                    }

                    $onlyDirectories = false;
                    if(isset($permission['only_directories']) && $permission['only_directories']){
                        $onlyDirectories = true;
                    }

                    $onlyFiles = false;
                    if(isset($permission['only_files']) && $permission['only_files']){
                        $onlyFiles = true;
                    }

                    $isRecursive = false;
                    if(isset($permission['recursive']) && $permission['recursive']){
                        $isRecursive = true;
                    }

                    // command to set permission only of files
                    if($onlyDirectories){
                        $command .= 'find ' . $permission['path'] . ' -type d -exec ';
                    }
                    // command to set permission only of directories
                    elseif($onlyFiles){
                        $command .= 'find ' . $permission['path'] . ' -type f -exec ';
                    }

                    $command .='chmod '.$permission['permission'].' ';

                    if(!$onlyFiles && !$onlyDirectories){
                        // Set recursive mode
                        if($isRecursive){
                            $command .= '-R ';
                        }

                        $command .= $permission['path'].' ';
                    }else{
                        $command .= ' {} \\;';
                    }


                    $this->info("Running command: '".$command."'");

                    exec($command, $shellResponse, $status);
                    if ($status != 0) {
                        throw new \Exception("Command '$command' could not be run!");
                    }
                }
            }

            $this->info("Chmod permissions set!");
        }
    }

    /**
     *
     * Set apache's user & groups permissions
     *
     * @throws \Exception
     */
    private function chown(){
        // Set chmod directories
        $chownPermissions = config('deploy.permissions.apache');
        if($chownPermissions ){
            $this->comment("\nSetting apache permissions");

            foreach($chownPermissions  as $permission){
                if(!isset($permission['group']) || !$permission['group']){
                    throw new \Exception("No group defined for path  '".permission['path']."'!");
                }

                if($permission['path'] && file_exists($permission['path'])) {
                    $command = "";

                    // add slash on end
                    $endsWith = substr($permission['path'],-1);
                    if($endsWith !== '/'){
                        $permission['path'] .= '/';
                    }

                    $onlyDirectories = false;
                    if(isset($permission['only_directories']) && $permission['only_directories']){
                        $onlyDirectories = true;
                    }

                    $onlyFiles = false;
                    if(isset($permission['only_files']) && $permission['only_files']){
                        $onlyFiles = true;
                    }

                    $isRecursive = false;
                    if(isset($permission['recursive']) && $permission['recursive']){
                        $isRecursive = true;
                    }

                    // command to set permission only of files
                    if($onlyDirectories){
                        $command .= 'find ' . $permission['path'] . ' -type d -exec ';
                    }
                    // command to set permission only of directories
                    elseif($onlyFiles){
                        $command .= 'find ' . $permission['path'] . ' -type f -exec ';
                    }

                    $command .='chown ';

                    //user
                    if(isset($permission['user']) && $permission['user']){
                        $command .= $permission['user'];
                    }

                    // group
                    $command .= ':'.$permission['group'];

                    if(!$onlyFiles && !$onlyDirectories){
                        // Set recursive mode
                        if($isRecursive){
                            $command .= '-R ';
                        }

                        $command .= $permission['path'].' ';
                    }else{
                        $command .= ' {} \\;';
                    }

                    $this->info("Running command: '".$command."'");

                    $response = exec($command, $shellResponse, $status);

                    exec($command, $shellResponse, $status);
                    if ($status != 0) {
                        throw new \Exception("Apache permission could not be set. Command '$command' could not be run!");
                    }
                }
            }

            $this->info("Apache permissions set!");
        }
    }

}