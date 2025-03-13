<?php

namespace App\Repositories\Services\AdministrationApp;

use App\Models\AdministrationApp\Announcement;
use App\Repositories\Interfaces\AdministrationApp\AnnouncementInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AnnouncementService implements AnnouncementInterface
{
    protected $model;

    public function __construct(Announcement $model)
    {
        $this->model = $model;
    }

    /**
     * @inheritDoc
     */
    public function create(array $data)
    {
        $announcement = $this->model->create($data);
        // Clear cache when a new announcement is created
        Cache::forget('announcements_paginated');
        return $announcement;
    }

    /**
     * @inheritDoc
     */
    public function delete(int $id): bool
    {
        $find = $this->model->find($id);
        if ($find) {
            $find->delete();
            // Clear cache when an announcement is deleted
            Cache::forget('announcement_' . $id);
            Cache::forget('announcements_paginated');
        }
        return $find ? true : false;
    }

    /**
     * @inheritDoc
     */
    public function find(int $id)
    {
        // Cache individual announcement data for faster retrieval
        return Cache::remember('announcement_' . $id, 3600, function () use ($id) {
            return $this->model->find($id);
        });
    }

    /**
     * @inheritDoc
     */
    public function paginate(int $page, int $limit, ?string $search = null)
    {
        $auth_user = Auth::user();
        $cacheKey = 'announcements_paginated_' . $auth_user->company_id . "_page_{$page}_limit_{$limit}_search_" . md5($search);

        return Cache::remember($cacheKey, 3600, function () use ($auth_user, $page, $limit, $search) {
            $query = $this->model->query();
            $query->where([
                'company_id' => $auth_user->company_id,
                'status' => true,
            ]);

            if (!empty($search)) {
                $query->where('title', 'like', '%' . $search . '%');
            }

            return $query->orderByDesc('created_at')
                ->paginate($limit, ['*'], 'page', $page);
        });
    }

    /**
     * @inheritDoc
     */
    public function update(int $id, array $data)
    {
        $find = $this->model->findOrFail($id);
        if ($find) {
            $find->update($data);
            // Update the cache for the updated announcement
            Cache::put('announcement_' . $id, $find, 3600);
            Cache::forget('announcements_paginated');
        }
        return $find;
    }
}
