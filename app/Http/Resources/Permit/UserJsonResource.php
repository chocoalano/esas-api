<?php

namespace App\Http\Resources\Permit;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserJsonResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "company_id" => $this->company_id,
            "name" => $this->name,
            "nip" => $this->nip,
            "email" => $this->email,
            "email_verified_at" => $this->email_verified_at,
            "avatar" => $this->avatar,
            "status" => $this->status,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
