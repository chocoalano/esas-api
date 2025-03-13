<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserTimeworkResource extends JsonResource
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
            "company_id" => $this->company_id,
            "departemen_id" => $this->departemen_id,
            "name" => $this->name,
            "in" => $this->in,
            "out" => $this->out,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
