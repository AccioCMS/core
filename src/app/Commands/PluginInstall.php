<?php

namespace Accio\App\Commands;

use App\Models\Plugin;
use Chumper\Zipper\Facades\Zipper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Accio\App\Traits\OutputStyles;

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
        }else{
            $this->pluginNamespace = $this->argument('source');
        }

        // Validate and install
        if ($this->canInstall()) {
            $this->info("Installing");
            if ($this->pluginInstance->install($this)) {
                if ($this->addPluginInDB()) {
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
            File::deleteDirectory($this->tmpDirectory, true);
            throw new \Exception('A plugin with the same namespace already exists in plugins directory');
        }

        // Move source to plugins's directory
        $explodeNamespace = explode('/',$this->pluginNamespace);
        // Created organisation directory if it doesn't exist
        if(!file_exists(pluginsPath($explodeNamespace[0]))) {
            File::makeDirectory(pluginsPath($explodeNamespace[0]), 0755, true);
        }

        $directories = File::directories($this->tmpDirectory);
        $pluginPathSource =  $directories[0];

        // Delete created directory if rename can not be executed
        $directories = File::directories($this->tmpDirectory);
        $pluginPathSource =  $directories[0];
        if(!File::move($pluginPathSource, pluginsPath($this->pluginNamespace))){
            File::deleteDirectory(pluginsPath($explodeNamespace[0]), true);
            throw new \Exception('Source could not be moved to plugins directory!');
        }

        // Remove tmp shits
        File::deleteDirectory($this->tmpDirectory, true);

        return $this;
    }

    /**
     * Readon installing plugin's config file
     *
     * @return $this
     * @throws \Exception
     */
    private function readConfigFile(){
        // Get plugin data for config.json
        $directories = File::directories($this->tmpDirectory);
        if(!isset($directories[0])){
            File::deleteDirectory($this->tmpDirectory, true);
            throw new \Exception('No direcetory could be found in downloaded plugin!');
        }

        // Check config.json exists
        $this->info("Reading config");
        $pluginPathSource =  $directories[0];
        if(!file_exists($pluginPathSource.'/config.json')){
            File::deleteDirectory($this->tmpDirectory, true);
            throw new \Exception('config.json file not found');
        }

        $this->configContent = json_decode(File::get($pluginPathSource.'/config.json'));

        return $this;
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
        Zipper::make($this->tmpZipFile)->extractTo($this->tmpDirectory);
        File::delete($this->tmpZipFile);

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
