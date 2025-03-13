<?php
namespace App\Repositories\Interfaces\CoreApp;

use Illuminate\Support\Collection;

interface UserInterface
{
    public function paginate(int $page, int $limit, string $search);
    public function all();
    public function find(int $id);
    public function findbyNip(string $nip);
    public function findUserHr();
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id): bool;
    public function countAll(): int;
    public function login(array $data);
    public function profile();
    public function update_password(int $userId, array $data);
    public function auth_update_family(array $data);
    public function auth_update_formal_education(array $data);
    public function auth_update_informal_education(array $data);
    public function auth_update_work_experience(array $data);
    public function auth_update_bank(array $data);
    public function schedule(int $userId);
    public function profile_schedule_list(int $userId);
}
