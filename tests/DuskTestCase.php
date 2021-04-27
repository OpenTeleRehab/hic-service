<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Facades\Log;
use Laravel\Dusk\Browser;
use Laravel\Dusk\TestCase as BaseTestCase;
use Tests\Browser\Pages\HomePage;

abstract class DuskTestCase extends BaseTestCase
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
        $this->artisan('hi:import-default-translation');
        $this->artisan('db:seed');
    }

    /**
     * Prepare for Dusk test execution.
     *
     * @beforeClass
     * @return void
     */
    public static function prepare()
    {
        if (! static::runningInSail()) {
            static::startChromeDriver();
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    protected function driver()
    {
        $options = (new ChromeOptions)->addArguments(collect(['--window-size=1920,1080',])->unless($this->hasHeadlessDisabled(), function ($items) {
            return $items->merge([
                '--disable-gpu',
                '--headless',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(ChromeOptions::CAPABILITY, $options)
        );
    }

    /**
     * Determine whether the Dusk command has disabled headless mode.
     *
     * @return bool
     */
    protected function hasHeadlessDisabled()
    {
        return isset($_SERVER['DUSK_HEADLESS_DISABLED']) ||
               isset($_ENV['DUSK_HEADLESS_DISABLED']);
    }

    /**
     * @param Browser $browser
     *
     * @return Browser
     */
    public function loginAsGlobal(Browser $browser): Browser
    {
        return $browser->visit('https://test-admin-rehabilitation.wehost.asia/auth/realms/hi/protocol/openid-connect/auth?client_id=hi_frontend&redirect_uri=https%3A%2F%2Ftest-admin-rehabilitation.wehost.asia%2F&state=48bc4481-0625-4c03-b767-3a8adb730e0e&response_mode=fragment&response_type=code&scope=openid&nonce=8b375d71-807b-4292-887f-9a2ed5d8bf2b')
            ->type('username', 'global-admin@we.co')
            ->type('password', 'global-admin@we.co')
            ->press('Login')
            ->on(new HomePage);
    }

    /**
     * @param Browser $browser
     *
     * @return Browser
     */
    public function loginAsCountryAdmin(Browser $browser): Browser
    {
        return $browser->visit('https://test-admin-rehabilitation.wehost.asia/auth/realms/hi/protocol/openid-connect/auth?client_id=hi_frontend&redirect_uri=https%3A%2F%2Ftest-admin-rehabilitation.wehost.asia%2F&state=48bc4481-0625-4c03-b767-3a8adb730e0e&response_mode=fragment&response_type=code&scope=openid&nonce=8b375d71-807b-4292-887f-9a2ed5d8bf2b')
            ->type('username', 'country-admin@we.co')
            ->type('password', 'country-admin@we.co')
            ->press('Login')
            ->on(new HomePage);
    }

    /**
     * @param Browser $browser
     *
     * @return Browser
     */
    public function logout(Browser $browser): Browser
    {
        return $browser->pause(10000)
            ->press('.btn-link')
            ->clickLink('Logout')
            ->press('Yes')
            ->waitForText('Login');
    }
}
