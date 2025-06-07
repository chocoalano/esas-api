<?php

namespace App\Providers;

use App\Models\AdministrationApp\Permit;
use App\Models\AdministrationApp\PermitType;
use App\Models\AdministrationApp\UserAttendance;
use App\Models\AdministrationApp\UserTimeworkSchedule;
use App\Models\CoreApp\Company;
use App\Models\CoreApp\Departement;
use App\Models\CoreApp\JobLevel;
use App\Models\CoreApp\JobPosition;
use App\Models\CoreApp\TimeWork;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Observers\UserObserver;
use App\Policies\AdministrationApp\AttendancePolicy;
use App\Policies\AdministrationApp\PermitPolicy;
use App\Policies\AdministrationApp\PermitTypePolicy;
use App\Policies\AdministrationApp\SchedulePolicy;
use App\Policies\CoreApp\CompanyPolicy;
use App\Policies\CoreApp\DepartementPolicy;
use App\Policies\CoreApp\JobLevelPolicy;
use App\Policies\CoreApp\JobPositionPolicy;
use App\Policies\CoreApp\TimeWorkPolicy;
use App\Policies\CoreApp\UserPolicy;
use App\Policies\RolePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        date_default_timezone_set(config('app.timezone'));

        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Departement::class, DepartementPolicy::class);
        Gate::policy(JobLevel::class, JobLevelPolicy::class);
        Gate::policy(JobPosition::class, JobPositionPolicy::class);
        Gate::policy(TimeWork::class, TimeWorkPolicy::class);
        Gate::policy(Permit::class, PermitPolicy::class);
        Gate::policy(PermitType::class, PermitTypePolicy::class);
        Gate::policy(UserAttendance::class, AttendancePolicy::class);
        Gate::policy(UserTimeworkSchedule::class, SchedulePolicy::class);
    }
}
