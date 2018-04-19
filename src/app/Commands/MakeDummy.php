<?php

namespace Accio\App\Commands;

use App\Models\Language;
use App\Models\Permission;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Mockery\Exception;

class MakeDummy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:dummy {--users} {--postTypes} {--media} {--categories} {--tags} {--posts} {--languages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create dummy content';

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
        $totalSteps = 11;
        $outputs = [];
        $bar = $this->output->createProgressBar($totalSteps);

        // Remove caches
        Cache::flush();

        // STEP 1   -   Ensure there there is a language
        Language::createDefaultLanguage();
        $bar->advance();

        // STEP 2   -   Ensure default roles are already created
        UserGroup::createDefaultRoles();
        $bar->advance();

        // STEP 3   -   Ensure there are some users
        if(!User::count()){
            if(!$this->argument('languages')){
                throw new Exception('No Users found. Create a user with "make:user {email} {password}" command to proceed!');
            }
        }

        // STEP 4   -   Create users (optional)
        $outputs[] = (new \UserDevSeeder())->run($this->option('users'));
        $bar->advance();


        // STEP 5   -   Create language (optional) @TODO check if its really necessary
        //$outputs[] = (new \LanguageDevSeeder())->run($this->option('languages'));
        //$bar->advance();

        // STEP 6   -   Create Default Post Types (if they do not exist)
        $outputs[] = (new \DefaultPostTypesDevSeeder())->run();
        $bar->advance();

        // STEP 7   -   Create Post Types (optional)
        $outputs[] = (new \PostTypeDevSeeder())->run($this->option('postTypes'));
        $bar->advance();

        // STEP 8   -   Create Media
        $outputs[] = (new \MediaDevSeeder())->run($this->option('media'));
        $bar->advance();

        // STEP 9   -   Create Category
        $outputs[] = (new \CategoryDevSeeder())->run($this->option('categories'));
        $bar->advance();

        // STEP 10   -   Create Tags
        $outputs[] = (new \TagDevSeeder())->run($this->option('tags'));
        $bar->advance();

        // STEP 11   -    Create Posts
        $outputs[] = (new \PostDevSeeder())->run($this->option('posts'));
        $bar->advance();

        $bar->finish();

        // Output
        $this->info("\n");
        foreach($outputs as $output){
            if($output){
                $this->info($output);
            }
        }

        // Remove caches
        Cache::flush();

        $this->info('All dummies created successfully');
    }
}
