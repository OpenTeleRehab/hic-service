<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Log;
use Laravel\Dusk\Browser;
use Laravel\Dusk\TestCase as BaseTestCase;
use Tests\Browser\Pages\HomePage;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseMigrations;

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
        return $browser->visit('http://localhost:8080/auth/realms/hi/protocol/openid-connect/auth?client_id=hi_frontend&redirect_uri=http%3A%2F%2Flocalhost%3A3001%2F&state=6d7e4e94-6248-415c-8cc5-6f9c71890bfb&response_mode=fragment&response_type=code&scope=openid&nonce=4deeaf1b-4930-4832-9ace-79b079eb4af5')
            ->type('username', 'adminuser@gmail.com')
            ->type('password', 'adminuser@gmail.com')
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
        return $browser->press('.btn-link')
            ->clickLink('Logout')
            ->press('Yes')
            ->waitForText('Login');
    }
}
