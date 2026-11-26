<?php

namespace Tests\Browser;

use PHPUnit\Framework\Attributes\Group;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * @returns void
 */
#[Group('QuestionnaireTest')]
class QuestionnaireTest extends DuskTestCase
{

    /**
     * @return void
     */
    #[Group('CreateQuestionnaireWithQuestionTypeCheckboxTest')]
    public function testCreateQuestionnaireWithQuestionTypeCheckbox()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/service-setup#questionnaire')
                ->waitForText('Services Setup')
                ->clickLink('New Content')
                ->pause(1000)
                ->waitForText('Add new Questionnaire')
                ->type('title', 'Questionnaire Test')
                ->type('description', 'Description test')
                ->type('#formTitle0', 'question 1')
                ->select('type', 'checkbox')
                ->type('#formValue0', 'Answer 1')
                ->type('#formValue1', 'Answer 2')
                ->pause(1000)
                ->press('Add more answer')
                ->type('#formValue2', 'Answer3')
                ->press('Save')
                ->waitForText('Questionnaire created successfully');
            $this->logout($browser);
        });
    }

    /**
     * @return void
     */
    #[Group('CreateQuestionnaireWithQuestionTypeMultipleChoiceTest')]
    public function testCreateQuestionnaireWithQuestionTypeMultipleChoice()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/service-setup#questionnaire')
                ->waitForText('Services Setup')
                ->clickLink('New Content')
                ->pause(1000)
                ->waitForText('Add new Questionnaire')
                ->type('title', 'Questionnaire Test')
                ->type('description', 'Description test')
                ->select('#formType0', 'multiple')
                ->type('#formTitle0', 'question 1?')
                ->type('#formValue0', 'Answer 1')
                ->type('#formValue1', 'Answer 2')
                ->pause(1000)
                ->press('Add more answer')
                ->type('#formValue2', 'Answer3')
                ->press('Save')
                ->waitForText('Questionnaire created successfully')
                ->waitForText('Questionnaire Test');
            $this->logout($browser);
        });
    }

    /**
     * @return void
     */
    #[Group('CreateQuestionnaireWithQuestionTypeOpenTextTest')]
    public function testCreateQuestionnaireWithQuestionTypeOpenText()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/service-setup#questionnaire')
                ->waitForText('Services Setup')
                ->clickLink('New Content')
                ->pause(1000)
                ->waitForText('Add new Questionnaire')
                ->type('title', 'Questionnaire Test')
                ->type('description', 'Description test')
                ->pause(1000)
                ->type('#formTitle0', 'question 1?')
                ->select('#formType0', 'open-text')
                ->press('Save')
                ->waitForText('Questionnaire created successfully')
                ->waitForText('Questionnaire Test');
            $this->logout($browser);
        });
    }

    /**
     * @return void
     */
    #[Group('CreateQuestionnaireWithQuestionTypeOpenNumberTest')]
    public function testCreateQuestionnaireWithQuestionTypeOpenNumber()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/service-setup#questionnaire')
                ->waitForText('Services Setup')
                ->pause(1000)
                ->clickLink('New Content')
                ->waitForText('Add new Questionnaire')
                ->type('title', 'Questionnaire Test')
                ->type('description', 'Description test')
                ->pause(1000)
                ->type('#formTitle0', 'question 1?')
                ->select('#formType0', 'open-number')
                ->press('Save')
                ->waitForText('Questionnaire created successfully')
                ->waitForText('Questionnaire Test');
            $this->logout($browser);
        });
    }

    /**
     * @return void
     */
    #[Group('CreateQuestionnaireWithMultipleQuestionTest')]
    public function testCreateQuestionnaireWithMultipleQuestion()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/service-setup#questionnaire')
                ->waitForText('Services Setup')
                ->pause(1000)
                ->clickLink('New Content')
                ->waitForText('Add new Questionnaire')
                ->type('title', 'Questionnaire Test')
                ->type('description', 'Description test')
                ->type('#formTitle0', 'question 1?')
                ->select('type', 'checkbox')
                ->type('#formValue0', 'Answer 1')
                ->type('#formValue1', 'Answer 2')
                ->pause(1000)
                ->press('Add more answer')
                ->type('#formValue2', 'Answer3')
                ->press('New Question')
                ->select('#formType1', 'open-text')
                ->type('#formTitle1', 'question 2?')
                ->pause(1000)
                ->press('New Question')
                ->select('#formType2', 'open-number')
                ->type('#formTitle2', 'question 3?')
                ->press('Save')
                ->waitForText('Questionnaire created successfully');
            $this->logout($browser);
        });
    }

    /**
     * @return void
     */
    #[Group('EditQuestionnaireTest')]
    public function testEditQuestionnaire()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/service-setup#questionnaire')
                ->waitForText('Services Setup')
                ->pause(1000)
                ->clickLink('New Content')
                ->waitForText('Add new Questionnaire')
                ->type('title', 'Questionnaire Test')
                ->type('description', 'Description test')
                ->pause(1000)
                ->select('#formType0', 'multiple')
                ->type('#formTitle0', 'question 1?')
                ->type('#formValue0', 'Answer 1')
                ->type('#formValue1', 'Answer 2')
                ->pause(1000)
                ->press('Add more answer')
                ->type('#formValue2', 'Answer3')
                ->press('Save')
                ->waitForText('Questionnaire created successfully')
                ->waitForText('Questionnaire Test')
                ->press('svg[viewBox="0 0 24 24"]')
                ->pause(1000)
                ->assertSee('Edit Questionnaire')
                ->type('title', 'Questionnaire Edited Test')
                ->press('Save')
                ->waitForText('Questionnaire updated successfully')
                ->pause(1000)
                ->waitForText('Questionnaire Edited Test');
            $this->logout($browser);
        });
    }

    /**
     * @return void
     */
    #[Group('DeleteQuestionnaireTest')]
    public function testDeleteQuestionnaire()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/service-setup#questionnaire')
                ->waitForText('Services Setup')
                ->pause(1000)
                ->clickLink('New Content')
                ->waitForText('Add new Questionnaire')
                ->type('title', 'Questionnaire Test')
                ->type('description', 'Description test')
                ->select('#formType0', 'multiple')
                ->type('#formTitle0', 'question 1?')
                ->type('#formValue0', 'Answer 1')
                ->type('#formValue1', 'Answer 2')
                ->pause(1000)
                ->press('Add more answer')
                ->type('#formValue2', 'Answer3')
                ->press('Save')
                ->waitForText('Questionnaire created successfully')
                ->waitForText('Questionnaire Test')
                ->pause(1000)
                ->press('svg[viewBox="0 0 448 512"]')
                ->press('Yes')
                ->waitForText('Questionnaire deleted successfully');
            $this->logout($browser);
        });
    }
}
