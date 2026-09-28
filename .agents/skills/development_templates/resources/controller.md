# Controller Template

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreModelRequest;
use App\Http\Requests\UpdateModelRequest;
use App\Services\ModelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ModelController extends Controller
{
    protected ModelService $service;

    public function __construct(ModelService $service)
    {
        $this->service = $service;
        $this->middleware('permission:models.view')->only(['index', 'show']);
        $this->middleware('permission:models.create')->only(['create', 'store']);
        $this->middleware('permission:models.edit')->only(['edit', 'update']);
        $this->middleware('permission:models.delete')->only(['destroy']);
    }

    public function index(): View
    {
        $records = $this->service->getAllPaginated(15);
        return view('modules.models.index', compact('records'));
    }

    public function store(StoreModelRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());
        return redirect()->route('models.index')->with('success', 'Record created successfully.');
    }
}
```