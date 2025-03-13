<?php

namespace App\Providers;
use App\Repositories\Interfaces\AdministrationApp\AnnouncementInterface;
use App\Repositories\Interfaces\AdministrationApp\AttendanceInterface;
use App\Repositories\Interfaces\AdministrationApp\PermitInterface;
use App\Repositories\Interfaces\AdministrationApp\ScheduleAttendanceInterface;
use App\Repositories\Interfaces\CoreApp\DepartementInterface;
use App\Repositories\Interfaces\CoreApp\TimeWorkInterface;
use App\Repositories\Interfaces\CoreApp\UserInterface;
use App\Repositories\Interfaces\Tools\BugReportInterface;
use App\Repositories\Services\AdministrationApp\AnnouncementService;
use App\Repositories\Services\AdministrationApp\AttendanceService;
use App\Repositories\Services\AdministrationApp\PermitService;
use App\Repositories\Services\AdministrationApp\ScheduleAttendanceService;
use App\Repositories\Services\CoreApp\DepartementService;
use App\Repositories\Services\CoreApp\TimeWorkService;
use App\Repositories\Services\CoreApp\UserService;
use App\Repositories\Services\Tools\BugReportService;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(UserInterface::class, UserService::class);
        $this->app->bind(DepartementInterface::class, DepartementService::class);
        $this->app->bind(TimeWorkInterface::class, TimeWorkService::class);
        $this->app->bind(AttendanceInterface::class, AttendanceService::class);
        $this->app->bind(ScheduleAttendanceInterface::class, ScheduleAttendanceService::class);
        $this->app->bind(PermitInterface::class, PermitService::class);
        $this->app->bind(AnnouncementInterface::class, AnnouncementService::class);
        $this->app->bind(BugReportInterface::class, BugReportService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
