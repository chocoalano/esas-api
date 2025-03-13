<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdministrationApp\QrPresence;
use App\Models\AdministrationApp\QrPresenceTransaction;
use App\Models\AdministrationApp\UserAttendance;
use App\Models\User;
use App\Repositories\Interfaces\CoreApp\DepartementInterface;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AttendanceDeviceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function company(Request $request, DepartementInterface $proses)
    {
        try {
            $input = $request->all();
            $data = $proses->companyall($input['search']);
            return $this->sendResponse($data, 'List show shift successfully.');
        } catch (\Exception $e) {
            return $this->sendError('List failed.', ['error' => $e->getMessage()], 500);
        }
    }
    public function departement(Request $request, DepartementInterface $proses)
    {
        try {
            $input = $request->all();
            $data = $proses->all($input['companyId'], $input['search']);
            return $this->sendResponse($data, 'List show departement successfully.');
        } catch (\Exception $e) {
            return $this->sendError('List failed.', ['error' => $e->getMessage()], 500);
        }
    }
    public function shift(Request $request, DepartementInterface $proses)
    {
        try {
            $input = $request->all();
            $data = $proses->shift($input['companyId'], $input['deptId']);
            return $this->sendResponse($data, 'List show shift successfully.');
        } catch (\Exception $e) {
            return $this->sendError('List failed.', ['error' => $e->getMessage()], 500);
        }
    }
    public function validate_user(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'nip' => 'required|numeric',
            'departement_id' => 'required|exists:departments,id',
        ]);

        $input = $validatedData->getData();
        try {
            $data = User::where('nip', 'like', '%' . $input['nip'] . '%')
                ->whereHas('employee', function ($query) use ($input) {
                    $query->where('departement_id', $input['departement_id']);
                })
                ->firstOrFail();
            return $this->sendResponse($data, 'List show shift successfully.');
        } catch (\Exception $e) {
            // dd($e->getMessage());
            return $this->sendError('User fetching failed.', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => 'required|exists:companies,id', // company_id harus ada dan ada di tabel companies
                'departement_id' => 'required|exists:departements,id', // departement_id harus ada dan ada di tabel departements
                'shift_id' => 'required|exists:time_workes,id', // shift_id harus ada dan ada di tabel shifts
                'type_presence' => 'required|in:in,out', // type_presence harus 'in' atau 'out'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422); // Kembalikan error jika validasi gagal
            }
            $input = $validator->getData();
            // Mendapatkan waktu saat ini
            $currentTime = Carbon::now();
            // Menambahkan waktu kedaluwarsa (10 detik dari waktu saat ini)
            $expiresAt = $currentTime->copy()->addSeconds(10);
            // Membuat token yang aman
            $token = Crypt::encryptString($currentTime->format('Y-m-d H:i:s'));
            $qr = QrPresence::firstOrCreate(
                ['token' => $token], // Pastikan token unik
                [
                    'type' => $input['type_presence'],
                    'departement_id' => $input['departement_id'],
                    'timework_id' => $input['shift_id'],
                    'for_presence' => $currentTime,
                    'expires_at' => $expiresAt,
                ]
            );
            return $this->sendResponse($qr, 'Save token qr successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Save token qr failed.', ['error' => $e->getMessage()], 500);
        }
    }

    public function qr_attendance(Request $request)
    {
        $validatedData = $request->validate([
            'type' => 'required|string|in:in,out',
            'id' => 'required|numeric',
            'token' => 'required|string',
        ]);
        try {
            return $this->qrAttendance(Auth::id(), $validatedData['type'], $validatedData['id'], $validatedData['token']);
        } catch (\Exception $e) {
            Log::error("Terjadi kesalahan absensi QR: " . $e->getMessage(), [
                'exception' => $e,
                'user_id' => auth()->id(), // Tambahkan user ID jika ada
                'request_data' => request()->all(), // Log request data jika perlu
            ]);
            return $this->sendError('Terjadi kesalahan server.', $e->getMessage(), 500);
        }
    }

    private function qrAttendance(int $userId, string $type, int $idtoken, string $token)
    {
        if (!in_array($type, ['in', 'out'])) {
            throw new \Exception('Tipe absen tidak valid!');
        }

        $currentTime = Carbon::now()->setTimezone(config('app.timezone'));
        $currentDate = Carbon::today()->setTimezone(config('app.timezone'));

        // Ambil data QR Presence + departemen + waktu kerja
        $qrPresence = DB::table('qr_presences as qrp')
            ->join('departements as d', 'qrp.departement_id', '=', 'd.id')
            ->join('time_workes as tw', 'qrp.timework_id', '=', 'tw.id')
            ->select([
                'qrp.id',
                'qrp.type',
                'qrp.token',
                'qrp.expires_at',
                'qrp.departement_id',
                'qrp.timework_id',
                'd.name as departement_name',
                'tw.in as work_start_time',
                'tw.company_id'
            ])
            ->where([
                ['qrp.type', '=', $type],
                ['qrp.id', '=', $idtoken]
            ])
            ->first();
        if (!$qrPresence) {
            throw new \Exception('Token tidak ditemukan!');
        }

        // Cek apakah QR sudah digunakan
        if (DB::table('qr_presence_transactions')->where('qr_presence_id', $qrPresence->id)->exists()) {
            throw new \Exception('Kode QR sudah digunakan!');
        }

        // Cek apakah QR sudah expired
        if ($currentTime->gt($qrPresence->expires_at)) {
            throw new \Exception('Kode QR sudah kadaluarsa!');
        }

        // Cek apakah user terdaftar di departemen QR
        $isUserInDepartment = DB::table('users')
            ->where('id', $userId)
            ->whereExists(function ($query) use ($qrPresence) {
                $query->select(DB::raw(1))
                    ->from('user_employes')
                    ->whereColumn('user_employes.user_id', 'users.id')
                    ->where('user_employes.departement_id', $qrPresence->departement_id);
            })
            ->exists();

        if (!$isUserInDepartment) {
            throw new \Exception('User tidak terdaftar di departemen ini.');
        }

        // Ambil jadwal kerja user (jika ada)
        $scheduleId = DB::table('user_timework_schedules')
            ->where('user_id', $userId)
            ->where('work_day', $currentDate)
            ->value('id');

        // Jika absen keluar, pastikan sudah ada absen masuk
        if (
            $type === 'out' && !DB::table('user_attendances')
                ->where('user_id', $userId)
                ->whereDate('created_at', $currentDate)
                ->whereNotNull('time_in')
                ->exists()
        ) {
            throw new \Exception('Anda harus melakukan absensi masuk sebelum absensi pulang!');
        }

        // Hitung status keterlambatan
        $status = $currentTime->lt($qrPresence->work_start_time) ? 'normal' : 'late';
        $statusInOut = ($type === 'in') ? $status : ($currentTime->lt($qrPresence->work_start_time) ? 'unlate' : 'normal');

        // Ambil lokasi perusahaan
        $company = DB::table('companies')
            ->where('id', $qrPresence->company_id)
            ->select('latitude', 'longitude')
            ->first();

        DB::transaction(function () use ($userId, $currentTime, $currentDate, $type, $qrPresence, $scheduleId, $statusInOut, $company) {
            // Cek apakah user sudah memiliki absen hari ini
            $attendance = UserAttendance::where('user_id', $userId)
                ->whereDate('created_at', $currentDate)
                ->first();

            if ($attendance) {
                // Update absen jika sudah ada
                $attendance->update([
                    'updated_at' => $currentTime,
                    'time_in' => $type === 'in' ? $currentTime : $attendance->time_in,
                    'status_in' => $type === 'in' ? $statusInOut : $attendance->status_in,
                    'lat_in' => $type === 'in' ? $company->latitude : $attendance->lat_in,
                    'long_in' => $type === 'in' ? $company->longitude : $attendance->long_in,
                    'type_in' => $type === 'in' ? 'qrcode' : $attendance->type_in,
                    'created_by' => $type === 'in' ? $userId : $userId,
                    'time_out' => $type === 'out' ? $currentTime : $attendance->time_out,
                    'status_out' => $type === 'out' ? $statusInOut : ($attendance->status_out ?? 'normal'),
                    'lat_out' => $type === 'out' ? $company->latitude : $attendance->lat_out,
                    'long_out' => $type === 'out' ? $company->longitude : $attendance->long_out,
                    'type_out' => $type === 'out' ? 'qrcode' : $attendance->type_out,
                    'updated_by' => $type === 'out' ? $userId : null,
                ]);
            } else {
                // Buat absen baru jika belum ada
                UserAttendance::create([
                    'user_id' => $userId,
                    'user_timework_schedule_id' => $scheduleId,
                    'created_at' => $currentTime,
                    'updated_at' => $currentTime,
                    'time_in' => $type === 'in' ? $currentTime : null,
                    'status_in' => $type === 'in' ? $statusInOut : 'normal',
                    'lat_in' => $type === 'in' ? $company->latitude : null,
                    'long_in' => $type === 'in' ? $company->longitude : null,
                    'type_in' => $type === 'in' ? 'qrcode' : null,
                    'created_by' => $type === 'in' ? $userId : null,
                    'time_out' => $type === 'out' ? $currentTime : null,
                    'status_out' => $type === 'out' ? $statusInOut : 'normal',
                    'lat_out' => $type === 'out' ? $company->latitude : null,
                    'long_out' => $type === 'out' ? $company->longitude : null,
                    'type_out' => $type === 'out' ? 'qrcode' : null,
                    'updated_by' => $type === 'out' ? $userId : null,
                ]);
            }

            // Simpan transaksi QR
            QrPresenceTransaction::create([
                'qr_presence_id' => $qrPresence->id,
                'user_attendance_id' => $attendance->id ?? DB::getPdo()->lastInsertId(), // Ambil ID terakhir jika baru dibuat
                'token' => $qrPresence->token,
                'created_at' => $currentTime,
                'updated_at' => $currentTime,
            ]);
        });

        return response()->json([
            'message' => 'success',
            'result' => "Absensi {$type} berhasil disimpan."
        ]);
    }

    public function face_attendance(Request $request)
    {
        $validated = $request->validate([
            'nip' => 'required|numeric|exists:users,nip',
            'departement_id' => 'required|numeric|exists:departements,id',
            'shift_id' => 'required|numeric|exists:time_workes,id',
            'type' => 'required|string|in:in,out',
        ]);

        $user = User::with('company')->where('nip', $validated['nip'])->firstOrFail();
        $time = now()->setTimezone(config('app.timezone'))->format('H:i:s');

        if ($validated['type'] === 'out' && !UserAttendance::where('user_id', $user->id)->whereDate('created_at', now())->exists()) {
            return $this->sendError('Terjadi kesalahan.', ['error' => 'Anda harus absen masuk terlebih dulu sebelum absen pulang!'], 404);
        }

        // Tentukan stored procedure yang akan digunakan
        $procedure = $validated['type'] === 'in' ? 'UpdateAttendanceIn' : 'UpdateAttendanceOut';

        // Jalankan stored procedure
        try {
            $exec = DB::select("CALL {$procedure}(?,?,?,?,?,?)", [
                $user->id,
                $validated['shift_id'],
                $user->company->latitude,
                $user->company->longitude,
                "{$user->nip}.png",
                $time
            ]);

            // Cek status eksekusi
            $status = !empty($exec) && $exec[0]->success === 1;

            return $status
                ? $this->sendResponse('success', "Absensi {$validated['type']} berhasil disimpan.")
                : $this->sendError('Terjadi kesalahan.', ['error' => 'Gagal menyimpan absensi.'], 500);
        } catch (QueryException $e) {
            dd($e);
            // Tangkap exception database
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();

            // Log error untuk debugging
            \Log::error("Error Database (QueryException): " . $errorMessage);

            // Berikan respons error yang lebih spesifik (opsional)
            if ($errorCode === '45000') { // Contoh: Error dari SIGNAL di stored procedure
                // Ekstrak pesan dari SQLSTATE
                $message = substr($errorMessage, strpos($errorMessage, "SQLSTATE[45000]:") + 16);
                return response()->json(['status' => 'error', 'message' => $message], 400); // Bad Request
            }

            return $this->sendError('Terjadi kesalahan database.', $e->getMessage(), 500); // Internal Server Error
        } catch (\Exception $e) {
            dd($e);
            // Tangkap exception umum
            $errorMessage = $e->getMessage();

            // Log error untuk debugging
            \Log::error("Error Umum: " . $errorMessage);

            return $this->sendError('Terjadi kesalahan server.', $e->getMessage(), 500); // Internal Server Error
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
