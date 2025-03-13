<?php
namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserFormalEducationResource extends JsonResource
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
            'majors' => $this->majors,
            'score' => $this->score,
            'start' => $this->start,
            'finish' => $this->finish,
            'status' => $this->status,
            'certification' => $this->certification,
        ];
    }
}
