<?php
namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserInformalEducationResource extends JsonResource
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
            'institution' => $this->institution,
            'start' => $this->start,
            'finish' => $this->finish,
            'type' => $this->type,
            'duration' => $this->duration,
            'status' => $this->status,
            'certification' => $this->certification,
        ];
    }
}
