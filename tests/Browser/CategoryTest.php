<?php

namespace Tests\Browser;

use App\Models\Category;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Illuminate\Support\Facades\Auth;

/**
 * @group CategoryTest
 *
 * @return void
 */
class CategoryTest extends DuskTestCase
{

    /**
     * @group CreateNewExerciseCategoryTest
     *
     * @return void
     */
    public function testCreateNewExerciseCategory()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/category')
                ->waitForText('Categories')
                ->click('svg[viewBox="0 0 16 16"]')
                ->waitForText('New Exercise Category Value')
                ->type('category', 'Healthy Condition')
                ->type('category_value', 'Cerebral palsy;Traumatic brain injury')
                ->press('Create')
                ->waitForText('Category created successfully')
                ->waitForText('Healthy Condition (2)');
            $this->logout($browser);
        });
    }
    /**
     * @group EditExerciseCategoryTest
     *
     * @return void
     */
    public function testEditExerciseCategory()
    {
        Category::factory()->create();
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/category')
                ->waitForText('Categories')
                ->pause(1000)
                ->press('svg[viewBox="0 0 24 24"]')
                ->type('category', 'Traumatic brain injury')
                ->press('Save')
                ->waitForText('Category updated successfully');
            $this->logout($browser);
        });
    }

    /**
     * @group CreateNewEducationMaterialCategoryTest
     *
     * @return void
     */
    public function testCreateNewEducationMaterialCategory()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/category#education')
                ->waitForText('Categories')
                ->press('svg[viewBox="0 0 16 16"]')
                ->waitForText('New Education Material Category Value')
                ->type('category', 'Healthy Condition')
                ->type('category_value', 'Cerebral palsy;Traumatic brain injury')
                ->press('Create')
                ->waitForText('Category created successfully')
                ->waitForText('Healthy Condition (2)');
            $this->logout($browser);
        });
    }

    /**
     * @group EditEducationMaterialCategoryTest
     *
     * @return void
     */
    public function testEditEducationMaterialCategory()
    {
        Category::factory()->create(['type' => 'education']);
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/category#education')
                ->waitForText('Categories')
                ->waitForText('Health condition')
                ->press('svg[viewBox="0 0 24 24"]')
                ->type('category', 'Traumatic brain injury')
                ->press('Save')
                ->waitForText('Category updated successfully');
            $this->logout($browser);
        });
    }

    /**
     * @group CreateNewQuestionnaireCategoryTest
     *
     * @return void
     */
    public function testCreateNewQuestionnaireCategory()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/category#questionnaire')
                ->waitForText('Categories')
                ->press('svg[viewBox="0 0 16 16"]')
                ->waitForText('New Questionnaire Category Value')
                ->type('category', 'Healthy Condition')
                ->type('category_value', 'Cerebral palsy;Traumatic brain injury')
                ->press('Create')
                ->waitForText('Category created successfully')
                ->waitForText('Healthy Condition (2)');
            $this->logout($browser);
        });
    }

    /**
     * @group EditQuestionnaireCategoryTest
     *
     * @return void
     */
    public function testEditQuestionnaireCategory()
    {
        Category::factory()->create(['type' => 'questionnaire']);
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/category#questionnaire')
                ->waitForText('Categories')
                ->waitForText('Health condition')
                ->press('svg[viewBox="0 0 24 24"]')
                ->type('category', 'Traumatic brain injury')
                ->press('Save')
                ->waitForText('Category updated successfully')
                ->waitForText('Traumatic brain injury');
            $this->logout($browser);
        });
    }
}
