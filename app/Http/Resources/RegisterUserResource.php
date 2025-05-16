<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegisterUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }

    /**
     * Customize the outgoing response for the resource.
     */
    public function toResponse($request)
    {
        return response()->json([
            'message' => "User registered successfully",
            'user'    => $this->toArray($request),
        ], 201);
    }
}
