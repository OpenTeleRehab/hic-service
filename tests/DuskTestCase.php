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
        return $browser->visit('https://test-rehabilitation.wehost.asia/auth/realms/hi/protocol/openid-connect/auth?client_id=hi_frontend&redirect_uri=https%3A%2F%2Ftest-admin-rehabilitation.wehost.asia%2F&state=8a675374-cbda-4c43-a835-b527cc6ca0a8&response_mode=fragment&response_type=code&scope=openid&nonce=f23d462a-8b7a-45aa-8347-88feeb861dcf')
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
        return $browser->visit('https://test-rehabilitation.wehost.asia/auth/realms/hi/protocol/openid-connect/auth?client_id=hi_frontend&redirect_uri=https%3A%2F%2Ftest-admin-rehabilitation.wehost.asia%2F&state=8a675374-cbda-4c43-a835-b527cc6ca0a8&response_mode=fragment&response_type=code&scope=openid&nonce=f23d462a-8b7a-45aa-8347-88feeb861dcf')
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
