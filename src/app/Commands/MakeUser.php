<?php

namespace Accio\App\Commands;

use App\Models\Language;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class MakeUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:user {email} {password} {--role_id} {--first_name} {--last_name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a user';

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
        // STEP 1   -   Ensure there there is a language
        if(!Language::count()){
            // Create default language
            factory(Language::class)->create([
                'name' => config('app.locale'),
                'slug' => config('app.locale'),
                'isDefault' => true
            ]);
        }

        // Ensure default roles are already created
        UserGroup::createDefaultRoles();

        // Fill user data
        $data = [
            'email' => $this->argument('email'),
            'password' => Hash::make($this->argument('password')),
            'isActive' => true
        ];

        if($this->option('first_name')){
            $data['firstName'] = $this->option('first_name');
        }

        if($this->option('last_name')){
            $data['lastName'] = $this->option('last_name');
        }

        if($this->option('role_id')){
            $data['groupIDs'] = [ 1 => $this->option('role_id')];
        }else{
            $data['groupIDs'] = [ 1 => UserGroup::getAdminRole()->groupID];
        }

        // Create the user
        factory(User::class)->create($data);

        $this->info("User created successfully!");
    }
}
