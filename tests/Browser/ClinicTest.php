<?php

namespace Tests\Browser;

use App\Models\Clinic;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * @group ClinicTest
 * @package Tests\Browser
 */
class ClinicTest extends DuskTestCase
{

    /**
     * @group CreateClinicTest
     *
     * @return void
     */
    public function testCreateClinic()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsCountryAdmin($browser)
                ->visit('/setting#clinic')
                ->waitForText('Settings')
                ->press('New Clinic')
                ->type('name', 'Clinic Test')
                ->type('region', 'Cambodia')
                ->type('city', 'Phnom Penh')
                ->type('province', 'Siem Reap')
                ->pause(1000)
                ->press('Create')
                ->waitForText('Clinic created successfully');
            $this->logout($browser);
        });
    }

    /**
     * @group DeleteClinicTest
     *
     * @return void
     */
    public function testDeleteClinic()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsCountryAdmin($browser)
                ->visit('/setting#clinic')
                ->waitForText('Settings')
                ->pause(1000)
                ->press('svg[viewBox="0 0 448 512"]')
                ->press('Yes')
                ->waitForText('Clinic deleted successfully');
            $this->logout($browser);
        });
    }
}
