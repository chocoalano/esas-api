<?php

namespace App\Http\Resources\Attendance;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class AttendanceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            "id" => $this->id,
            "user_id" => $this->user_id,
            "company_id" => $this->company_id,
            "name" => $this->name,
            "nip" => $this->nip,
            "avatar" => $this->avatar,
            "departement_id" => $this->departement_id,
            "job_position_id" => $this->job_position_id,
            "job_level_id" => $this->job_level_id,
            "approval_line_id" => $this->approval_line_id,
            "approval_manager_id" => $this->approval_manager_id,
            "join_date" => $this->formatDate($this->join_date),
            "sign_date" => $this->formatDate($this->sign_date),
            "departement" => $this->departement,
            "position" => $this->position,
            "level" => $this->level,
            "work_day" => $this->work_day,
            "shiftname" => $this->shiftname,
            "in" => $this->in,
            "out" => $this->out,
            "user_timework_schedule_id" => $this->user_timework_schedule_id,
            "time_in" => $this->time_in,
            "lat_in" => $this->lat_in,
            "long_in" => $this->long_in,
            "image_in" => $this->image_in,
            "status_in" => $this->status_in,
            "time_out" => $this->time_out,
            "lat_out" => $this->lat_out,
            "long_out" => $this->long_out,
            "image_out" => $this->image_out,
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
