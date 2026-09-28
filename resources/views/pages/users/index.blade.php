@extends('layouts.app')

@section('title', 'User Management')
@section('page_title', 'User Management')
@section('page_badge', 'RBAC Administration')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="badge badge-primary badge-outline badge-sm font-mono">Administration</span>
                <span class="text-xs text-base-content/60">Manage Platform Team & Access Control</span>
            </div>
            <h1 class="text-xl font-black text-base-content mt-1">Platform Users & Roles</h1>
        </div>
        <button type="button" onclick="openCreateUserModal()" class="btn btn-primary btn-sm gap-2 font-bold shadow-xs">
            <i data-lucide="user-plus" class="w-4 h-4"></i> Add User
        </button>
    </div>

    <!-- Users Table -->
    <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl overflow-hidden">
        <div class="table-responsive overflow-x-auto">
            <table class="table table-hover align-middle mb-0 text-xs border-top w-full">
                <thead class="bg-base-200 text-base-content/70 border-b border-base-300 font-mono uppercase text-[11px] tracking-wider">
                    <tr>
                        <th class="w-20 text-nowrap">Actions</th>
                        <th class="text-nowrap">User</th>
                        <th class="text-nowrap">Email</th>
                        <th class="text-nowrap">Assigned Role</th>
                        <th class="text-nowrap">Active Client</th>
                        <th class="text-nowrap">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-300/50">
                    @foreach($users as $u)
                    <tr class="hover:bg-base-200/40 transition-colors">
                        <td class="whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5">
                                <button type="button" onclick="editUser({{ $u->toJson() }})" class="btn btn-xs btn-square btn-outline btn-warning rounded-lg" title="Edit User">
                                    <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                                </button>
                                @if(auth()->id() !== $u->id)
                                <button type="button" onclick="deleteUser({{ $u->id }})" class="btn btn-xs btn-square btn-outline btn-error rounded-lg" title="Delete User">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                        <td class="font-bold text-base-content whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">
                                    {{ strtoupper(substr($u->name, 0, 1)) }}
                                </div>
                                <span>{{ $u->name }}</span>
                            </div>
                        </td>
                        <td class="whitespace-nowrap font-mono text-base-content/70">{{ $u->email }}</td>
                        <td class="whitespace-nowrap">
                            <span class="badge badge-sm badge-neutral font-mono">{{ $u->getRoleNames()->first() ?? 'viewer' }}</span>
                        </td>
                        <td class="whitespace-nowrap">
                            @if($u->active_client_id && $client = $clients->firstWhere('id', $u->active_client_id))
                                <span class="badge badge-sm badge-ghost">{{ $client->name }}</span>
                            @else
                                <span class="text-base-content/40 italic">Generic Mode</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap font-mono text-base-content/60">{{ $u->created_at->format('M d, Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- User Create / Edit Modal -->
<dialog id="user_modal" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-lg bg-base-100 border border-base-300 text-base-content p-0 shadow-2xl rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-base-300 bg-base-200/50 flex items-center justify-between">
            <h3 id="user-modal-title" class="font-bold text-sm">Add User</h3>
            <form method="dialog"><button class="btn btn-xs btn-circle btn-ghost">✕</button></form>
        </div>

        <form id="user-form" class="p-6 space-y-4">
            @csrf
            <input type="hidden" id="user_id" name="id" value="" />

            <div>
                <label class="label py-0.5 text-xs font-semibold">Full Name <span class="text-error">*</span></label>
                <input type="text" id="u_name" name="name" required placeholder="John Doe" class="input input-bordered input-sm w-full bg-base-200/50 text-xs" />
            </div>

            <div>
                <label class="label py-0.5 text-xs font-semibold">Email Address <span class="text-error">*</span></label>
                <input type="email" id="u_email" name="email" required placeholder="john@example.com" class="input input-bordered input-sm w-full bg-base-200/50 text-xs" />
            </div>

            <div>
                <label class="label py-0.5 text-xs font-semibold">Password <span id="pwd-req" class="text-error">*</span></label>
                <input type="password" id="u_password" name="password" placeholder="••••••••" class="input input-bordered input-sm w-full bg-base-200/50 text-xs" />
                <span class="text-[10px] text-base-content/50" id="pwd-help">Required when creating a new user (min 8 chars).</span>
            </div>

            <div>
                <label class="label py-0.5 text-xs font-semibold">Role <span class="text-error">*</span></label>
                <select id="u_role" name="role" class="select select-bordered select-sm w-full bg-base-200/50 text-xs">
                    <option value="editor">Editor (Can create & generate)</option>
                    <option value="admin">Admin (Full access)</option>
                    <option value="viewer">Viewer (Read-only)</option>
                </select>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-base-300">
                <button type="button" onclick="document.getElementById('user_modal').close()" class="btn btn-ghost btn-sm">Cancel</button>
                <button type="button" onclick="saveUser(this)" class="btn btn-primary btn-sm font-bold shadow-xs">Save User</button>
            </div>
        </form>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
    function openCreateUserModal() {
        $('#user-modal-title').text('Add User');
        $('#user_id').val('');
        $('#user-form')[0].reset();
        $('#u_password').prop('required', true);
        $('#pwd-req').show();
        $('#pwd-help').text('Required for new user (min 8 chars).');
        document.getElementById('user_modal').showModal();
    }

    function editUser(u) {
        $('#user-modal-title').text('Edit User: ' + u.name);
        $('#user_id').val(u.id);
        $('#u_name').val(u.name);
        $('#u_email').val(u.email);
        $('#u_password').val('').prop('required', false);
        $('#pwd-req').hide();
        $('#pwd-help').text('Leave blank to keep current password.');
        $('#u_role').val(u.roles && u.roles.length ? u.roles[0].name : 'editor');
        document.getElementById('user_modal').showModal();
    }

    function saveUser(btn) {
        const id = $('#user_id').val();
        const url = id ? "/users/" + id : "{{ route('users.store') }}";
        const method = id ? 'PUT' : 'POST';

        $(btn).attr('disabled', 'disabled').addClass('opacity-75');
        const orig = $(btn).html();
        $(btn).html('<span class="loading loading-spinner loading-xs me-1"></span> Saving...');

        $.ajax({
            url: url,
            type: method,
            data: $('#user-form').serialize(),
            success: function(res) {
                document.getElementById('user_modal').close();
                showToast(res.message || 'User saved successfully!', 'success');
                setTimeout(() => window.location.reload(), 300);
            },
            error: function(xhr) {
                $(btn).removeAttr('disabled').removeClass('opacity-75').html(orig);
                showToast(xhr.responseJSON?.message || 'Error saving user.', 'error');
            }
        });
    }

    function deleteUser(id) {
        if (!confirm('Are you sure you want to delete this user?')) return;

        $.ajax({
            url: "/users/" + id,
            type: 'DELETE',
            success: function(res) {
                showToast('User deleted successfully.', 'success');
                setTimeout(() => window.location.reload(), 300);
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.message || 'Failed to delete user.', 'error');
            }
        });
    }
</script>
@endpush
