<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserEmployeeResource extends JsonResource
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
            "departement_id" => $this->departement_id,
            "job_position_id" => $this->job_position_id,
            "job_level_id" => $this->job_level_id,
            "approval_line_id" => $this->approval_line_id,
            "approval_manager_id" => $this->approval_manager_id,
            "join_date" => $this->join_date,
            "sign_date" => $this->sign_date,
            "resign_date" => $this->resign_date,
            "bank_name" => $this->bank_name,
            "bank_number" => $this->bank_number,
            "bank_holder" => $this->bank_holder,
            "saldo_cuti" => $this->saldo_cuti,
        ];
    }
}
