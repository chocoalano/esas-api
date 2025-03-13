<?php

namespace App\Http\Resources\Permit;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ApprovalResource extends ResourceCollection
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
            "permit_id" => $this->permit_id,
            "user_id" => $this->user_id,
            "user_type" => $this->user_type,
            "user_approve" => $this->user_approve,
            "notes" => $this->notes,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
