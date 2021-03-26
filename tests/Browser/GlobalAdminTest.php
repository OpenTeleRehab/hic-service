<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Illuminate\Support\Facades\Auth;

/**
 * @group GlobalAdminTest
 *
 * @return void
 */
class GlobalAdminTest extends DuskTestCase
{

    /**
     * @group CreateGlobalAdminWithValidationTest
     *
     * @return void
     */
    public function testCreateGlobalAdminWithValidation()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/admin')
                ->waitForText('Admin Management')
                ->press('New Admin')
                ->waitForText('New Admin')
                ->press('Create')
                ->waitForText('Please fill in the email')
                ->waitForText('Please fill last name')
                ->waitForText('Please fill first name')
                ->press('Cancel');
            $this->logout($browser);
        });
    }

    /**
     * @group CreateGlobalAdminWithValidCredentialTest
     *
     * @return void
     */
    public function testCreateAndDeleteGlobalAdminWithValideCredential()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/admin')
                ->waitForText('Admin Management')
                ->press('New Admin')
                ->waitForText('New Admin')
                ->type('email', 'global-admin@test.com')
                ->type('last_name', 'Global')
                ->type('first_name', 'Testing')
                ->press('Create')
                ->waitForText('User created successfully')
                ->type('search', 'global-admin@test.com')
                ->waitForText('global-admin@test.com')
                ->press('svg[viewBox="0 0 448 512"]')
                ->press('Yes')
                ->waitForText('User deleted successfully');
            $this->logout($browser);
        });
    }

    /**
     * @group DeactivateGlobalAdminTest
     *
     * @return void
     */
    public function testDeactivateGlobalAdmin()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/admin')
                ->waitForText('Admin Management')
                ->press('New Admin')
                ->waitForText('New Admin')
                ->type('email', 'global-admin@test.com')
                ->type('last_name', 'Global')
                ->type('first_name', 'Testing')
                ->press('Create')
                ->waitForText('User created successfully')
                ->assertSee('global-admin@test.com')
                ->type('search', 'global-admin@test.com')
                ->waitForText('global-admin@test.com')
                ->press('svg[viewBox="0 0 576 512"]')
                ->press('Yes')
                ->waitForText('User updated successfully')
                ->press('svg[viewBox="0 0 576 512"]')
                ->press('Yes')
                ->press('svg[viewBox="0 0 448 512"]')
                ->press('Yes')
                ->waitForText('User deleted successfully');
            $this->logout($browser);
        });
    }
}
