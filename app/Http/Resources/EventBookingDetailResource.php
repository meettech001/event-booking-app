<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventBookingDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'user_id'      => $this->user_id,
            'title'        => $this->title,
            'description'  => $this->description,
            'country'      => $this->country,
            'start_time'   => $this->start_time,
            'end_time'     => $this->end_time,
            'capacity'     => $this->capacity,
            'bookings'     => EventBookingResource::collection($this->bookings ?? []),
            'bookingcount' => $this->bookingcount ?? 0,
        ];
    }
}

