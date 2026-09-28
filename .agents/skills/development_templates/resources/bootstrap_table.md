# Bootstrap Table Template

```html
<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $row)
                <tr>
                    <td>{{ $row->id }}</td>
                    <td>{{ $row->name }}</td>
                    <td>
                        <span class="badge bg-{{ $row->status === 'active' ? 'success' : 'danger' }}">
                            {{ ucfirst($row->status) }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('models.edit', $row->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center py-4 text-muted">No records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
```