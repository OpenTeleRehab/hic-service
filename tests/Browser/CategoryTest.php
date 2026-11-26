<?php

namespace Tests\Browser;

use PHPUnit\Framework\Attributes\Group;
use App\Models\Category;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Illuminate\Support\Facades\Auth;

/**
 * @return void
 */
#[Group('CategoryTest')]
class CategoryTest extends DuskTestCase
{

    /**
     * @return void
     */
    #[Group('CreateNewExerciseCategoryTest')]
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
     * @return void
     */
    #[Group('EditExerciseCategoryTest')]
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
     * @return void
     */
    #[Group('CreateNewEducationMaterialCategoryTest')]
    public function testCreateNewEducationMaterialCategory()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/category#education')
                ->waitForText('Categories')
                ->pause(1000)
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
     * @return void
     */
    #[Group('EditEducationMaterialCategoryTest')]
    public function testEditEducationMaterialCategory()
    {
        Category::factory()->create(['type' => 'education']);
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/category#education')
                ->waitForText('Categories')
                ->waitForText('Health condition')
                ->pause(1000)
                ->press('svg[viewBox="0 0 24 24"]')
                ->type('category', 'Traumatic brain injury')
                ->press('Save')
                ->waitForText('Category updated successfully');
            $this->logout($browser);
        });
    }

    /**
     * @return void
     */
    #[Group('CreateNewQuestionnaireCategoryTest')]
    public function testCreateNewQuestionnaireCategory()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/category#questionnaire')
                ->waitForText('Categories')
                ->pause(1000)
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
     * @return void
     */
    #[Group('EditQuestionnaireCategoryTest')]
    public function testEditQuestionnaireCategory()
    {
        Category::factory()->create(['type' => 'questionnaire']);
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/category#questionnaire')
                ->waitForText('Categories')
                ->waitForText('Health condition')
                ->pause(1000)
                ->press('svg[viewBox="0 0 24 24"]')
                ->type('category', 'Traumatic brain injury')
                ->press('Save')
                ->waitForText('Category updated successfully')
                ->waitForText('Traumatic brain injury');
            $this->logout($browser);
        });
    }

    /**
     * @return void
     */
    #[Group('SearchCategoryTest')]
    public function testSearchCategory()
    {
        Category::factory()->create();
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/category')
                ->waitForText('Categories')
                ->waitForText('Health condition')
                ->type('search_value', 'Health condition')
                ->pause(1000)
                ->waitForText('Health condition');
            $this->logout($browser);
        });
    }
}
