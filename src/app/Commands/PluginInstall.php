<?php

namespace Accio\App\Commands;

use App\Models\Plugin;
use Chumper\Zipper\Facades\Zipper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Accio\App\Traits\OutputStyles;
use Cz\Git\GitRepository;

class PluginInstall extends Command
{

    use OutputStyles;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:install {source}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install a plugin';

    /**
     * Saves plugin namespace
     * @var string $pluginNamespace
     */
    protected $pluginInstance;

    /**
     * @var string $pluginNamespace
     */
    protected $pluginNamespace;

    /**
     * @var string $tmpZipFile
     */
    protected $tmpZipFile;

    /**
     * @var string $tmpDirectory
     */
    protected $tmpDirectory;

    /**
     * @var string $tmpRandomName;
     */
    protected $tmpRandomName;

    /**
     * Installing plugin's config content
     * @var object $configContent
     */
    protected $configContent;

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
     *
     * Execute the console command.
     *
     * @return mixed
     * @throws \Exception
     */
    public function handle(){
        // Ensure there are no caches
        $this->callSilent('cache:clear');

        // Install plugin from a http/s source
        if(strstr($this->argument('source'),'http')){
            $this
              ->getZip()
              ->extractZip()
              ->readConfigFile()
              ->copySourceContent();
        }
        // clone from git
        elseif(strstr($this->argument('source'),'git')){
            $this->cloneFromGit()->readConfigFile()->copySourceContent();
        }else{
            $this->pluginNamespace = $this->argument('source');
        }

        // Validate and install
        if ($this->canInstall()) {
            $this->info("Installing");
            if ($this->pluginInstance->install($this)) {
                if ($this->addPluginInDB()) {
                    $this->cleanTmp();

                    $this->line('');
                    $this->block('Plugin installed successfully', 'fg=black;bg=green');
                    $this->line('');
                    $this->comment('Next step: Execute "npm run watch" to compile view files');
                    $this->line('');
                }
            }
        }
    }

    /**
     * CLone form git.
     *
     * @throws \Cz\Git\GitException
     * @return $this;
     */
    private function cloneFromGit(){
        $this->tmpDirectory = tmpPath().'/'.time();
        GitRepository::cloneRepository($this->argument('source'), $this->tmpDirectory);
        return $this;
    }
    /**
     * Checks if a plugin can be installed
     *
     * @return bool
     * @throws \Exception
     */
    private function canInstall(){
        $this->info("Validating");

        // Exists as a directory
        if(!file_exists(pluginsPath($this->pluginNamespace))){
            throw new \Exception("Plugin ".$this->pluginNamespace." not found in plugins directory!");
        }

        // Remove composer autoloader
        exec('composer dump-autoload', $output, $return_var);

        // Clear laravel compiled files
        $this->callSilent('clear-compiled');

        // Plugin class exists
        $fullNamespace =  'Plugins\\'.str_replace('/','\\', $this->pluginNamespace.'\\Plugin');

        if(!class_exists($fullNamespace)){
            // When composer autoload was generated, we didn't have this plugin's classes
            // that's why we have to autoload all Plugin' classes manually
            spl_autoload_register(function ($class) {
                $class = str_replace("\\", "/", $class);
                $class = str_replace("Plugins", "plugins", $class);
                include base_path().'/' . $class. '.php';
            });
        }

        // Make sure Plugin namespace class exist
        if(!class_exists($fullNamespace)){
            throw new \Exception("Class $fullNamespace not found!");
        }

        // Plugin needs to have an "install" method
        $this->pluginInstance = new $fullNamespace();
        if(!method_exists($this->pluginInstance, 'install')){
            throw new \Exception("Plugin ".$this->pluginNamespace." does not have an install method!");
        }

        // Plugin should not be in DB Table
        if(Plugin::where('namespace',$this->pluginNamespace)->first()){
            throw new \Exception("Plugin ".$this->pluginNamespace." already exists in DB!");
        }

        // Config file exist
        if(!file_exists(pluginsPath($this->pluginNamespace.'/config.json'))) {
            throw new \Exception("Plugin ".$this->pluginNamespace." config.file does not exists!");
        }
        return true;
    }

    /**
     * Copy source content to plugins directory
     *
     * @return $this
     * @throws \Exception
     */
    private function copySourceContent(){
        // Move plugin to Plugin's directory
        $this->info("Copying source to plugins directory");
        $this->pluginNamespace = str_replace('\\','/',$this->configContent->namespace);

        if(file_exists(pluginsPath($this->pluginNamespace))){
            $this->cleanTmp();
            throw new \Exception('A plugin with the same namespace already exists in plugins directory');
        }

        // Created organisation directory if it doesn't exist
        $explodeNamespace = explode('/',$this->pluginNamespace);
        if(!file_exists(pluginsPath($explodeNamespace[0]))) {
            File::makeDirectory(pluginsPath($explodeNamespace[0]), 0755, true);
        }

        // Delete created directory if rename can not be executed
        if(!File::move($this->tmpDirectory, pluginsPath($this->pluginNamespace))){
            $this->cleanTmp();
            throw new \Exception('Source could not be moved to plugins directory!');
        }

        return $this;
    }

    /**
     * Clean tmp directories
     * @return $this
     */
    private function cleanTmp(){
        $directories = File::directories(tmpPath());
        foreach($directories as $directory){
            File::deleteDirectory($directory);
        }

        return $this;
    }

    /**
     * Readon installing plugin's config file
     *
     * @return $this
     * @throws \Exception
     */
    private function readConfigFile(){
        // Check config.json exists
        $this->info("Reading config");

        // look for config in main directory
        if(file_exists($this->tmpDirectory.'/config.json')){
            $this->configContent = json_decode(File::get($this->tmpDirectory.'/config.json'));
            return $this;
        }

        // look for config in the first directory
        // useful in case the plugin is downloaded via http
        $directories = File::directories($this->tmpDirectory);
        if(isset($directories[0])) {
            $pluginPathSource = $directories[0];
            if (file_exists($pluginPathSource . '/config.json')) {
                $this->configContent = json_decode(File::get($pluginPathSource.'/config.json'));
                return $this;
            }
        }

        // no config.json found
        $this->cleanTmp();
        throw new \Exception('config.json file not found');
    }

    /**
     * Get zip from source
     * @return $this
     * @throws \Exception
     */
    private function getZip(){
        $this->info("Downloading");

        $sourceContent = file_get_contents($this->argument('source'));
        if(!$sourceContent) {
            throw new \Exception('Source could not be found!');
        }

        $this->tmpRandomName = time();
        $this->tmpZipFile = tmpPath($this->tmpRandomName.'.zip');

        // Save zip to tmp directory
        if(!file_put_contents($this->tmpZipFile, $sourceContent)){
            File::delete($this->tmpZipFile);
            throw new \Exception('Could not copy source file to path '.$this->tmpZipFile);
        }

        return $this;
    }
    /**
     * Read zip file
     * @return $this
     */
    private function extractZip(){
        $this->info("Extracting");
        $this->tmpDirectory = tmpPath($this->tmpRandomName);

        // extract
        Zipper::make($this->tmpZipFile)->extractTo($this->tmpDirectory);

        // delete zip file
        File::delete($this->tmpZipFile);

        // zip creates a parent directory on extract, pass it
        $directories = File::directories($this->tmpDirectory);
        if(isset($directories[0])){
            $this->tmpDirectory = $directories[0];
        }

        return $this;
    }

    /**
     * Add plugin in database
     *
     * @param string $namespace
     * @return mixed
     */
    private function addPluginInDB(){
        $configContent = Plugin::config($this->pluginNamespace);

        $plugin = new Plugin();
        $plugin->title = $configContent->title;
        $plugin->namespace = $configContent->namespace;
        $plugin->organization = $configContent->organization;
        $plugin->version = $configContent->version;
        $plugin->isActive = 1;
        return $plugin->save();
    }
}
