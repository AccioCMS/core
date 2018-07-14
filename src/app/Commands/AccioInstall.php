<?php

namespace Accio\App\Commands;

use Accio\App\Services\DummyTheme;
use App\Models\Language;
use App\Models\Settings;
use App\Models\User;
use App\Models\UserGroup;
use Cz\Git\GitRepository;
use Doctrine\DBAL\Driver\PDOException;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Larapack\ConfigWriter\Repository;
use Accio\App\Services\Languages;
use Accio\App\Services\Requirements;
use Accio\App\Services\Environment;
use Accio\PackageServiceProvider;
use Mockery\Exception;
use Accio\App\Traits\GetAvailableOptions;
use Accio\App\Traits\OutputStyles;
use Illuminate\Config\Repository as ConfigRepository;
use Symfony\Component\Process\Process;

class AccioInstall extends Command{

    use OutputStyles, GetAvailableOptions;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accio:install {--deleteUploads}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Accio CMS';

    /**
     * if cms is installed
     * @var
     */
    private $isInstalled = false;

    /**
     * App name
     * @var string
     */
    private $APP_NAME;

    /**
     * APP URL
     * @var string
     */
    private $APP_URL;

    /** Database type
     * @var string
     */
    private $DB_TYPE;

    /** Database Host
     * @var string
     */
    private $DB_HOST;

    /** Database Port
     * @var int
     */
    private $DB_PORT;

    /** Database Name
     * @var string
     */
    private $DB_DATABASE;

    /** Database Username
     * @var string
     */
    private $DB_USERNAME;

    /** Database Password
     * @var string
     */
    private $DB_PASSWORD;

    /**
     * Time zone
     * @var string $TIMEZONE
     */
    private $TIMEZONE;

    /**
     * ADMin first name
     * @var string $ADMIN_FIRST_NAME
     */
    private $ADMIN_FIRST_NAME;

    /**
     * Admin last name
     * @var string $ADMIN_LAST_NAME
     */
    private $ADMIN_LAST_NAME;

    /**
     * Admin email
     * @var string $ADMIN_EMAIL
     */
    private $ADMIN_EMAIL;

    /**
     * Admin password
     * @var string $ADMIN_PASSWORD
     */
    private $ADMIN_PASSWORD;

    /**
     * Site's primary language
     * @var object $PRIMARY_LANGUAGE
     */
    private $PRIMARY_LANGUAGE;

    /**
     * @var Requirements
     */
    private $requirements;

    /**
     * @var object
     */
    private $bar;

    /**
     * @var Environment
     */
    private $env;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Process
     */
    private $process;

    /**
     * AccioInstall constructor.
     *
     * @param Requirements $requirements
     * @param Environment $environment
     * @param Filesystem $filesystem
     */
    public function __construct(
      Requirements $requirements,
      Environment $environment,
      Filesystem $filesystem
    ){
        parent::__construct();

        $this->requirements = $requirements;
        $this->env = $environment;
        $this->filesystem = $filesystem;
        $this->process = new Process($this);
    }


    /**
     * Execute the console command.
     *
     * @throws \Exception
     */
    public function handle(){

        $this->clearCaches();

        if($this->isInstalled()){
            return false;
        }

        if(!$this->requirements->check($this)){
            return false;
        }

        $this
          ->welcomeMessage()
          ->setBar()
          ->askInstallingQuestions()
          ->renameEnvFile()
          ->setEnvValues()
          ->saveConfiguration()
          ->deleteUploads()
          ->runMigration()
          ->createDummyContent()
          ->createDefaultTheme()
          ->generateKey()
          ->successfullyInstalled();
        return;
    }

    /**
     * Create dummy theme.
     *
     * @return $this
     * @throws \Cz\Git\GitException
     * @throws \Exception
     */
    private function createDefaultTheme(){
        $this->info("Creating default theme...");

        // create theme if it doesn't exist
        if(!file_exists(base_path('themes/'.config('project.defaultTheme')))) {
            (new DummyTheme([
              'title' => 'Default Theme',
              'namespace' => 'DefaultTheme',
              'organisation' => 'Manaferra',
              'authorName' => 'Faton Sopa',
              'authorEmail' => 'fatom.sopa@manaferra.com',
              'auth' => true,
              'activate' => true,
            ]))->make();
        }

        // Delete default theme, so we later get the latest version from git
        $defaultThemePath = base_path('themes/'.config('project.defaultTheme'));
        if(file_exists($defaultThemePath)){
            File::deleteDirectory($defaultThemePath);
        }

        // clone theme

        // Recreate an empty directory for theme
        if(!file_exists($defaultThemePath)) {
//            File::makeDirectory($defaultThemePath);
        }
        
        GitRepository::cloneRepository('https://github.com/AccioCMS/default-theme.git', $defaultThemePath);

        $this->advanceBar();

        return $this;
    }
    /**
     * Set progress bar
     *
     * @param int $steps
     * @return $this
     */
    private function setBar($steps = 15){
        if($this->option('deleteUploads')){
            $steps++;
        }

        $this->bar = $this->output->createProgressBar($steps);

        return $this;
    }
    /**
     * Set welcome message
     *
     * @return $this
     */
    private function welcomeMessage(){
        $this->block(' -- Welcome to CMS -- ', 'fg=white;bg=green;options=bold');
        $this->line('');
        $this->line('Please answer the following questions:');
        $this->line('');

        return $this;
    }
    /**
     * The response when accio is installed sucessfully
     * @return $this
     */
    private function successfullyInstalled(){
        $this->line('');
        $this->block('Success! Accio is now installed', 'fg=black;bg=green');
        $this->line('');
        $this->header('Next steps');
        $this->line('');

        $instructions = [
          'Visit your website <options=bold>' . $this->APP_URL . '</>',
          'Visit administration panel <options=bold>' .$this->APP_URL.'/'.config('project.adminPrefix'). '</> & login with the details you provided to get started',
          'You may need to set write permissions to public directories by executing the following command: "php artisan set:permissions"',
        ];
        foreach ($instructions as $i => $instruction) {
            if ($i !== 0) {
                $instruction = $i . '. ' . $instruction;
            }
            $this->comment($instruction);
            $this->line('');
        }
        return $this;
    }
    /**
     * Run migration
     *
     * @return $this
     */
    private function runMigration(){
        $this->info("Running database migration...");
        $this->call('migrate',['--force' => true]);

        $this->line('');
        $this->advanceBar();

        $this->clearCaches();
        return $this;
    }
    /**
     * Delete uploads
     * @return $this
     */
    private function deleteUploads(){
        if($this->option('deleteUploads')){
            $this->info("Deleting uploads...");
            File::deleteDirectory(public_path('uploads'), true);
            $this->advanceBar();
        }
        return $this;
    }

    /**
     * Create dummy content
     *
     * @return $this
     * @throws \Exception
     */
    private function createDummyContent(){
        $this->info("Creating default roles...");
        UserGroup::createDefaultRoles();
        $this->advanceBar();

        $this->info("Creating admin user...");
        $this->createAdminUser();
        $this->advanceBar();

        // Create Default Language
        $this->info("Creating default language...");
        factory(\App\Models\Language::class)->create([
          'name' => $this->PRIMARY_LANGUAGE->name,
          'nativeName' => $this->PRIMARY_LANGUAGE->nativeName,
          'slug' => $this->PRIMARY_LANGUAGE->slug,
          'isDefault' => true
        ]);
        $this->advanceBar();

        $this->info("Creating default post types...");
        (new \DefaultPostTypesDevSeeder())->run();
        $this->advanceBar();

        $this->info("Creating example media...");
        (new \MediaDevSeeder())->run(20);
        $this->advanceBar();

        // Create tags example
        $this->info("Creating example tags...");
        (new \TagDevSeeder())->run(20, null, true);
        $this->advanceBar();

        // Create a category
        $this->info("Creating an example category...");
        $categoryObj = new \CategoryDevSeeder();
        $categoryObj->exampleTitles = true;
        $categoryObj->run(3, null);
        $this->advanceBar();

        // Create default permalinks
        $this->info("Creating default permalinks...");
        (new \PermalinksTableSeeder())->run();
        $this->advanceBar();

        // Creating settings
        $this->info("Saving settings...");
        $this->setSettings();
        $this->advanceBar();

        $this->info("Creating example posts...");
        (new \PostDevSeeder())->run(0, 5,'', 0, 0, 0, true);
        $this->advanceBar();

        // Create Primary Menu
        $this->info("Creating primary Menu...");
        $menuSeeder = new \MenuSeeder();
        $menu = $menuSeeder->createPrimaryMenu();
        $menuSeeder->addHomepageToPrimaryMenu($menu);
        $menuSeeder->addAboutToPrimaryMenu($menu);
        $menuSeeder->addCategoryToMenu($menu);
        $this->advanceBar();

        return $this;
    }


    /**
     * Save configuration in .env file and in config run time.
     *
     * @return $this
     * @throws \Exception
     */
    private function saveConfiguration(){
        $this->info("Writing configuration file...");

        // Save in .env file
        $this->env->setEnv([
          'APP_URL' => $this->APP_URL,
          'DB_CONNECTION' => $this->DB_TYPE,
          'DB_HOST' => $this->DB_HOST,
          'DB_PORT' => $this->DB_PORT,
          'DB_DATABASE' => $this->DB_DATABASE,
          'DB_USERNAME' => $this->DB_USERNAME,
          'DB_PASSWORD' => $this->DB_PASSWORD,
        ]);

        // save in runtime

        config(['app.url' => $this->APP_URL]);
        config(['app.name' => str_replace(' ','_',$this->APP_NAME)]);
        config(['database.connections.mysql.driver' => $this->DB_TYPE]);
        config(['database.connections.mysql.host' => $this->DB_HOST]);
        config(['database.connections.mysql.port' => $this->DB_PORT]);
        config(['database.connections.mysql.database' => $this->DB_DATABASE]);
        config(['database.connections.mysql.username' => $this->DB_USERNAME]);
        config(['database.connections.mysql.password' => $this->DB_PASSWORD]);

        // save in app.config
        $content = File::get(config_path('app.php'));
        $newContent = str_replace(
          "'name' => '".config('app.name')."'",
          "'name' => '".$this->APP_NAME."'",
          $content);

        FILe::put(config_path('app.php'), $newContent);

        $this->advanceBar();

        $this->clearCaches();

        return $this;
    }

    /**
     * Ask installing questions
     *
     * @return $this
     * @throws \Exception
     */
    private function askInstallingQuestions(){
        // Database information
        $this->DB_TYPE = $this->ask('Your Database type', config('database.default'));
        $this->DB_HOST = $this->ask('Your DB_HOST', config('database.connections.'.$this->DB_TYPE.'.host'));
        $this->DB_PORT = $this->ask('Your DB PORT', config('database.connections.'.$this->DB_TYPE.'.port'));
        $this->DB_DATABASE = $this->ask('Your Database Name', config('database.connections.'.$this->DB_TYPE.'.database'));
        $this->DB_USERNAME = $this->ask('Your DB Username', config('database.connections.'.$this->DB_TYPE.'.username'));
        $this->DB_PASSWORD = $this->secret('Your DB Password');

        // Validate Database
        $this->validateDatabase();

        $this->APP_NAME = $this->ask('Your Site Name', config('app.name'));
        $this->APP_URL = $this->ask('Your App URL', config('app.url'));

        // Timezone
        $regions = $this->getTimezoneRegions();
        $this->TIMEZONE = $this->choice('Timezone region', array_keys($regions), 0);
        if ($this->TIMEZONE !== 'UTC') {
            $locations = $this->getTimezoneLocations($regions[$this->TIMEZONE]);
            $this->TIMEZONE .= '/' . $this->choice('Timezone location', $locations, 0);
        }

        $this->askAboutDefaultLanguage();

        // User information
        $this->ADMIN_FIRST_NAME = $this->ask('Your First Name');
        $this->ADMIN_LAST_NAME = $this->ask('Your Last Name');
        $this->ADMIN_EMAIL = $this->ask('Your Email');
        $this->ADMIN_PASSWORD = $this->ask('Set Your Admin Password');

        return $this;
    }


    /**
     * Set CMS Settings
     * @return void
     */
    private function setSettings(){
        Settings::setSetting('siteTitle', $this->APP_NAME);
        Settings::setSetting('adminEmail', $this->ADMIN_EMAIL);
        Settings::setSetting('defaultUserRole', 'admin');
        Settings::setSetting('timezone', $this->TIMEZONE);
        Settings::setSetting('logo', 1);
        Settings::setSetting('activeTheme', config('project.defaultTheme'));
        Settings::setSetting('activateMobileTheme', false);
        Settings::setSetting('mobileActiveTheme', '');
        Settings::setSetting('trackingCode', '');
        Settings::setSetting('useTagManager', 0);
        Settings::setSetting('tagManager', '');
        Settings::setSetting('watermark', '');

        $language = Language::where('isDefault', 1)->first();
        Settings::setSetting('defaultLanguage', $language->languageID);
    }
    /**
     * Forks a process to create the admin user.
     *
     */
    private function createAdminUser(){
        $user = factory(User::class)->create([
          'firstName' => $this->ADMIN_FIRST_NAME,
          'lastName' => $this->ADMIN_LAST_NAME,
          'slug' => str_slug($this->ADMIN_FIRST_NAME.'-'.$this->ADMIN_LAST_NAME),
          'email' => $this->ADMIN_EMAIL,
          'password' => Hash::make($this->ADMIN_PASSWORD),
          'isActive' => true
        ]);

        // assign role
        $user->assignRoles(UserGroup::getAdminGroup()->groupID);
    }
    /**
     * Calls the artisan key:generate to set the APP_KEY.
     *
     * return $this;
     */
    private function generateKey()
    {
        $this->info('Generating application key');
        $this->callSilent('key:generate', ['--force' => true]);
        $this->advanceBar();

        $this->clearCaches();

        return $this;
    }

    /**
     * Clears all Laravel caches.
     */
    protected function clearCaches()
    {
        $this->callSilent('clear-compiled');
        $this->callSilent('cache:clear');
        $this->callSilent('route:clear');
        $this->callSilent('config:clear');
        $this->callSilent('view:clear');
        Cache::flush();
    }

    /*
    *
    * Rename .env file
    *
    * @return $this
    */
    private function renameEnvFile(){
        // delete current .env file
        if(file_exists(app()->environmentFilePath())){
            File::delete(app()->environmentFilePath());
        }

        // get .env.example or stub file
        $envTemplate = base_path('.env.example');
        if(!file_exists($envTemplate)){
            $envTemplate = stubPath('.env');
        }

        // Rename .env.example to .env
        File::put(app()->environmentFilePath(), File::get($envTemplate));

        // remove .env.example
        if(file_exists(base_path('.env.example'))){
            File::delete(base_path('.env.example'));
        }

        return $this;
    }

    /**
     * Set env values
     */
    private function setEnvValues(){
        config(['app.key' => 'SomeRandomString']);
        config(['app.env' => 'local']);
        return $this;
    }

    /**
     * Validate Database
     * @return void
     * @throws Exception
     */
    private function validateDatabase(){
        /*
        * Check Database Connection
        */
        $dsn = $this->DBConnectComand();
        try {
            $DBConnection = new \PDO($dsn, $this->DB_USERNAME, $this->DB_PASSWORD, array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION));
        }
        catch (PDOException $ex) {
            throw new \Exception('Connection to database failed: ' . $ex->getMessage());
        }

        /*
         * Check if the database is empty on install
         */
        if(!$this->isInstalled) {
            $tables = 0;
            $fetch = $this->checkDatabase($DBConnection);
            while ($result = $fetch->fetch()){
                $tables++;
                break;
            }
            if ($tables > 0) {
                throw new \Exception('Database "' . $this->DB_DATABASE . '" is not empty. Please empty the database or specify another database.');
            }
        }

        return;
    }

    /**
     * Create db connection command for PDO
     * @return string
     */
    private function DBConnectComand(){
        /*
        * Check Database Connection
        */
        switch ($this->DB_TYPE){
            case 'sqlite':
                $dsn = 'sqlite:'.$this->DB_DATABASE;
                $this->validateSqliteFile($this->DB_DATABASE);
                break;

            case 'pgsql':
                $dsn ='pgsql:host='.$this->DB_HOST.';dbname='.$this->DB_DATABASE.';port='.$this->DB_PORT;
                break;

            case 'sqlsrv':
                $availableDrivers = \PDO::getAvailableDrivers();
                if (in_array('dblib', $availableDrivers)) {
                    $dsn = 'dblib:host='.$this->DB_HOST.$this->DB_PORT.';dbname='.$this->DB_DATABASE;
                }
                else {
                    $dsn = 'sqlsrv:Server='.$this->DB_HOST.$this->DB_PORT.';Database='.$this->DB_DATABASE;
                }
                break;

            default:
                $dsn = 'mysql:host='.$this->DB_HOST.';dbname='.$this->DB_DATABASE.';port='.$this->DB_PORT;
                break;
        }

        return $dsn;
    }

    /**
     * @param \PDO $DBConnection
     * @return mixed
     */
    private function checkDatabase($DBConnection){
        switch ($this->DB_TYPE){
            case 'sqlite':
                $fetch = $DBConnection->query("select name from sqlite_master where type='table'", \PDO::FETCH_NUM);
                break;

            case 'pgsql':
                $fetch = $DBConnection->query("select table_name from information_schema.tables where table_schema = 'public'", \PDO::FETCH_NUM);
                break;

            case 'sqlsrv':
                $fetch = $DBConnection->query("select [table_name] from information_schema.tables", \PDO::FETCH_NUM);
                break;

            default:
                $fetch = $DBConnection->query('show tables', \PDO::FETCH_NUM);
                break;
        }

        return $fetch;
    }

    /**
     * Validate that sql file exist
     * @param string $DB_DATABASE
     * @return void
     */
    protected function validateSqliteFile($DB_DATABASE)
    {
        if (!file_exists($DB_DATABASE)) {

            $directory = dirname($DB_DATABASE);
            if (!is_dir($directory))
                mkdir($directory, 0644, true);

            new \PDO('sqlite:' . $DB_DATABASE);
        }
        return;

    }

    private function advanceBar(){
        $this->bar->advance();
        $this->line('');
        $this->line('');
    }

    /**
     * @return bool
     */
    private function isInstalled(){

        if(config('app.key') && config('app.key') !== 'SomeRandomString')
        {
            $this->failure(
              'You have already installed CMS!',
              'If you were trying to update CMS, please use "php artisan app:update" or run "php artisan app:uninstall" to delete current instalation.',
              'If you were trying to reinstall CMS, you have to first uninstall it by running php artisan app::uninstall');

            return true;
        }
        return false;
    }

    /**
     * Ask about default langauge
     * @throws \Exception
     */
    private function askAboutDefaultLanguage(){
        $languageList = [];
        foreach(Language::ISOlist() as $language){
            $languageList[] = $language->name;
        }

        $languageName = $this->anticipate('What is your site\'s primary language?', $languageList, 'English');
        $this->PRIMARY_LANGUAGE = Language::getISOByName($languageName);
        if(!$this->PRIMARY_LANGUAGE){
            throw  new \Exception('Langauge could not be found in ISO 639.1 list!');
        }
    }


}