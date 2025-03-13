<?php
namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserFamilyResource extends JsonResource
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
            'user_id' => $this->user_id,
            'fullname' => $this->fullname,
            'relationship' => $this->relationship,
            'birthdate' => $this->birthdate,
            'marital_status' => $this->marital_status,
            'job' => $this->job,
        ];
    }
}
