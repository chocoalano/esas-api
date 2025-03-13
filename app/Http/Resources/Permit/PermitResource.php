<?php

namespace App\Http\Resources\Permit;

use Illuminate\Http\Resources\Json\JsonResource;

class PermitResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'permit_numbers' => $this->permit_numbers,
            'user_id' => $this->user_id,
            'permit_type_id' => $this->permit_type_id,
            'user_timework_schedule_id' => $this->user_timework_schedule_id,
            'timein_adjust' => $this->timein_adjust,
            'timeout_adjust' => $this->timeout_adjust,
            'current_shift_id' => $this->current_shift_id,
            'adjust_shift_id' => $this->adjust_shift_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'notes' => $this->notes,
            'file' => $this->file,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => new UserJsonResource($this->user),
            'permit_type' => new PermitTypeJsonResource($this->permitType),
            'approvals' => ApprovalJsonResource::collection($this->approvals),
            'user_timework_schedule' => new UserTimeworkScheduleResource($this->userTimeworkSchedule),
        ];
    }
}
