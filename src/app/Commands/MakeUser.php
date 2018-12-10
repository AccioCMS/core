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
    protected $signature = 'make:user';

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

        if(config('app.env') == 'production') {
            return $this->error('Opps! You can\'t create users while in production! ');
        }

        $this->comment("Please answer following questions to create a new user.");

        $firstName = $this->ask('First name');
        $lastName = $this->ask('Last name');
        $email = $this->ask('Email');
        $password = $this->secret('Password');
        $groupID = $this->ask('Role', UserGroup::getEditorGroup()->groupID);
        $activate = $this->confirm('Do you want to activate the user now?', true);

        // Fill user data
        $data = [
            'email' => $email,
            'password' => Hash::make($password),
            'isActive' => $activate,
            'firstName' => $firstName,
            'lastName' => $lastName,
        ];

        // Create the user
        $user = factory(User::class)->create($data);

        // Assign the role
        if($user) {
            $user->assignRoles($groupID);
        }

        $this->info("User created successfully!");
    }
}
