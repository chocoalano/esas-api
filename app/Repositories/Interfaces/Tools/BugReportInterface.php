<?php
namespace App\Repositories\Interfaces\Tools;

interface BugReportInterface
{
    public function paginate(int $page, int $limit, string $search);
    public function create(array $data);
}
