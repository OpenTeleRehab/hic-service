<?php

namespace App\Http\Controllers;

use App\Http\Resources\ContributorResource;
use App\Models\Contributor;

class ContributorController extends Controller
{
    /**
     * @return array
     */
    public function index()
    {
        $contributors = Contributor::all();

        return ['success' => true, 'data' => ContributorResource::collection($contributors)];
    }
}
