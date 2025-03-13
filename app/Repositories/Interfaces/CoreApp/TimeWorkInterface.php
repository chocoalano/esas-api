<?php
namespace App\Repositories\Interfaces\CoreApp;

use Illuminate\Support\Collection;

interface TimeWorkInterface
{
    public function paginate(int $page, int $limit, string $search);
    public function all();
    public function find(int $id);
    public function findbyName(string $name, int $dept_id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function import(array $data);
    public function delete(int $id): bool;
}
