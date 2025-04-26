<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendee extends Model
{
    protected $table = "attendee";
    protected $fillable = [
        'name',
        'email',
    ];
}
