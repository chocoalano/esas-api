<?php
namespace App\Repositories\Interfaces\AdministrationApp;

interface PermitInterface
{
    public function generate_unique_numbers(int $permit_type_id);
    public function countAll();
    public function chart(string $filter);
    public function type();
    public function all();
    public function paginate(int $page, int $limit, string $search, int $type);
    public function find(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id): bool;
    public function approved(int $permitId, int $authId, string $approve, string $notes): bool;
}
