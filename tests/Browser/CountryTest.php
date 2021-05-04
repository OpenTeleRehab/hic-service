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
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/setting')
                ->assertPathIs('/setting')
                ->pause(1000)
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
                ->pause(1000)
                ->select('country_code', 'VN')
                ->press('Create')
                ->waitForText('Country created successfully')
                ->waitForText('Viet Nam');
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
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/setting')
                ->waitForText('New Country')
                ->press('New Country')
                ->pause(1000)
                ->select('country_code', 'VN')
                ->press('Create')
                ->waitForText('Country created successfully')
                ->waitForText('Viet Nam')
                ->press('svg[viewBox="0 0 24 24"]')
                ->pause(1000)
                ->select('country_code', 'FR')
                ->press('Save')
                ->waitForText('Country updated successfully')
                ->waitForText('France');
            $this->logout($browser);
        });
    }
}
