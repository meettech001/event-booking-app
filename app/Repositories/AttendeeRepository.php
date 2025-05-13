<?php

namespace App\Repositories;

use App\Models\Attendee;

class AttendeeRepository
{
    public function create(array $data): Attendee
    {
        return Attendee::create($data);
    }
}
