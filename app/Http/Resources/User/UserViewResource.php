<?php
namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class UserViewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'company_id' => $this->company_id,
            'name' => $this->name,
            'nip' => $this->nip,
            'email' => $this->email,
            'email_verified_at' => $this->formatDate($this->email_verified_at),
            'password' => $this->password,
            'avatar' => $this->avatar,
            'status' => $this->status,
            'remember_token' => $this->remember_token,
            'company' => $this->company,
            'company_lat' => $this->company_lat,
            'company_long' => $this->company_long,
            'company_radius' => $this->company_radius,
            'company_address' => $this->company_address,
            'basic_salary' => $this->basic_salary,
            'payment_type' => $this->payment_type,
            'bank_name' => $this->bank_name,
            'bank_number' => $this->bank_number,
            'bank_holder' => $this->bank_holder,
            'phone' => $this->phone,
            'placebirth' => $this->placebirth,
            'datebirth' => $this->formatDate($this->datebirth),
            'gender' => $this->gender,
            'blood' => $this->blood,
            'marital_status' => $this->marital_status,
            'religion' => $this->religion,
            'identity_type' => $this->identity_type,
            'identity_numbers' => $this->identity_numbers,
            'province' => $this->province,
            'city' => $this->city,
            'citizen_address' => $this->citizen_address,
            'residential_address' => $this->residential_address,
            'departement_id' => $this->departement_id,
            'job_position_id' => $this->job_position_id,
            'job_level_id' => $this->job_level_id,
            'approval_line_id' => $this->approval_line_id,
            'approval_manager_id' => $this->approval_manager_id,
            'join_date' => $this->formatDate($this->join_date),
            'sign_date' => $this->formatDate($this->sign_date),
            'resign_date' => $this->formatDate($this->resign_date),
            'departement' => $this->departement,
            'position' => $this->position,
        ];
    }

    /**
     * Format tanggal untuk memastikan tidak ada perbedaan zona waktu.
     */
    private function formatDate($date)
    {
        return $date ? Carbon::parse($date)->timezone(config('app.timezone'))->format('Y-m-d') : null;
    }
}
