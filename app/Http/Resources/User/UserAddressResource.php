<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "user_id" => $this->user_id,
            "identity_type" => $this->identity_type,
            "identity_numbers" => $this->identity_numbers,
            "province" => $this->province,
            "city" => $this->city,
            "citizen_address" => $this->citizen_address,
            "residential_address" => $this->residential_address,
        ];
    }
}
