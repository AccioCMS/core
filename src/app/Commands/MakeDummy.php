<?php

namespace Accio\App\Commands;

use App\Models\Language;
use App\Models\Permission;
use App\Models\PostType;
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
    protected $signature = 'make:dummy {--all} {--users=} {--users_per_role=} {--role_id=} {--post_types=} {--media=} {--categories=} {--categories_per_post_type=} {--category=} {--tags=} {--tags_per_post_type=} {--posts=} {--post_type=} {--posts_per_category=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create dummy content';

    /**
     * @var array
     */
    public $defaultsOptions = [
        'users' => 5,
        'users_per_role' => 0,
        'role_id' => 0,
        'post_types' => 1,
        'post_type' => '',
        'media' => 20,
        'categories' => 5,
        'categories_per_post_type' => 0,
        'category' => 0,
        'tags' => 20,
        'tags_per_post_type' => 0,
        'posts' => 25,
        'posts_per_category' => 0
    ];

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
     * Set total dummy nr per app
     *
     * @param string $app
     * @param int $value
     * @return $this
     */
    private function setDefaultOption(string $app, int $value){
        $this->defaultsOptions[$app] = $value;
        return $this;
    }

    /**
     * Get Total Dummy Per App
     * @param string $app
     * @return array|mixed|string
     * @throws \Exception
     */
    public function getDefaultOption(string $app){
        if($this->option($app)){
            return $this->option($app);
        }

        if(!isset($this->defaultsOptions[$app])){
            throw new \Exception('Option '.$app.' is not configured');
        }

        return $this->defaultsOptions[$app];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if($this->validateRequirements()) {
            Cache::flush();

            $this->createUsers()
              ->createPostTypes()
              ->createMedia()
              ->createCategory()
              ->createTags()
              ->createPosts();

            Cache::flush();
        }
    }

    /**
     * Validate requirements
     *
     * @return bool
     */
    private function validateRequirements(){
        // enough options to proceed
        if(!$this->option('all')) {
            $enoughOptions = false;
            foreach ($this->defaultsOptions as $app => $nr) {
                if($this->option($app)){
                    $enoughOptions = true;
                    break;
                }
            }

            if(!$enoughOptions){
                $this->error('No options given! Write --all=1 to create dummy for all default apps, or write --{APP NAME}={NUMBER OF DUMMIES TO CREATE} to proceed!');
                return false;
            }
        }

        // Ensure there is at least one user
        if(!User::count()){
            $this->error('No Users found. Create a user with "make:user {email} {password}" command to proceed!');
            return false;
        }

        // Ensure there is at least one langauge
        if(!Language::count()){
            $this->error('No languages found. Create a langauge manually via Admin interface to proceed!');
            return false;
        }

        return true;
    }

    /**
     * Create users
     *
     * @return $this
     * @throws \Exception
     */
    private function createUsers(){
        if(
            $this->option('users')
            || $this->option('users_per_role')
            || $this->option('all')
        ) {

            $this->comment('Creating dummy users...');
            $output = (new \UserDevSeeder())->run(
              $this->getDefaultOption('users'),
              $this->getDefaultOption('users_per_role'),
              $this->getDefaultOption('role_id')
            );
            $this->info($output);
        }
        return $this;
    }

    /**
     * Create post types
     *
     * @return $this
     */
    private function createPostTypes(){
        if(!$this->option('posts') && ($this->option('post_types') || $this->option('all'))) {
            $this->comment('Creating dummy Post Types...');
            $output = (new \PostTypeDevSeeder())->run($this->getDefaultOption('post_types'));
            $this->info($output);
        }
        return $this;
    }

    /**
     * Craete media
     *
     * @return $this
     */
    private function createMedia(){
        if($this->option('media') || $this->option('all')) {
            $this->comment('Creating dummy media...');
            $output = (new \MediaDevSeeder())->run($this->getDefaultOption('media'));
            $this->info($output);
        }
        return $this;
    }

    /**
     * Create category
     *
     * @return $this
     * @throws \Exception
     */
    private function createCategory(){
        if($this->option('categories') || $this->option('categories_per_post_type') || $this->option('all')) {
            $this->comment('Creating dummy categories...');

            $output = (new \CategoryDevSeeder())->run(
              ($this->getDefaultOption('categories_per_post_type') ? $this->getDefaultOption('categories_per_post_type') : $this->getDefaultOption('categories')),
              $this->getDefaultOption('post_type'),
              ($this->getDefaultOption('categories_per_post_type') ? true : false)
            );

            $this->info($output);
        }
        return $this;
    }

    /**
     * Create tags
     * @return $this
     * @throws \Exception
     */
    private function createTags(){
        if($this->option('tags') || $this->option('tags_per_post_type') || $this->option('all')) {
            $this->comment('Creating dummy tags...');

            $output = (new \TagDevSeeder())->run(
              ($this->getDefaultOption('tags_per_post_type') ? $this->getDefaultOption('tags_per_post_type') : $this->getDefaultOption('tags')),
              $this->getDefaultOption('post_type'),
              ($this->getDefaultOption('tags_per_post_type') ? true : false)
            );

            $this->info($output);
        }

        return $this;
    }

    /**
     * Create posts
     *
     * @return $this
     * @throws \Exception
     */
    private function createPosts(){
        if($this->option('posts') || $this->option('posts_per_category') || $this->option('all')) {
            $this->comment('Creating dummy posts...');
            $output = (new \PostDevSeeder())->run(
                $this->getDefaultOption('posts'),
                $this->getDefaultOption('posts_per_category'),
                $this->getDefaultOption('post_type'),
                $this->getDefaultOption('media'),
                $this->getDefaultOption('tags'),
                $this->getDefaultOption('category'),
                ($this->getDefaultOption('post_types') == 'all' ? true : false)

            );
            $this->info($output);
        }
        return $this;
    }
}
