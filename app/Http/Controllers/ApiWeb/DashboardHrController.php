<?php

namespace App\Http\Controllers\ApiWeb;

use App\Http\Controllers\Controller;
use App\Models\AdministrationApp\Permit;
use App\Models\AdministrationApp\UserAttendance;
use App\Models\CoreApp\Departement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class DashboardHrController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function akun(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|integer|exists:companies,id',
            'start' => 'nullable|date|before_or_equal:end',
            'end' => 'nullable|date|after_or_equal:start',
        ]);
        // Query pengguna
        $query = User::query();
        // Filter berdasarkan company_id jika ada
        if (!empty($validated['company_id'])) {
            $query->where('company_id', $validated['company_id']);
        }
        if (!empty($validated['start'])) {
            $query->whereDate('created_at', '>=', $validated['start']);
        }
        if (!empty($validated['end'])) {
            $query->whereDate('created_at', '<=', $validated['end']);
        }
        $jumlah = $query->count();
        $data = [
            'total_user' => $jumlah,
            'company_id' => $validated['company_id'] ?? null,
        ];

        return $this->sendResponse($data, 'Jumlah akun berhasil dihitung');
    }
    public function departemen(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|integer|exists:companies,id',
            'start' => 'nullable|date|before_or_equal:end',
            'end' => 'nullable|date|after_or_equal:start',
        ]);
        // Query pengguna
        $query = Departement::query();
        // Filter berdasarkan company_id jika ada
        if (!empty($validated['company_id'])) {
            $query->where('company_id', $validated['company_id']);
        }
        if (!empty($validated['start'])) {
            $query->whereDate('created_at', '>=', $validated['start']);
        }
        if (!empty($validated['end'])) {
            $query->whereDate('created_at', '<=', $validated['end']);
        }
        $jumlah = $query->count();
        $data = [
            'total_departement' => $jumlah,
            'company_id' => $validated['company_id'] ?? null,
        ];

        return $this->sendResponse($data, 'Jumlah akun berhasil dihitung');
    }
    public function posisi(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|integer|exists:companies,id',
            'start' => 'nullable|date|before_or_equal:end',
            'end' => 'nullable|date|after_or_equal:start',
        ]);
        // Query pengguna
        $query = Departement::query();
        // Filter berdasarkan company_id jika ada
        if (!empty($validated['company_id'])) {
            $query->where('company_id', $validated['company_id']);
        }
        if (!empty($validated['start'])) {
            $query->whereDate('created_at', '>=', $validated['start']);
        }
        if (!empty($validated['end'])) {
            $query->whereDate('created_at', '<=', $validated['end']);
        }
        $jumlah = $query->count();
        $data = [
            'total_posisi' => $jumlah,
            'company_id' => $validated['company_id'] ?? null,
        ];

        return $this->sendResponse($data, 'Jumlah akun berhasil dihitung');
    }
    public function absen(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|integer|exists:companies,id',
            'start' => 'nullable|date|before_or_equal:end',
            'end' => 'nullable|date|after_or_equal:start',
            'now' => [
                'nullable',
                Rule::in(['true', 'false', true, false, 1, 0, '1', '0']),
            ],
        ]);
        // Query pengguna
        $query = UserAttendance::query();
        // Filter berdasarkan company_id jika ada
        if (!empty($validated['company_id'])) {
            $query->whereHas('user', function ($u) use ($validated) {
                $u->where('company_id', $validated['company_id']);
            });
        }
        if (!empty($validated['start'])) {
            $query->whereDate('created_at', '>=', $validated['start']);
        }
        if (!empty($validated['end'])) {
            $query->whereDate('created_at', '<=', $validated['end']);
        }
        if (!empty($validated['now'])) {
            $query->whereDate('created_at', Carbon::now()->format('Y-m-d'));
        }
        $jumlah = $query->count();
        $data = [
            'total_absen' => $jumlah,
            'company_id' => $validated['company_id'] ?? null,
        ];

        return $this->sendResponse($data, 'Jumlah akun berhasil dihitung');
    }
    public function absen_telat(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|integer|exists:companies,id',
            'start' => 'nullable|date|before_or_equal:end',
            'end' => 'nullable|date|after_or_equal:start',
            'now' => [
                'nullable',
                Rule::in(['true', 'false', true, false, 1, 0, '1', '0']),
            ],
        ]);
        // Query pengguna
        $query = UserAttendance::query();
        // Filter berdasarkan company_id jika ada
        if (!empty($validated['company_id'])) {
            $query->whereHas('user', function ($u) use ($validated) {
                $u->where('company_id', $validated['company_id']);
            });
        }
        if (!empty($validated['start'])) {
            $query->whereDate('created_at', '>=', $validated['start']);
        }
        if (!empty($validated['end'])) {
            $query->whereDate('created_at', '<=', $validated['end']);
        }
        if (!empty($validated['now'])) {
            $query->whereDate('created_at', Carbon::now()->format('Y-m-d'));
        }
        $jumlah = $query->where('status_in', 'late')->count();
        $data = [
            'total_absen' => $jumlah,
            'company_id' => $validated['company_id'] ?? null,
        ];

        return $this->sendResponse($data, 'Jumlah akun berhasil dihitung');
    }
    public function absen_alpha(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|integer|exists:companies,id',
            'start' => 'nullable|date|before_or_equal:end',
            'end' => 'nullable|date|after_or_equal:start',
            'now' => [
                'nullable',
                Rule::in(['true', 'false', true, false, 1, 0, '1', '0']),
            ],
        ]);
        // Query pengguna
        $query = UserAttendance::query();
        // Filter berdasarkan company_id jika ada
        if (!empty($validated['company_id'])) {
            $query->whereHas('user', function ($u) use ($validated) {
                $u->where('company_id', $validated['company_id']);
            });
        }
        if (!empty($validated['start'])) {
            $query->whereDate('created_at', '>=', $validated['start']);
        }
        if (!empty($validated['end'])) {
            $query->whereDate('created_at', '<=', $validated['end']);
        }
        if (!empty($validated['now'])) {
            $query->whereDate('created_at', Carbon::now()->format('Y-m-d'));
        }
        $jumlah = $query->where('status_in', 'late')->count();
        $data = [
            'total_absen' => $jumlah,
            'company_id' => $validated['company_id'] ?? null,
        ];

        return $this->sendResponse($data, 'Jumlah akun berhasil dihitung');
    }
    public function absen_chart(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|integer|exists:companies,id',
            'start' => 'nullable|date|before_or_equal:end',
            'end' => 'nullable|date|after_or_equal:start',
            'now' => [
                'nullable',
                Rule::in(['true', 'false', true, false, 1, 0, '1', '0']),
            ],
        ]);

        $query = UserAttendance::query();

        // Filter berdasarkan company_id
        if (!empty($validated['company_id'])) {
            $query->whereHas('user', function ($q) use ($validated) {
                $q->where('company_id', $validated['company_id']);
            });
        }

        // Filter tanggal
        if (!empty($validated['now'])) {
            $query->whereDate('created_at', Carbon::now()->format('Y-m-d'));
        } else {
            if (!empty($validated['start']) && !empty($validated['end'])) {
                $query
                ->whereDate('created_at', '>=', $validated['start'])
                ->whereDate('created_at', '<=', $validated['end']);
            }else{
                $query->whereDate('created_at', Carbon::now()->format('Y-m-d'));
            }
        }

        // Ambil data dan kelompokkan berdasarkan tanggal
        $attendances = $query
            ->selectRaw("DATE(created_at) as date")
            ->selectRaw("SUM(CASE WHEN status_in = 'normal' OR status_in = 'unlate' THEN 1 ELSE 0 END) as normal")
            ->selectRaw("SUM(CASE WHEN status_in = 'late' THEN 1 ELSE 0 END) as telat")
            ->groupByRaw("DATE(created_at)")
            ->orderByRaw("DATE(created_at)")
            ->get();

        // Format hasilnya untuk chart
        $labels = [];
        $normal = [];
        $telat = [];

        foreach ($attendances as $row) {
            $labels[] = $row->date;
            $normal[] = (int) $row->normal;
            $telat[] = (int) $row->telat;
        }

        return response()->json([
            'labels' => $labels,
            'normal' => $normal,
            'telat' => $telat,
        ]);
    }

    public function izin(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|integer|exists:companies,id',
            'start' => 'nullable|date|before_or_equal:end',
            'end' => 'nullable|date|after_or_equal:start',
        ]);
        $query = Permit::query();
        // Filter berdasarkan company_id melalui relasi user
        if (!empty($validated['company_id'])) {
            $query->whereHas('user', function ($q) use ($validated) {
                $q->where('company_id', $validated['company_id']);
            });
        }
        // Filter berdasarkan tanggal izin (created_at atau field sesuai kebutuhan)
        if (!empty($validated['start'])) {
            $query->whereDate('created_at', '>=', $validated['start']);
        }
        if (!empty($validated['end'])) {
            $query->whereDate('created_at', '<=', $validated['end']);
        }
        $jumlah = $query->count();
        $data = [
            'total_izin' => $jumlah,
            'company_id' => $validated['company_id'] ?? null,
            'start' => $validated['start'] ?? null,
            'end' => $validated['end'] ?? null,
        ];
        return $this->sendResponse($data, 'Jumlah izin berhasil dihitung');
    }

}
