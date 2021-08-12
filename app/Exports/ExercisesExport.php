<?php

namespace App\Exports;

use App\Exercise;
use App\Helpers\ExerciseHelper;
use App\Models\Exercise;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromView;

class ExercisesExport implements FromView
{
    /**
     * @var \Illuminate\Http\Request
     */
    private $request;

    /**
     * ExercisesExport constructor.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @inheritDoc
     *
     * @return View
     */
    public function view(): View
    {
        $query = ExerciseHelper::generateFilterQuery($this->request, with(new Exercise));

        return view('exports.exercises', [
            'exercises' => $query->get(),
        ]);
    }
}
