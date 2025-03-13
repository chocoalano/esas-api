<?php

namespace App\Http\Resources\Announcement;

use App\Http\Resources\Announcement\AnnouncementDetailResouces;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AnnouncementLIstPaginateResource extends ResourceCollection
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
            'current_page' => $this->currentPage(),
            'data' => AnnouncementDetailResouces::collection($this->collection),
            'first_page_url' => $this->url(1),
            'from' => $this->firstItem(),
            'last_page' => $this->lastPage(),
            'last_page_url' => $this->url($this->lastPage()),
            'links' => $this->links(),
            'next_page_url' => $this->nextPageUrl(),
            'path' => $this->path(),
            'per_page' => $this->perPage(),
            'prev_page_url' => $this->previousPageUrl(),
            'to' => $this->lastItem(),
            'total' => $this->total(),
        ];
    }

    /**
     * Generate paginated links.
     *
     * @return array
     */
    private function links(): array
    {
        return collect($this->linkCollection())
            ->map(function ($link) {
                return [
                    'url' => $link['url'],
                    'label' => $link['label'],
                    'active' => $link['active'],
                ];
            })
            ->toArray();
    }
}
