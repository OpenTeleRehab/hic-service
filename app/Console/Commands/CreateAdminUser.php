<?php

namespace App\Console\Commands;

use App\Helpers\KeycloakHelper;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hi:create-admin-user {email} {first_name} {last_name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Admin User';

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
     * @return boolean
     */
    public function handle()
    {
        DB::beginTransaction();
        $email = $this->argument('email');
        $firstName = $this->argument('first_name');
        $lastName = $this->argument('last_name');
        $availableEmail = User::where('email', $email)->count();
        $type = User::GROUP_ADMIN;
        if ($availableEmail) {
            $this->error('Email is already exist');
            return false;
        }

        $user = User::create([
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'type' => $type
        ]);

        if (!$user) {
            $this->error('User is not able to create');
            return false;
        }

        try {
            KeycloakHelper::createUser($user, $email, false, User::GROUP_ADMIN);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('User is not able to create on Keycloak');
            return false;
        }
        DB::commit();

        $this->info('User has been created');
        return true;
    }
}
