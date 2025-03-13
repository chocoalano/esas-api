<?php
namespace App\Repositories\Services\Tools;

use App\Models\Tools\BugReport;
use App\Repositories\Interfaces\Tools\BugReportInterface;
use App\Support\UploadFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BugReportService implements BugReportInterface
{
    protected $model;

    public function __construct(BugReport $model)
    {
        $this->model = $model;
    }

    /**
     * @inheritDoc
     */
    public function create(array $data)
    {
        $user = Auth::user();
        $upload = UploadFile::uploadWithResize($data['image'], 'bug-reports');
        $data['company_id'] = $user->company_id;
        $data['user_id'] = $user->id;
        $data['status'] = false;
        $data['image'] = $upload;
        // Simpan data ke database
        return $this->model->create($data);
    }

    /**
     * @inheritDoc
     */
    public function paginate(int $page, int $limit, string $search = '')
    {
        $user = Auth::user();
        $query = $this->model->newQuery();

        // Tambahkan kondisi untuk user_id dan status
        $query->where('user_id', $user->id);
        $query->where('status', false);

        // Filter berdasarkan search jika tidak kosong
        if (!empty($search)) {
            $query->where('title', 'LIKE', '%' . $search . '%');
        }

        // Mengembalikan data dengan pagination
        return $query->paginate($limit, ['*'], 'page', $page);
    }
}
