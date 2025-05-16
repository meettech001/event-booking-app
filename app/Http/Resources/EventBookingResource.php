<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class EventBookingResource extends JsonResource
{
    public function toArray($request): array
    {
        $resource = $this->resource;

        return [
            'id'          => is_array($resource) ? $resource['id'] : $resource->id,
            'event_id'    => is_array($resource) ? $resource['event_id'] : $resource->event_id,
            'attendee_id' => is_array($resource) ? $resource['attendee_id'] : $resource->attendee_id,
            'created_at'  => is_array($resource)
                ? Carbon::parse($resource['created_at'])->toDateTimeString()
                : $resource->created_at->toDateTimeString(),
        ];
    }
}
