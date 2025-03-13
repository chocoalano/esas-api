<?php
namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserWorkExperienceResource extends JsonResource
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
            'company_name' => $this->company_name,
            'position' => $this->position,
            'start' => $this->start,
            'finish' => $this->finish,
            'certification' => $this->certification,
        ];
    }
}
