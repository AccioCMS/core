<?php

namespace Accio\App\Commands;

use App\Models\Language;
use App\Models\Settings;
use App\Models\User;
use App\Models\UserGroup;
use Doctrine\DBAL\Driver\PDOException;
use Illuminate\Console\Command;
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

class AppInstall extends Command{

    use OutputStyles, GetAvailableOptions;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:install {--deleteUploads}';

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
     * @var ConfigRepository
     */
    private $config;

    /**
     * MakeInstall constructor.
     *
     * @param Requirements $requirements
     * @param Environment $environment
     *
     * @return void
     */
    public function __construct(
        ConfigRepository $config,
        Requirements $requirements,
        Environment $environment
    ){
        parent::__construct();

        $this->config = $config;
        $this->requirements = $requirements;
        $this->env = $environment;
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

        // delete current .env file
        if(file_exists(app()->environmentFilePath())){
            File::delete(app()->environmentFilePath());
        }

        // get .env.example or stub file
        $envTemplate = base_path('.env.example');
        if(!file_exists($envTemplate)){
            $envTemplate = stubPath('.env');
        }
        File::put(app()->environmentFilePath(), File::get($envTemplate));
        $this->config->set('app.key', 'SomeRandomString');
        $this->config->set('app.env', 'local');

        $this->block(' -- Welcome to CMS -- ', 'fg=white;bg=green;options=bold');
        $this->line('');
        $this->line('Please answer the following questions:');
        $this->line('');

        // show Bar
        $steps = 14;
        if($this->option('deleteUploads')){
            $steps++;
        }

        $this->bar = $this->output->createProgressBar($steps);

        $this->APP_NAME = $this->ask('Your Site Name', config('app.name'));
        $this->APP_URL = $this->ask('Your App URL', config('app.url'));
        $this->DB_TYPE = $this->ask('Your Database type', config('database.default'));
        $this->DB_HOST = $this->ask('Your DB_HOST', config('database.connections.'.$this->DB_TYPE.'.host'));
        $this->DB_PORT = $this->ask('Your DB PORT', config('database.connections.'.$this->DB_TYPE.'.port'));
        $this->DB_DATABASE = $this->ask('Your Database Name', config('database.connections.'.$this->DB_TYPE.'.database'));
        $this->DB_USERNAME = $this->ask('Your DB Username', config('database.connections.'.$this->DB_TYPE.'.username'));
        $this->DB_PASSWORD = $this->ask('Your DB Password', config('database.connections.'.$this->DB_TYPE.'.password'));

        // Validate Database
        $this->validateDatabase();

        $regions = $this->getTimezoneRegions();
        $this->TIMEZONE = $this->choice('Timezone region', array_keys($regions), 0);
        if ($this->TIMEZONE !== 'UTC') {
            $locations = $this->getTimezoneLocations($regions[$this->TIMEZONE]);
            $this->TIMEZONE .= '/' . $this->choice('Timezone location', $locations, 0);
        }

        $this->askAboutDefaultLanguage();
        $this->ADMIN_FIRST_NAME = $this->ask('Your First Name');
        $this->ADMIN_LAST_NAME = $this->ask('Your Last Name');
        $this->ADMIN_EMAIL = $this->ask('Your Email');
        $this->ADMIN_PASSWORD = $this->ask('Set Your Admin Password');

        $this->saveConfiguration();

        if($this->option('deleteUploads')){
            $this->info("Deleting uploads");
            File::deleteDirectory(public_path('uploads'), true);
            $this->advanceBar();
        }

        $this->info("Running database migration");
        $this->call('migrate',['--force' => true]);
        $this->advanceBar();

        $this->clearCaches();
        $this->optimize();

        // Create Default Language
        $this->info("Creating default language");
        factory(\App\Models\Language::class)->create([
            'name' => $this->PRIMARY_LANGUAGE->name,
            'nativeName' => $this->PRIMARY_LANGUAGE->nativeName,
            'slug' => $this->PRIMARY_LANGUAGE->slug,
            'isDefault' => true
        ]);
        $this->advanceBar();

        $this->info("Creating default roles");
        UserGroup::createDefaultRoles();
        $this->advanceBar();

        $this->info("Creating admin user");
        $this->createAdminUser();
        $this->advanceBar();

        $this->info("Creating default post types");
        (new \DefaultPostTypesDevSeeder())->run();
        $this->advanceBar();

        $this->info("Creating examples media files");
        (new \MediaDevSeeder())->run(10);
        $this->advanceBar();

        // Create tags example
        $this->info("Creating example tags");
        (new \TagDevSeeder())->run();
        $this->advanceBar();

        // Create a category
        $this->info("Creating an example category");
        $categoryObj = new \CategoryDevSeeder();
        $categoryObj->exampleTitles = true;
        $categoryObj->run();
        $this->advanceBar();

        // Create default permalinks
        $this->info("Creating default permalinks");
        (new \PermalinksTableSeeder())->run();
        $this->advanceBar();

        // Creating settings
        $this->info("Saving settings");
        $this->setSettings();
        $this->advanceBar();

        $this->info("Creating example posts");
        (new \PostDevSeeder())->run(3);
        $this->advanceBar();

        // Create Primary Menu
        $this->info("Creating primary Menu");
        $menuSeeder = new \MenuSeeder();
        $menu = $menuSeeder->createPrimaryMenu();
        $menuSeeder->addHomepageToPrimaryMenu($menu);
        $menuSeeder->addAboutToPrimaryMenu($menu);
        $menuSeeder->addCategoryToMenu($menu);
        $this->advanceBar();

        $this->generateKey();
        $this->clearCaches();
        $this->optimize();

        exec('chmod -R 0777 '. uploadsPath().'/*');

        $this->line('');
        $this->block('Success! CMS is now installed', 'fg=black;bg=green');
        $this->line('');
        $this->header('Next steps');
        $this->line('');

        $instructions = [
            'Visit your website <options=bold>' . $this->APP_URL . '</>',
            'Visit administration panel <options=bold>' .$this->APP_URL.'/'.config('project.adminPrefix'). '</> & login with the details you provided to get started',
        ];
        foreach ($instructions as $i => $instruction) {
            if ($i !== 0) {
                $instruction = $i . '. ' . $instruction;
            }
            $this->comment($instruction);
            $this->line('');
        }
        return;
    }

    /**
     * Save configuration in .env file and in config run time
     */
    private function saveConfiguration(){
        $this->info("Writing configuration file");

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
        $this->config->set('app.url', $this->APP_URL);
        $this->config->set('app.name', str_replace(' ','_',$this->APP_NAME));
        $this->config->set('database.connections.mysql.driver', $this->DB_TYPE);
        $this->config->set('database.connections.mysql.host', $this->DB_HOST);
        $this->config->set('database.connections.mysql.port', $this->DB_PORT);
        $this->config->set('database.connections.mysql.database', $this->DB_DATABASE);
        $this->config->set('database.connections.mysql.username', $this->DB_USERNAME);
        $this->config->set('database.connections.mysql.password', $this->DB_PASSWORD);

        // save in app.config
        $content = File::get(config_path('app.php'));
        $newContent = str_replace("'name' => '".config('app.name')."'", "'name' => '".$this->APP_NAME."'", $content);
        FILe::put(config_path('app.php'), $newContent);

        $this->advanceBar();
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

        $language = Language::where('isDefault', 1)->first();
        Settings::setSetting('defaultLanguage', $language->languageID);
    }
    /**
     * Forks a process to create the admin user.
     *
     */
    private function createAdminUser(){
        factory(User::class)->create([
            'firstName' => $this->ADMIN_FIRST_NAME,
            'lastName' => $this->ADMIN_LAST_NAME,
            'slug' => str_slug($this->ADMIN_FIRST_NAME.'-'.$this->ADMIN_LAST_NAME),
            'email' => $this->ADMIN_EMAIL,
            'password' => Hash::make($this->ADMIN_PASSWORD),
            'isActive' => true,
            'groupIDs' => [
                1 => UserGroup::getAdminRole()->groupID
            ]
        ]);
    }
    /**
     * Calls the artisan key:generate to set the APP_KEY.
     */
    private function generateKey()
    {
        $this->info('Generating application key');
        $this->callSilent('key:generate', ['--force' => true]);
        $this->advanceBar();
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

    /**
     * Runs the artisan optimize commands.
     */
    protected function optimize()
    {
        if (!App::environment('local')) {
            $this->callSilent('optimize', ['--force' => true]);
            $this->callSilent('config:cache');
        }
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
            throw new Exception('Connection to database failed: ' . $ex->getMessage());
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
                throw new Exception('Database "' . $this->DB_DATABASE . '" is not empty. Please empty the database or specify another database.');
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
        if(
            // APP KEY is not a a random string
            ($this->config->get('app.key') && $this->config->get('app.key') !== 'SomeRandomString')
        )
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
