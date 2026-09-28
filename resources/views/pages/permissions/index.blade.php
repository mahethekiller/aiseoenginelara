@extends('layouts.app')

@section('title', 'Role Permissions')
@section('page_title', 'Role Permissions')
@section('page_badge', 'Spatie RBAC Matrix')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="badge badge-primary badge-outline badge-sm font-mono">Access Control</span>
                <span class="text-xs text-base-content/60">Dynamic Spatie Permission Matrix</span>
            </div>
            <h1 class="text-xl font-black text-base-content mt-1">Roles & System Permissions</h1>
        </div>
    </div>

    <!-- Permission Matrix Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($roles as $role)
        <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-base-300">
                    <div class="flex items-center gap-2">
                        <i data-lucide="shield" class="w-4 h-4 text-primary"></i>
                        <h3 class="font-bold text-sm text-base-content uppercase">{{ $role->name }}</h3>
                    </div>
                    <span class="badge badge-sm badge-neutral font-mono text-[10px]">{{ $role->permissions->count() }} Perms</span>
                </div>

                <form id="role-perm-form-{{ $role->id }}" class="space-y-2">
                    @csrf
                    <input type="hidden" name="role_id" value="{{ $role->id }}" />
                    
                    <div class="space-y-1.5 max-h-64 overflow-y-auto pr-1">
                        @foreach($permissions as $perm)
                        <label class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-base-200/50 cursor-pointer text-xs">
                            <input type="checkbox" name="permissions[]" value="{{ $perm->name }}"
                                   {{ $role->hasPermissionTo($perm->name) ? 'checked' : '' }}
                                   class="checkbox checkbox-primary checkbox-xs" />
                            <span class="font-mono text-base-content/80 text-[11px]">{{ $perm->name }}</span>
                        </label>
                        @endforeach
                    </div>
                </form>
            </div>

            <div class="pt-4 border-t border-base-300 mt-4 flex justify-end">
                <button type="button" onclick="syncRolePermissions({{ $role->id }}, this)" class="btn btn-primary btn-xs font-bold shadow-xs">
                    Save {{ ucfirst($role->name) }} Permissions
                </button>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
    function syncRolePermissions(roleId, btn) {
        $(btn).attr('disabled', 'disabled').addClass('opacity-75');
        const orig = $(btn).html();
        $(btn).html('<span class="loading loading-spinner loading-xs me-1"></span> Saving...');

        $.ajax({
            url: "{{ route('permissions.sync') }}",
            type: 'POST',
            data: $('#role-perm-form-' + roleId).serialize(),
            success: function(res) {
                $(btn).removeAttr('disabled').removeClass('opacity-75').html(orig);
                showToast(res.message || 'Permissions updated successfully!', 'success');
            },
            error: function(xhr) {
                $(btn).removeAttr('disabled').removeClass('opacity-75').html(orig);
                showToast(xhr.responseJSON?.message || 'Failed to update permissions.', 'error');
            }
        });
    }
</script>
@endpush
