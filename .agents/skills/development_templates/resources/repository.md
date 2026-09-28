# Repository Layer Template

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class ModelRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Model::with(['relationship'])
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): Model
    {
        return Model::create($data);
    }

    public function find(int $id): ?Model
    {
        return Model::find($id);
    }

    public function update(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }
}
```