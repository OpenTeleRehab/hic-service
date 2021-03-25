<?php

namespace Tests\Browser;

use App\Models\Country;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Illuminate\Support\Facades\Auth;

/**
 * @group CountryTest
 *
 * @return void
 */
class CountryTest extends DuskTestCase
{

    /**
     * @group ListCountryTest
     *
     * @return void
     */
    public function testListCountry()
    {
        Country::factory()->create();
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/setting')
                ->assertPathIs('/setting')
                ->waitForText('Cambodia');
            $this->logout($browser);
        });
    }

    /**
     * @group CreateCountryTest
     *
     * @return void
     */
    public function testCreateCountry()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/setting')
                ->waitForText('New Country')
                ->press('New Country')
                ->type('name', 'America')
                ->type('iso_code', 'ac')
                ->type('phone_code', '96')
                ->press('Create')
                ->waitForText('Country created successfully')
                ->waitForText('America');
            $this->logout($browser);
        });
    }

    /**
     * @group EditCountryTest
     *
     * @return void
     */
    public function testEditCountry()
    {
        Country::factory()->create();
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/setting')
                ->waitForText('Cambodia')
                ->press('svg[viewBox="0 0 24 24"]')
                ->type('name', 'China')
                ->type('iso_code', 'CN')
                ->type('phone_code', '86')
                ->select('language', 1)
                ->press('Save')
                ->waitForText('Country updated successfully')
                ->waitForText('China');
            $this->logout($browser);
        });
    }
}
