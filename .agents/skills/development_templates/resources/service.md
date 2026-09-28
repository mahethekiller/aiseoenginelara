# Service Layer Template

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ModelRepository;
use App\Models\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;

class ModelService
{
    protected ModelRepository $repository;

    public function __construct(ModelRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function create(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // Business logic validations here
            $record = $this->repository->create($data);
            
            // Dispatch events or notifications
            // event(new ModelCreated($record));
            
            return $record;
        });
    }
}
```