<?php

namespace App\Http\Controllers\ApiWeb;

use App\Http\Controllers\Controller;
use App\Models\AdministrationApp\PermitType;
use App\Models\CoreApp\Company;
use App\Models\CoreApp\Departement;
use App\Models\CoreApp\JobLevel;
use App\Models\CoreApp\JobPosition;
use App\Models\CoreApp\TimeWork;
use App\Models\User;
use App\Models\views\UserTimeworkSchedule;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class KelengkapanFormController extends Controller
{
    public function all_company()
    {
        $data = Company::all();
        return $this->sendResponse($data, 'Data berhasil diambil');
    }
    public function all_roles()
    {
        $data = Role::all();
        return $this->sendResponse($data, 'Data berhasil diambil');
    }
    public function all_departement()
    {
        $data = Departement::all();
        return $this->sendResponse($data, 'Data berhasil diambil');
    }
    public function all_position()
    {
        $data = JobPosition::all();
        return $this->sendResponse($data, 'Data berhasil diambil');
    }
    public function all_level()
    {
        $data = JobLevel::all();
        return $this->sendResponse($data, 'Data berhasil diambil');
    }
    public function all_timework()
    {
        $data = TimeWork::all();
        return $this->sendResponse($data, 'Data berhasil diambil');
    }
    public function all_user()
    {
        $data = User::all();
        return $this->sendResponse($data, 'Data berhasil diambil');
    }
    public function all_schedule()
    {
        $data = UserTimeworkSchedule::all();
        return $this->sendResponse($data, 'Data berhasil diambil');
    }
    public function all_permit()
    {
        $data = PermitType::all();
        return $this->sendResponse($data, 'Data berhasil diambil');
    }
    public function filter_company()
    {
        $data = Company::all();
        return $this->sendResponse($data, 'Data berhasil diambil');
    }
    public function filter_departement(Request $request)
    {
        $request->validate([
            'company_id' => 'nullable|integer|exists:companies,id',
        ]);

        $data = Departement::when($request->company_id, function ($query) use ($request) {
            $query->where('company_id', $request->company_id);
        })->get();

        return $this->sendResponse($data, 'Data berhasil diambil');
    }

    public function filter_position(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'departement_id' => 'required|integer|exists:departements,id',
        ]);
        $data = JobPosition::where($validated)->get();
        return $this->sendResponse($data, 'Data berhasil diambil');
    }
    public function filter_level(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'departement_id' => 'required|integer|exists:departements,id',
        ]);
        $data = JobLevel::where($validated)->get();
        return $this->sendResponse($data, 'Data berhasil diambil');
    }
    public function filter_timework(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'departement_id' => 'required|integer|exists:departements,id',
        ]);
        $data = TimeWork::where([
            'company_id' => $validated['company_id'],
            'departemen_id' => $validated['departement_id']
        ])->get();
        return $this->sendResponse($data, 'Data berhasil diambil');
    }
    public function filter_user(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'departement_id' => 'required|integer|exists:departements,id',
        ]);
        $data = User::where('company_id', $validated['company_id'])
            ->whereHas('employee', function ($emp) use ($validated) {
                $emp->where('departement_id', $validated['departement_id']);
            })->get();
        return $this->sendResponse($data, 'Data berhasil diambil');
    }
    public function filter_schedule(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'departement_id' => 'required|integer|exists:departements,id',
            'user_id' => 'required|integer|exists:users,id',
        ]);
        $data = UserTimeworkSchedule::with(([
            'user',
            'timeWork',
            'departement'
        ]))
            ->whereHas('user', function ($usr) use ($validated) {
                $usr->where('company_id', $validated['company_id'])
                    ->whereHas('employee', function ($emp) use ($validated) {
                        $emp->where('departement_id', $validated['departement_id']);
                    });
            })->get();
        return $this->sendResponse($data, 'Data berhasil diambil');
    }
}
