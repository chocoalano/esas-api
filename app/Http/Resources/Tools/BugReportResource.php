<?php
namespace App\Http\Resources\Tools;

use Illuminate\Http\Resources\Json\JsonResource;

class BugReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'status' => $this->status,
            'message' => $this->message,
            'platform' => $this->platform,
            'image' => $this->image,
        ];
    }
}
