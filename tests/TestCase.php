<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->initDefaultData();
    }

    /**
     * @return void
     */
    private function initDefaultData(): void
    {
        $this->artisan('migrate:fresh');
        $this->artisan('db:seed', ['--env' => 'testing']);
    }

    /**
     * @return User
     */
    public function getGlobalAdmin(): User
    {
        return User::where('email', 'global-admin@we.co')->first();
    }

    /**
     * @param string|null $email
     *
     * @return User
     */
    public function getCountryAdmin(string $email = null): User
    {
        return User::where('email', 'country-admin@we.co')->first();
    }
}
