<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Illuminate\Support\Facades\Auth;

/**
 * @group ExerciseTest
 *
 * @return void
 */
class ExerciseTest extends DuskTestCase
{

    /**
     * @group CreateExerciseTest
     *
     * @return void
     */
    public function testCreateExercise()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/service-setup')
                ->waitForText('Services Setup')
                ->clickLink('New Content')
                ->type('title', 'Jogging')
                ->check('get_pain_level')
                ->press('Add more field')
                ->type('field', 'Instruction')
                ->type('value', 'This is the instruction')
                ->attach('file', 'storage/app/asset/play_button.png')
                ->press('Save')
                ->waitForText('Exercise created successfully');
            $this->logout($browser);
        });
    }

    /**
     * @group EditExerciseTest
     *
     * @return void
     */
    public function testEditExercise()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/service-setup')
                ->waitForText('Services Setup')
                ->clickLink('New Content')
                ->type('title', 'Jogging')
                ->check('get_pain_level')
                ->press('Add more field')
                ->type('field', 'Instruction')
                ->type('value', 'This is the instruction')
                ->attach('file', 'storage/app/asset/play_button.png')
                ->press('Save')
                ->waitForText('Exercise created successfully')
                ->waitForText('Jogging')
                ->press('svg[viewBox="0 0 24 24"]')
                ->type('title', 'Jogging testing')
                ->press('Save')
                ->waitForText('Exercise updated successfully')
                ->waitForText('Jogging testing');
            $this->logout($browser);
        });
    }

    /**
     * @group DeleteExerciseTest
     *
     * @return void
     */
    public function testDeleteExercise()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/service-setup')
                ->waitForText('Services Setup')
                ->clickLink('New Content')
                ->type('title', 'Jogging')
                ->check('get_pain_level')
                ->press('Add more field')
                ->type('field', 'Instruction')
                ->type('value', 'This is the instruction')
                ->attach('file', 'storage/app/asset/play_button.png')
                ->press('Save')
                ->waitForText('Exercise created successfully')
                ->waitForText('Jogging')
                ->press('svg[viewBox="0 0 448 512"]')
                ->press('Yes')
                ->waitForText('Exercise deleted successfully')
                ->assertDontSee('Jogging');
            $this->logout($browser);
        });
    }
}
