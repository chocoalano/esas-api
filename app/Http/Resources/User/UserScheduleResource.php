<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class UserScheduleResource extends JsonResource
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
            "time_work_id" => $this->time_work_id,
            "work_day" => $this->formatDate($this->work_day),
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "timework" => new UserTimeworkResource($this->timework)
        ];
    }
    private function formatDate($date)
    {
        return $date ? Carbon::parse($date)->timezone(config('app.timezone'))->format('Y-m-d') : null;
    }
}
