<?php
namespace App\Repositories\Services\CoreApp;

use App\Models\AdministrationApp\UserTimeworkSchedule;
use App\Models\User;
use App\Models\views\EmployeView;
use App\Repositories\Interfaces\CoreApp\UserInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserService implements UserInterface
{
    protected $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    /**
     * @inheritDoc
     */
    public function all()
    {
        return $this->model->all();
    }

    /**
     * @inheritDoc
     */
    public function create(array $data)
    {
        try {
            DB::beginTransaction();
            // // Hash password or fallback to a default value
            $hashedPassword = bcrypt($data['password']);
            // Create the main model
            $datacreate = [
                'company_id' => $data['company_id'] ?? null,
                'nip' => $data['nip'],
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $hashedPassword,
                'avatar' => $data['avatar'] ?? 'default.png',
                'status' => $data['status'] ?? 'active',
            ];
            // dd($datacreate);
            $model = $this->model->create($datacreate);
            // Assign role to the user (validate role existence)
            if (!empty($data['role'])) {
                $model->assignRole($data['role']);
            }
            // Update or create related data
            if (method_exists($this, 'updateOrCreateRelatedData')) {
                $this->updateOrCreateRelatedData($model, $data);
            }
            DB::commit();
            return $model;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error creating user: ' . $e->getMessage(), [
                'data' => $data,
                'exception' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to create user. Please try again. ' . $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(int $id): bool
    {
        $find = $this->model->find($id);
        if ($find) {
            return $find->delete();
        }
        throw new ModelNotFoundException("User with ID {$id} not found.");
    }

    /**
     * @inheritDoc
     */
    public function find(int $id)
    {
        return $this->model
            ->with(
                'details',
                'address',
                'salaries',
                'families',
                'formalEducations',
                'informalEducations',
                'workExperiences',
                'employee'
            )->findOrFail($id);
    }
    /**
     * @inheritDoc
     */
    public function profile()
    {
        $id = Auth::user()->id;
        return EmployeView::find($id);
    }

    /**
     * @inheritDoc
     */
    public function paginate(int $page, int $limit, ?string $search = null)
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole(['super_admin', 'Administrator']);

        $query = EmployeView::query();
        // Filter data berdasarkan role
        if (!$isAdmin) {
            $query->whereHas('employee', function ($q) use ($user) {
                $q->where('departement_id', $user->employee->departement_id);
            });
        }
        // Pencarian (search)
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', '%' . $search . '%')
                    ->orWhere('nip', 'LIKE', '%' . $search . '%')
                    ->orWhere('email', 'LIKE', '%' . $search . '%');
            });
        }
        // Paginate hasil query
        return $query->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * @inheritDoc
     */
    public function update(int $id, array $data)
    {
        try {
            DB::beginTransaction();
            // Create main model
            $model = $this->model->with(
                'details',
                'address',
                'salaries',
                'families',
                'formalEducations',
                'informalEducations',
                'workExperiences',
                'employee'
            )->find($id);
            $model->company_id = $data['company_id'];
            $model->nip = $data['nip'];
            $model->name = $data['name'];
            $model->email = $data['email'];
            $model->status = $data['status'];
            if ($data['password']) {
                $model->password = bcrypt($data['password']);
            }
            if ($data['avatar']) {
                $model->avatar = $data['avatar'];
            }
            $model->save();
            // Assign role to the user
            if (isset($data['role'])) {
                $model->syncRoles($data['role']);
            }
            $this->updateOrCreateRelatedData($model, $data);

            DB::commit();
            return $model;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error creating user: ' . $e->getMessage(), [
                'data' => $data,
                'exception' => $e,
            ]);
            dd($e->getMessage());
            throw new \Exception('Failed to update user. Please try again.');
        }
    }

    /**
     * @inheritDoc
     */
    public function update_upload_avatar($file)
    {
        $user = Auth::user();
        $baseDir = config('app.basepath');

        // File baru
        $uploadedFile = $file;
        $path = "avatar-users";
        $fileName = strtolower($user->nip . '-' . time() . '.png');
        $filePath = "$baseDir/$path/$fileName";

        // Simpan file baru sebelum menghapus file lama
        // Storage::disk(config('app.disk'))
        //     ->putFileAs($baseDir, $uploadedFile, $fileName, 'public');
        dd($filePath);
        // Hapus avatar lama hanya jika file sebelumnya ada dan berbeda dengan yang baru
        // if (!empty($user->avatar) && $user->avatar !== $filePath) {
        //     if (Storage::disk(config('app.disk'))->exists("$baseDir/$user->avatar")) {
        //         Storage::disk(config('app.disk'))->delete("$baseDir/$user->avatar");
        //     }
        // }

        // Update avatar user dalam transaksi database
        // return DB::transaction(function () use ($user, $path, $fileName) {
        //     $user->update(['avatar' => "$path/$fileName"]);
        // });
    }

    /**
     * @inheritDoc
     */
    private function updateOrCreateRelatedData($model, $data)
    {
        // Update related details
        $model->details()->updateOrCreate([], array_filter($data, fn($key) => in_array($key, ['phone', 'placebirth', 'datebirth', 'gender', 'blood', 'marital_status', 'religion']), ARRAY_FILTER_USE_KEY));

        // Update related addresses
        $model->address()->updateOrCreate([], array_filter($data, fn($key) => in_array($key, ['identity_type', 'identity_numbers', 'province', 'city', 'citizen_address', 'residential_address']), ARRAY_FILTER_USE_KEY));

        // Update salaries
        $model->salaries()->updateOrCreate([], array_filter($data, fn($key) => in_array($key, ['basic_salary', 'payment_type']), ARRAY_FILTER_USE_KEY));

        // Update employee details
        $model->employee()->updateOrCreate([], array_filter($data, fn($key) => in_array($key, ['departement_id', 'job_position_id', 'job_level_id', 'approval_line_id', 'approval_manager_id', 'join_date', 'sign_date', 'resign_date', 'bank_name', 'bank_number', 'bank_holder']), ARRAY_FILTER_USE_KEY));

        // Bulk update family, education, and work experience
        if (isset($data['family']) && count($data['family']) > 0) {
            $this->bulkUpdateOrCreate($model->families(), $data['family'] ?? []);
        }
        if (isset($data['formal_education']) && count($data['formal_education']) > 0) {
            $this->bulkUpdateOrCreate($model->formalEducations(), $data['formal_education'] ?? []);
        }
        if (isset($data['informal_education']) && count($data['informal_education']) > 0) {
            $this->bulkUpdateOrCreate($model->informalEducations(), $data['informal_education'] ?? []);
        }
        if (isset($data['work_experience']) && count($data['work_experience']) > 0) {
            $this->bulkUpdateOrCreate($model->workExperiences(), $data['work_experience'] ?? []);
        }
    }

    /**
     * @inheritDoc
     */
    private function bulkUpdateOrCreate($relation, $data)
    {
        try {
            $existingIds = $relation->pluck('id')->toArray();
            $newIds = array_column($data, 'id');
            $idsToDelete = array_diff($existingIds, $newIds);

            if (!empty($idsToDelete)) {
                $relation->whereIn('id', $idsToDelete)->delete();
            }

            foreach ($data as $item) {
                if (isset($item['id']) && in_array($item['id'], $existingIds)) {
                    $relation->where('id', $item['id'])->update($item);
                } else {
                    $relation->create($item);
                }
            }
            return $data;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @inheritDoc
     */
    public function countAll(): int
    {
        return $this->model->count();
    }

    /**
     * @inheritDoc
     */
    public function findbyNip(string $nip)
    {
        return $this->model->with(
            'details',
            'address',
            'salaries',
            'families',
            'formalEducations',
            'informalEducations',
            'workExperiences',
            'employee'
        )->where('nip', $nip)->first();
    }

    /**
     * @inheritDoc
     */
    public function findUserHr()
    {
        return $this->model->whereHas('employee', function ($u) {
            $u->whereHas('departement', function ($d) {
                $d->where('name', 'HRGA');
            });
        })->get();
    }

    /**
     * @inheritDoc
     */
    public function login(array $data)
    {
        // Validasi input untuk memastikan 'indicatour' dan 'password' tersedia
        if (empty($data['indicatour']) || empty($data['password'])) {
            return [
                'success' => false,
                'message' => 'NIP dan password harus diisi.',
                'user' => null
            ];
        }

        // Cari pengguna berdasarkan email atau NIP, dan pastikan device_info sesuai
        $user = $this->model->where(function ($query) use ($data) {
            $query->where('email', $data['indicatour'])
                ->orWhere('nip', $data['indicatour']);
        });
        if (isset($data['device_info']) && !empty($data['device_info'])) {
            $user->where(function ($query) use ($data) {
                $query->where('device_id', $data['device_info'])
                    ->orWhereNull('device_id');
            });
        }

        $detail = $user->first();
        // Jika pengguna tidak ditemukan
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Pengguna dengan NIP dan Imei device tersebut tidak ditemukan. Pastikan anda login dengan device yang sesuai dengan device yang terdaftar pada akun anda sebelumnya!',
                'user' => null
            ];
        }

        // Coba autentikasi dengan email pengguna dan password yang diberikan
        if (Auth::attempt(['email' => $detail->email, 'password' => $data['password']])) {
            return [
                'success' => true,
                'message' => 'Login berhasil.',
                'user' => Auth::user() // Kembalikan data pengguna yang sudah login
            ];
        }

        // Jika autentikasi gagal
        return [
            'success' => false,
            'message' => 'Password yang dimasukkan salah.',
            'user' => null
        ];
    }

    /**
     * @inheritDoc
     */
    public function auth_update_family(array $data)
    {
        $find = $this->find(Auth::id());
        $proses = $this->bulkUpdateOrCreate($find->families(), $data ?? []);
        return $proses;
    }

    /**
     * @inheritDoc
     */
    public function auth_update_formal_education(array $data)
    {
        $find = $this->find(Auth::id());
        return $this->bulkUpdateOrCreate($find->formalEducations(), $data ?? []);
    }

    /**
     * @inheritDoc
     */
    public function auth_update_informal_education(array $data)
    {
        $find = $this->find(Auth::id());
        return $this->bulkUpdateOrCreate($find->informalEducations(), $data ?? []);
    }

    /**
     * @inheritDoc
     */
    public function auth_update_work_experience(array $data)
    {
        $find = $this->find(Auth::id());
        return $this->bulkUpdateOrCreate($find->workExperiences(), $data ?? []);
    }

    /**
     * @inheritDoc
     */
    public function update_password(int $userId, array $data)
    {
        $find = $this->find($userId);
        $validate_password = Hash::check($data['password'], $find->password);
        if ($validate_password) {
            $find->update([
                'password' => Hash::make($data['confirmation_new_password'])
            ]);
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function schedule(int $userId)
    {
        $schedule = UserTimeworkSchedule::with('timework')
            ->where([
                'user_id' => $userId,
                'work_day' => Carbon::now()->format('Y-m-d')
            ])
            ->firstOrFail();
        return $schedule;
    }

    /**
     * @inheritDoc
     */
    public function auth_update_bank(array $data)
    {
        $auth = Auth::user();
        $user = $this->model->find($auth->id);
        $user->employee->updateOrCreate([
            'user_id' => $auth->id
        ], $data);
        return $user;
    }

    /**
     * @inheritDoc
     */
    public function profile_schedule_list(int $userId)
    {
        $schedule = UserTimeworkSchedule::with('timework')
            ->where('user_id', $userId)
            ->whereYear('work_day', Carbon::now()->year)
            ->whereMonth('work_day', Carbon::now()->month)
            ->get();
        return $schedule;
    }
}
