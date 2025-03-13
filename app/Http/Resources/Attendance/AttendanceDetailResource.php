<?php

namespace App\Http\Resources\Attendance;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class AttendanceDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            "id" => $this->id,
            "user_id" => $this->user_id,
            "user_timework_schedule_id" => $this->user_timework_schedule_id,
            "time_in" => $this->time_in,
            "time_out" => $this->time_out,
            "lat_in" => $this->lat_in,
            "lat_out" => $this->lat_out,
            "long_in" => $this->long_in,
            "long_out" => $this->long_out,
            "image_in" => $this->image_in,
            "image_out" => $this->image_out,
            "status_in" => $this->status_in,
            "status_out" => $this->status_out,
            "created_at" => $this->formatDate($this->created_at),
            "updated_at" => $this->formatDate($this->updated_at),
        ];
    }
    private function formatDate($date)
    {
        return $date ? Carbon::parse($date)->timezone(config('app.timezone'))->format('Y-m-d') : null;
    }
}
