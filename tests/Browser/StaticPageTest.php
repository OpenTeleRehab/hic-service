<?php

namespace Tests\Browser;

use PHPUnit\Framework\Attributes\Group;
use App\Models\Clinic;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * @returns void
 */
#[Group('StaticPageTest')]
class StaticPageTest extends DuskTestCase
{

    /**
     * @return void
     */
    #[Group('CreateStaticPageForAdminPortalTest')]
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
