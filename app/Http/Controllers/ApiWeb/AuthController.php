<?php

namespace App\Http\Controllers\ApiWeb;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserResource;
use App\Models\CoreApp\Notification;
use App\Models\User;
use App\Repositories\Interfaces\CoreApp\UserInterface;
use App\Support\Logger;
use App\Support\UploadFile;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected $proses;
    public function __construct(UserInterface $proses)
    {
        $this->proses = $proses;
    }
    /**
     * Login of the resource.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'indicatour' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }
        $proses = $this->proses->login($validator->getData());
        if ($proses['success']) {
            $user = Auth::user();
            $success['token'] = $user->createToken('esas-app')->plainTextToken;
            $success['token_type'] = 'Bearer';
            $success['name'] = $user->name;
            $success['userId'] = $user->id;
            Logger::log('login', Auth::user(), $user->toArray());
            return $this->sendResponse($success, 'User login successfully.');
        }
        return $this->sendError('Unauthorised.', ['error' => $proses['message']]);
    }

    /**
     * Get user permissions.
     */
    public function permission()
    {
        $user = Auth::user();
        if (!$user) {
            return $this->sendError('Unauthorized', ['error' => 'User not authenticated'], 401);
        }

        return $this->sendResponse([
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ], 'User permissions retrieved successfully.');
    }

    /**
     * Logout from the system.
     */
    public function logout()
    {
        $user = Auth::user();
        if (!$user) {
            return $this->sendError('Unauthorized', ['error' => 'User not authenticated'], 401);
        }
        Logger::log('logout', Auth::user(), $user->toArray());
        // Hapus semua token pengguna (berlaku untuk Sanctum)
        $user->tokens()->delete();

        return $this->sendResponse([], 'User logged out successfully.');
    }
    /**
     * Profile from the system.
     */
    public function profile()
    {
        try {
            $detail = $this->proses->find(Auth::user()->id);
            return $this->sendResponse(new UserResource($detail), 'User detail access successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Process errors.', ['error' => $e->getMessage()]);
        }
    }
    public function profile_update(Request $request)
    {
        try {
            $validated = $this->validateUser($request);
            $user = User::findOrFail(Auth::id());

            // Simpan salinan data sebelum update untuk keperluan logging
            $payload = [
                'before' => $user->toArray(),
                'after' => $validated
            ];

            // Data yang akan di-update
            $updateData = [
                'company_id' => $validated['company_id'],
                'name' => $validated['name'],
                'nip' => $validated['nip'],
                'email' => $validated['email'],
                'status' => $validated['status'],
            ];

            // Update password jika ada
            if (!empty($validated['password'])) {
                $updateData['password'] = bcrypt($validated['password']);
            }

            $user->update($updateData);

            // Update relasi hasOne
            $user->details()->updateOrCreate([], $validated['details'] ?? []);
            $user->address()->updateOrCreate([], $validated['address'] ?? []);
            $user->salaries()->updateOrCreate([], $validated['salaries'] ?? []);
            $user->employee()->updateOrCreate([], $validated['employee'] ?? []);

            // Update avatar jika file dikirim
            if ($request->hasFile('avatar_file')) {
                UploadFile::removeFromSpaces($user->avatar);
                $upload = UploadFile::uploadToSpaces(
                    $request->file('avatar_file'),
                    'avatars',
                    Carbon::now()->format('YmdHis')
                );

                if (is_array($upload) && isset($upload['url'], $upload['path'])) {
                    $user->update(['avatar' => $upload['path']]);
                }
            }

            Logger::log('update', $user, $payload);
            return $this->sendResponse($user->fresh(), 'Data User berhasil diperbaharui');

        } catch (\Exception $e) {
            return $this->sendError('Terjadi kesalahan saat memperbarui data.', [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function pemberitahuan(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'page' => 'required|integer|min:1',
            'sortBy' => 'nullable|array',
            'sortBy.*.key' => 'required_with:sortBy|string|in:name,latitude,longitude,radius,full_address,created_at,updated_at',
            'sortBy.*.order' => 'required_with:sortBy|string|in:asc,desc',
            'search' => 'nullable|array',
            'search.start' => 'nullable|date_format:Y-m-d',
            'search.end' => 'nullable|date_format:Y-m-d',
        ]);

        $page = $validated['page'];
        $search = $validated['search'] ?? [];
        $sortBy = $validated['sortBy'] ?? [];
        $query = Notification::query();
        // Filtering
        if (!empty($search)) {
            if (!empty($search['start']) && !empty($search['end'])) {
                $query->whereBetween('created_at', [$search['start'], $search['end']]);
            }
        }
        // Sorting
        if (!empty($sortBy)) {
            foreach ($sortBy as $sort) {
                $query->orderBy($sort['key'], $sort['order']);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }
        $id = Auth::user()->id;
        $query->where('notifiable_id', $id);
        // Pagination
        $data = $query->paginate(20, ['*'], 'page', $page);
        Logger::log('list paginate', new Notification(), $data->toArray());
        return $this->sendResponse($data, 'Data pemberitahuan berhasil diambil');
    }

    public function pemberitahuan_read($id)
    {
        $query = Notification::find($id);
        if ($query) {
            $query->read_at = Carbon::now();
            $query->save();
        }
        Logger::log('show', $query ?? new Notification(), $query->toArray());
        return $this->sendResponse($query, 'Data pemberitahuan telah dibaca');
    }

    private function validateUser(Request $request): array
    {
        return $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'nip' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['nullable', 'max:20'],
            'status' => ['required', 'in:active,inactive'],
            'details.phone' => ['required', 'string', 'max:20'],
            'details.placebirth' => ['required', 'string', 'max:100'],
            'details.datebirth' => ['required', 'date'],
            'details.gender' => ['required', 'in:m,f'],
            'details.blood' => ['nullable', 'in:a,b,ab,o'],
            'details.marital_status' => ['required', 'in:single,married,widow,widower'],
            'details.religion' => ['required', 'string', 'max:50'],
            'address.identity_type' => ['required', 'in:ktp,sim,passport'],
            'address.identity_numbers' => ['required', 'string', 'max:100'],
            'address.province' => ['required', 'string', 'max:100'],
            'address.city' => ['required', 'string', 'max:100'],
            'address.citizen_address' => ['required', 'string', 'max:255'],
            'address.residential_address' => ['required', 'string', 'max:255'],
            'salaries.basic_salary' => ['required', 'numeric', 'min:0'],
            'salaries.payment_type' => ['required', 'in:Monthly,Weekly,Daily'],
            'employee.departement_id' => ['required', 'integer', 'exists:departements,id'],
            'employee.job_position_id' => ['required', 'integer', 'exists:job_positions,id'],
            'employee.job_level_id' => ['required', 'integer', 'exists:job_levels,id'],
            'employee.approval_line_id' => ['required', 'integer', 'exists:users,id'],
            'employee.approval_manager_id' => ['required', 'integer', 'exists:users,id'],
            'employee.join_date' => ['required', 'date'],
            'employee.sign_date' => ['required', 'date'],
            'employee.bank_name' => ['nullable', 'string', 'max:100'],
            'employee.bank_number' => ['nullable', 'string', 'max:50'],
            'employee.bank_holder' => ['nullable', 'string', 'max:100'],
            'avatar_file' => ['nullable', 'file', 'image', 'max:10048'],
        ]);
    }

    public function unauthorized()
    {
        return response()->json(['message' => 'unauthorized'], 401);
    }
}
