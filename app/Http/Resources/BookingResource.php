<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'event_id'    => $this->event_id,
            'attendee_id' => $this->attendee_id,
            'created_at'  => $this->created_at->toDateTimeString(),
        ];
    }

    public function toResponse($request)
    {
        return response()->json([
            'message' => 'You\'re booked event successfully',
            'booking' => $this->toArray($request),
        ], 201);
    }
}
