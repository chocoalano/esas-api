<?php
namespace App\Repositories\Services\CoreApp;

use App\Models\CoreApp\TimeWork;
use App\Repositories\Interfaces\CoreApp\TimeWorkInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TimeWorkService implements TimeWorkInterface
{
    protected $model;

    public function __construct(TimeWork $model)
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
        return $this->model->create([
            'company_id' => $data['company_id'],
            'departemen_id' => $data['departemen_id'],
            'name' => $data['name'],
            'in' => $data['in'],
            'out' => $data['out'],
        ]);
    }

    /**
     * @inheritDoc
     */
    public function delete(int $id): bool
    {
        $timeWork = $this->model->find($id);
        if ($timeWork) {
            return $timeWork->delete();
        }
        throw new ModelNotFoundException("TimeWork with ID {$id} not found.");
    }

    /**
     * @inheritDoc
     */
    public function find(int $id)
    {
        return $this->model->findOrFail($id);
    }

    /**
     * @inheritDoc
     */
    public function paginate(int $page, int $limit, string $search = '')
    {
        $query = $this->model->newQuery();
        if (!empty($search)) {
            $query->where('name', 'LIKE', '%' . $search . '%');
        }

        return $query->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * @inheritDoc
     */
    public function update(int $id, array $data)
    {
        $timeWork = $this->model->findOrFail($id);
        $timeWork->update([
            'company_id' => $data['company_id'],
            'departemen_id' => $data['departemen_id'],
            'name' => $data['name'],
            'in' => $data['in'],
            'out' => $data['out'],
        ]);

        return $timeWork;
    }
    /**
     * @inheritDoc
     */
    public function findbyName(string $name, int $dept_id)
    {
        return $this->model->where(function ($query) use ($name, $dept_id) {
            $query
                ->where('name', 'like', '%' . $name . '%')
                ->where('departemen_id', $dept_id);
        })
            ->firstOrFail();
    }
    /**
     * @inheritDoc
     */
    public function import(array $data) {
        dd($data);
    }
}
