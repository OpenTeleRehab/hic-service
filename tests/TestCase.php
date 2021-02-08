<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Mock\MockData;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @return void
     */
    protected function initDefaultData(): void
    {
        $this->artisan('migrate:fresh');
        $this->artisan('db:seed', ['--env' => 'testing']);
    }

    /**
     * @param string|null $email
     *
     * @return User
     */
    public function getGlobalAdmin(string $email = null): User
    {
        $email = empty($email) ? MockData::$users['global_admin'] : $email;
        return User::where('email', '=', $email)->first();
    }

    /**
     * @param string|null $email
     *
     * @return User
     */
    public function getCountryAdmin(string $email = null): User
    {
        $email = empty($email) ? MockData::$users['country_admin'] : $email;
        return User::where('email', '=', $email)->first();
    }
}
