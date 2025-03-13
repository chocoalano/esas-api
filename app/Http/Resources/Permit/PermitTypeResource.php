<?php

namespace App\Http\Resources\Permit;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PermitTypeResource extends ResourceCollection
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
            "type" => $this->type,
            "is_payed" => $this->is_payed,
            "approve_line" => $this->approve_line,
            "approve_manager" => $this->approve_manager,
            "approve_hr" => $this->approve_hr,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
