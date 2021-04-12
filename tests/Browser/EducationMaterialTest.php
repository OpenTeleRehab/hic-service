<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * @group EducationMaterialTest
 *
 * @return void
 */
class EducationMaterialTest extends DuskTestCase
{

    /**
     * @group CreateEducationMaterialTest
     *
     * @return void
     */
    public function testCreateEducationMaterial()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/service-setup#education')
                ->waitFortext('Services Setup')
                ->pause(10000)
                ->clickLink('New Content')
                ->waitForText('Add new Education Material')
                ->type('title', 'Education Material')
                ->attach('file', 'storage/app/test/exercise.jpeg')
                ->press('Save')
                ->waitForText('Education material created successfully')
                ->waitForText('Education Material');
            $this->logout($browser);
        });
    }

    /**
     * @group EditEducationMaterialTest
     *
     * @return void
     */
    public function testEditEducationMaterial()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/service-setup#education')
                ->waitFortext('Services Setup')
                ->pause(1000)
                ->clickLink('New Content')
                ->type('title', 'Education Material')
                ->attach('file', 'storage/app/test/exercise.jpeg')
                ->press('Save')
                ->waitForText('Education material created successfully')
                ->waitForText('Education Material')
                ->pause(1000)
                ->press('svg[viewBox="0 0 24 24"]')
                ->waitForText('Edit Education Material')
                ->type('title', 'Education Materail Test')
                ->press('Save')
                ->waitForText('Education material updated successfully')
                ->waitForText('Education Materail Test');
            $this->logout($browser);
        });
    }

    /**
     * @group DeleteEducationMaterialTest
     *
     * @return void
     */
    public function testDeleteEducationMaterial()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/service-setup#education')
                ->waitFortext('Services Setup')
                ->clickLink('New Content')
                ->type('title', 'Education Material')
                ->attach('file', 'storage/app/test/exercise.jpeg')
                ->press('Save')
                ->waitForText('Education material created successfully')
                ->waitForText('Education Material')
                ->pause(1000)
                ->press('svg[viewBox="0 0 448 512"]')
                ->press('Yes')
                ->waitForText('Education material deleted successfully')
                ->assertMissing('dEducation Material');
            $this->logout($browser);
        });
    }

    /**
     * @group CreateEducationMaterialWithAttachVideoTest
     *
     * @return void
     */
    public function testCreateEducationMaterialWithAttachVideo()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/service-setup#education')
                ->waitFortext('Services Setup')
                ->clickLink('New Content')
                ->waitForText('Add new Education Material')
                ->type('title', 'Education Material')
                ->attach('file', 'storage/app/test/video.mp4')
                ->press('Save')
                ->pause(10000)
                ->waitForText('Education Material');
            $this->logout($browser);
        });
    }

    /**
     * @group SearchEducationMaterialTest
     *
     * @return void
     */
    public function testSearchEducationMaterial()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/service-setup#education')
                ->waitFortext('Services Setup')
                ->clickLink('New Content')
                ->waitForText('Add new Education Material')
                ->type('title', 'Education Material')
                ->attach('file', 'storage/app/test/exercise.jpeg')
                ->press('Save')
                ->waitForText('Education material created successfully')
                ->waitForText('Education Material')
                ->type('search_value', 'Education Material')
                ->pause(1000)
                ->waitForText('Education Material')
                ->pause(1000);
            $this->logout($browser);
        });
    }
}
