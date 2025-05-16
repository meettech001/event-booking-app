<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    protected string|null $token = null;

    /**
     * Attach a token to the resource.
     */
    public function withToken(string $token): static
    {
        $this->token = $token;
        return $this;
    }

    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email,
        ];
    }

    /**
     * Customize the response format.
     */
    public function toResponse($request)
    {
        return response()->json([
            'message' => 'Login successful',
            'token'   => $this->token,
            'user'    => $this->toArray($request),
        ]);
    }
}
