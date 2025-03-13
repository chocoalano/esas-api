<?php
namespace App\Repositories\Services\CoreApp;

use App\Models\CoreApp\Company;
use App\Models\CoreApp\Departement;
use App\Models\CoreApp\TimeWork;
use App\Repositories\Interfaces\CoreApp\DepartementInterface;

class DepartementService implements DepartementInterface
{
    protected $model;
    protected $company;
    protected $shift;

    public function __construct(Departement $model, Company $company, TimeWork $timeWork)
    {
        $this->model = $model;
        $this->company = $company;
        $this->shift = $timeWork;
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
    public function existsByName(int $deptId, string $deptName)
    {
        return $this->model->where(function ($d) use ($deptId, $deptName) {
            $d->where('departement_id', $deptId)->where('name', $deptName);
        })->first();
    }
    /**
     * @inheritDoc
     */
    public function all(int $companyId, string $search = null)
    {
        return $this->model
            ->with(['company'])
            ->where('company_id', $companyId)
            ->when($search, fn($query) => $query->where('name', 'LIKE', "%$search%"))
            ->get();
    }


    /**
     * @inheritDoc
     */
    public function companyAll(string $search = null)
    {
        return $this->company
            ->when($search, fn($query) => $query->where('name', 'LIKE', "%$search%"))
            ->get();
    }
    /**
     * @inheritDoc
     */
    public function shift(int $companyId, int $deptId)
    {
        $query = $this->shift
            ->with(
                [
                    'company',
                    'department'
                ]
            )
            ->newQuery();
        if (!empty($companyId) && !empty($deptId)) {
            $query->where([
                'company_id' => $companyId,
                'departemen_id' => $deptId,
            ]);
        }

        return $query->get();
    }
}
