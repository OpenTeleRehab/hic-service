<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['title', 'include_feedback'];

    /**
     * @return bool
     */
    public function canDelete()
    {
        // Todo: check if it is not used.
        return $this->include_feedback;
    }
}
