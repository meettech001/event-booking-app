<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class EventResourceCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return EventResource::collection($this->collection);
    }

    public function toResponse($request)
    {
        $pagination = $this->resource->toArray();

        return response()->json([
            'status' => true,
            'message' => 'Event list fetched successfully',
            'data' => $this->collection,
            'pagination' => [
                'total' => $pagination['total'],
                'per_page' => $pagination['per_page'],
                'current_page' => $pagination['current_page'],
                'last_page' => $pagination['last_page'],
            ],
        ]);
    }
}
