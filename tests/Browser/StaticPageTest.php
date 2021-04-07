<?php

namespace Tests\Browser;

use App\Models\Clinic;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * @group StaticPageTest
 * @package Tests\Browser
 */
class StaticPageTest extends DuskTestCase
{

    /**
     * @group CreateStaticPageForAdminPortalTest
     *
     * @return void
     */
    public function testCreateStaticPageForAdminPortal()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/setting#static_page')
                ->waitForText('New Static Page')
                ->press('New Static Page')
                ->select('platform', 'admin_portal')
                ->type('url', 'about-us')
                ->attach('file', 'storage/app/test/children.jpeg')
                ->type('title', 'Welcome to Tele-rehabilitation App')
                ->press('Create');
            $this->logout($browser)
                ->waitForText('Welcome to Tele-rehabilitation App');
        });
    }
}
