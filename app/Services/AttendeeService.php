<?php

namespace App\Services;

use App\Models\Attendee;
use App\Repositories\AttendeeRepository;

class AttendeeService
{
    public function __construct(protected AttendeeRepository $repo) {}

    public function register(array $data): Attendee
    {
        return $this->repo->create($data);
    }
}
