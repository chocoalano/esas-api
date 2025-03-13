<?php

namespace App\Http\Resources\Permit;

use Illuminate\Http\Resources\Json\JsonResource;


class UserTimeworkScheduleResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'time_work_id' => $this->time_work_id,
            'work_day' => $this->work_day,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
