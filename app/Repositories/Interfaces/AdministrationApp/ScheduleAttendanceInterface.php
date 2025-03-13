<?php
namespace App\Repositories\Interfaces\AdministrationApp;

interface ScheduleAttendanceInterface
{
    public function template(int $company, int $departement);
    public function import(array $data);
    public function time_validation(int $scheduleId, int $userId, string $timeInOrOut, string $currenttime);
    public function find(int $id);
    public function update(int $id, array $data);
}
