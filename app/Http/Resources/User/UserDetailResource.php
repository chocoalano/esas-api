<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'phone' => $this->phone,
            'placebirth' => $this->placebirth,
            'datebirth' => $this->datebirth,
            'gender' => $this->gender,
            'blood' => $this->blood,
            'marital_status' => $this->marital_status,
            'religion' => $this->religion,
        ];
    }
}
