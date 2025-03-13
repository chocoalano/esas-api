<?php
namespace App\Repositories\Interfaces\AdministrationApp;

interface AnnouncementInterface
{
    public function paginate(int $page, int $limit, string $search);
    public function find(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id): bool;
}
