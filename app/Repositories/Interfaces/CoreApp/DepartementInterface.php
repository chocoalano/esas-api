<?php
namespace App\Repositories\Interfaces\CoreApp;

interface DepartementInterface
{
    public function countAll(): int;
    public function existsByName(int $deptId, string $deptName);
    public function companyall(string $search);
    public function all(int $companyId, string $search);
    public function shift(int $companyId, int $deptId);
}
