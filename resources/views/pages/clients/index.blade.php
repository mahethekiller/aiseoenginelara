@extends('layouts.app')

@section('title', 'Agency Clients')
@section('page_title', 'Agency Clients')
@section('page_badge', 'Multi-Client Management')

@section('content')
<div class="space-y-6">
    <!-- Header & Action CTA -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="badge badge-primary badge-outline badge-sm font-mono">Agency Hub</span>
                <span class="text-xs text-base-content/60">Multi-Client Brand Voice & Sitemap Caching</span>
            </div>
            <h1 class="text-xl font-black text-base-content mt-1">Client Profiles & Brand Directives</h1>
        </div>
        <button type="button" onclick="openCreateClientModal()" class="btn btn-primary btn-sm gap-2 font-bold shadow-xs">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Agency Client
        </button>
    </div>

    <!-- Telemetry Metric KPI Cards (Rule 13 Blueprint) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="badge badge-xs bg-primary/10 text-primary border border-primary/20 font-mono font-semibold rounded-full px-2 py-0.5">Portfolio</span>
                    <div class="text-xs text-base-content/60 font-semibold mt-1">Total Clients</div>
                    <div class="text-2xl font-black text-base-content mt-1">{{ $metrics['total_clients'] ?? 0 }}</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                    <i data-lucide="building-2" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-primary"></div>
        </div>

        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="badge badge-xs bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 font-mono font-semibold rounded-full px-2 py-0.5">Live</span>
                    <div class="text-xs text-base-content/60 font-semibold mt-1">Active Profiles</div>
                    <div class="text-2xl font-black text-emerald-500 mt-1">{{ $metrics['active_clients'] ?? 0 }}</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center shrink-0">
                    <i data-lucide="activity" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-emerald-500"></div>
        </div>

        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="badge badge-xs bg-indigo-500/10 text-indigo-500 border border-indigo-500/20 font-mono font-semibold rounded-full px-2 py-0.5">SEO Index</span>
                    <div class="text-xs text-base-content/60 font-semibold mt-1">Sitemaps Cached</div>
                    <div class="text-2xl font-black text-indigo-500 mt-1">{{ $metrics['with_sitemap'] ?? 0 }}</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center shrink-0">
                    <i data-lucide="map" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-indigo-500"></div>
        </div>

        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="badge badge-xs bg-cyan-500/10 text-cyan-500 border border-cyan-500/20 font-mono font-semibold rounded-full px-2 py-0.5">Sync Ready</span>
                    <div class="text-xs text-base-content/60 font-semibold mt-1">WordPress Connected</div>
                    <div class="text-2xl font-black text-cyan-500 mt-1">{{ $metrics['with_wordpress'] ?? 0 }}</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-cyan-500/10 text-cyan-500 flex items-center justify-center shrink-0">
                    <i data-lucide="globe" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-cyan-500"></div>
        </div>
    </div>

    <!-- Client Directory Data Table -->
    <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl overflow-hidden">
        <div class="table-responsive overflow-x-auto">
            <table class="table table-hover align-middle mb-0 text-xs border-top w-full">
                <!-- Standardized compact header (Rule 14) -->
                <thead class="bg-base-200/80 text-base-content/70 border-b border-base-300 font-mono uppercase text-[11px] tracking-wider">
                    <tr>
                        <th class="w-28 text-nowrap py-3 px-3.5">Actions</th>
                        <th class="text-nowrap py-3 px-3.5">Client</th>
                        <th class="text-nowrap py-3 px-3.5">Industry</th>
                        <th class="text-nowrap py-3 px-3.5">Website</th>
                        <th class="text-nowrap py-3 px-3.5">Sitemap Cache</th>
                        <th class="text-nowrap py-3 px-3.5">WordPress</th>
                        <th class="text-nowrap py-3 px-3.5">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-300/50">
                    @forelse($clients as $client)
                    <tr class="hover:bg-base-200/40 transition-colors">
                        <!-- Column 1 Icon-Only Actions (Rule 8 Compliance) -->
                        <td class="whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5">
                                <button type="button" onclick="editClient({{ $client->toJson() }})"
                                        class="btn btn-xs btn-square btn-outline btn-warning rounded-lg" title="Edit Profile">
                                    <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                                </button>
                                <button type="button" onclick="crawlClientSitemap({{ $client->id }}, this)"
                                        class="btn btn-xs btn-square btn-outline btn-info rounded-lg" title="Crawl & Cache Sitemap XML">
                                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                                </button>
                                <button type="button" onclick="deleteClient({{ $client->id }})"
                                        class="btn btn-xs btn-square btn-outline btn-error rounded-lg" title="Delete Client">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </td>
                        <td class="font-bold text-base-content whitespace-nowrap">
                            <div>{{ $client->name }}</div>
                            <div class="text-[10px] text-base-content/50 font-normal truncate max-w-xs">{{ $client->brand_tone ?: 'No tone specified' }}</div>
                        </td>
                        <td class="whitespace-nowrap">
                            <span class="badge badge-sm badge-ghost">{{ $client->industry ?: 'General' }}</span>
                        </td>
                        <td class="whitespace-nowrap">
                            <a href="{{ $client->website_url }}" target="_blank" rel="noopener noreferrer" class="text-primary hover:underline flex items-center gap-1">
                                <span>{{ parse_url($client->website_url, PHP_URL_HOST) ?: $client->website_url }}</span>
                                <i data-lucide="external-link" class="w-3 h-3"></i>
                            </a>
                        </td>
                        <td class="whitespace-nowrap font-mono text-[11px]">
                            @if(!empty($client->sitemap_cache))
                                @php 
                                    $cachedCount = is_array($client->sitemap_cache) 
                                        ? count($client->sitemap_cache) 
                                        : count(json_decode($client->sitemap_cache, true) ?? []); 
                                @endphp
                                <span class="badge badge-sm badge-success badge-outline">{{ $cachedCount }} URLs Cached</span>
                            @else
                                <span class="text-base-content/40">Not crawled</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap">
                            @if(!empty($client->wordpress_url))
                                <span class="badge badge-sm badge-info badge-outline flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span> Ready
                                </span>
                            @else
                                <span class="badge badge-sm badge-ghost text-base-content/40">Not connected</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap">
                            @if($client->is_active)
                                <span class="badge badge-sm badge-success">Active</span>
                            @else
                                <span class="badge badge-sm badge-neutral">Inactive</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-8 text-base-content/50">
                            <i data-lucide="building-2" class="w-8 h-8 mx-auto mb-2 opacity-30"></i>
                            No agency clients configured yet. Click "Add Agency Client" to create your first client profile.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Create / Edit Client Modal (DaisyUI 5 Modal Standard) -->
<!-- ========================================================================= -->
<dialog id="client_modal" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-3xl bg-base-100 border border-base-300 text-base-content p-0 shadow-2xl rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-base-300 bg-base-200/50 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i data-lucide="building-2" class="w-5 h-5 text-primary"></i>
                <h3 id="client-modal-title" class="font-bold text-sm">Add New Agency Client</h3>
            </div>
            <form method="dialog"><button class="btn btn-xs btn-circle btn-ghost">✕</button></form>
        </div>

        <form id="client-form" class="p-6 space-y-4">
            @csrf
            <input type="hidden" id="client_form_id" name="id" value="" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label py-0.5 text-xs font-semibold">Client Name <span class="text-error">*</span></label>
                    <input type="text" id="c_name" name="name" required placeholder="e.g. GEIMS Hospital" class="input input-bordered input-sm w-full bg-base-200/50 text-xs" />
                </div>
                <div>
                    <label class="label py-0.5 text-xs font-semibold">Website URL <span class="text-error">*</span></label>
                    <input type="url" id="c_website_url" name="website_url" required placeholder="https://example.com" class="input input-bordered input-sm w-full bg-base-200/50 text-xs" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label py-0.5 text-xs font-semibold">Industry / Domain <span class="text-error">*</span></label>
                    <input type="text" id="c_industry" name="industry" required placeholder="e.g. Healthcare & Medical" class="input input-bordered input-sm w-full bg-base-200/50 text-xs" />
                </div>
                <div>
                    <label class="label py-0.5 text-xs font-semibold">Brand Tone</label>
                    <input type="text" id="c_brand_tone" name="brand_tone" placeholder="e.g. Authoritative, Empathetic, Clinical Expert" class="input input-bordered input-sm w-full bg-base-200/50 text-xs" />
                </div>
            </div>

            <div>
                <label class="label py-0.5 text-xs font-semibold">Target Audience</label>
                <input type="text" id="c_target_audience" name="target_audience" placeholder="e.g. Patients seeking specialized surgery, hospital attendees" class="input input-bordered input-sm w-full bg-base-200/50 text-xs" />
            </div>

            <div>
                <label class="label py-0.5 text-xs font-semibold">Default Call To Action (CTA)</label>
                <textarea id="c_cta_default" name="cta_default" rows="2" placeholder="e.g. Book a consultation today at GEIMS Hospital or call +91-XXX-XXXX for emergency appointments." class="textarea textarea-bordered textarea-sm w-full bg-base-200/50 text-xs"></textarea>
            </div>

            <!-- Sitemap & WordPress Settings Accordion -->
            <div class="collapse collapse-arrow bg-base-200/50 border border-base-300 rounded-xl">
                <input type="checkbox" />
                <div class="collapse-title text-xs font-bold flex items-center gap-2">
                    <i data-lucide="settings" class="w-3.5 h-3.5 text-primary"></i> Sitemap Crawler & WordPress Publishing Credentials
                </div>
                <div class="collapse-content space-y-3 pt-2">
                    <div>
                        <label class="label py-0.5 text-xs font-semibold">Sitemap URL</label>
                        <input type="url" id="c_sitemap_url" name="sitemap_url" placeholder="https://example.com/sitemap.xml" class="input input-bordered input-sm w-full bg-base-100 text-xs" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="label py-0.5 text-[11px] font-semibold">WordPress REST URL</label>
                            <input type="url" id="c_wordpress_url" name="wordpress_url" placeholder="https://example.com" class="input input-bordered input-sm w-full bg-base-100 text-xs" />
                        </div>
                        <div>
                            <label class="label py-0.5 text-[11px] font-semibold">WP Username</label>
                            <input type="text" id="c_wordpress_username" name="wordpress_username" placeholder="admin" class="input input-bordered input-sm w-full bg-base-100 text-xs" />
                        </div>
                        <div>
                            <label class="label py-0.5 text-[11px] font-semibold">WP Application Password</label>
                            <input type="password" id="c_wordpress_app_password" name="wordpress_app_password" placeholder="•••• •••• •••• ••••" class="input input-bordered input-sm w-full bg-base-100 text-xs" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-base-300">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="c_is_active" name="is_active" value="1" checked class="checkbox checkbox-primary checkbox-xs" />
                    <span class="text-xs font-medium">Active Client Profile</span>
                </label>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="document.getElementById('client_modal').close()" class="btn btn-ghost btn-sm">Cancel</button>
                    <button type="button" onclick="saveClient(this)" class="btn btn-primary btn-sm font-bold shadow-xs">Save Profile</button>
                </div>
            </div>
        </form>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
    function openCreateClientModal() {
        $('#client-modal-title').text('Add New Agency Client');
        $('#client_form_id').val('');
        $('#client-form')[0].reset();
        document.getElementById('client_modal').showModal();
        window.refreshIcons();
    }

    function editClient(client) {
        $('#client-modal-title').text('Edit Client: ' + client.name);
        $('#client_form_id').val(client.id);
        $('#c_name').val(client.name);
        $('#c_website_url').val(client.website_url);
        $('#c_industry').val(client.industry);
        $('#c_brand_tone').val(client.brand_tone);
        $('#c_target_audience').val(client.target_audience);
        $('#c_cta_default').val(client.cta_default);
        $('#c_sitemap_url').val(client.sitemap_url);
        $('#c_wordpress_url').val(client.wordpress_url);
        $('#c_wordpress_username').val(client.wordpress_username);
        $('#c_wordpress_app_password').val('');
        $('#c_is_active').prop('checked', client.is_active == 1);
        document.getElementById('client_modal').showModal();
        window.refreshIcons();
    }

    function saveClient(btn) {
        const id = $('#client_form_id').val();
        const url = id ? "/clients/" + id : "{{ route('clients.store') }}";
        const method = id ? 'PUT' : 'POST';

        $(btn).attr('disabled', 'disabled').addClass('opacity-75');
        const originalHtml = $(btn).html();
        $(btn).html('<span class="loading loading-spinner loading-xs me-1"></span> Saving...');

        $.ajax({
            url: url,
            type: method,
            data: $('#client-form').serialize(),
            success: function(res) {
                document.getElementById('client_modal').close();
                showToast(res.message || 'Client profile saved successfully!', 'success');
                setTimeout(() => window.location.reload(), 300);
            },
            error: function(xhr) {
                $(btn).removeAttr('disabled').removeClass('opacity-75').html(originalHtml);
                showToast(xhr.responseJSON?.message || 'Error saving client profile.', 'error');
            }
        });
    }

    function crawlClientSitemap(clientId, btn) {
        $(btn).attr('disabled', 'disabled');
        const icon = $(btn).find('i');
        icon.addClass('animate-spin');

        $.ajax({
            url: "/clients/" + clientId + "/crawl-sitemap",
            type: 'POST',
            success: function(res) {
                $(btn).removeAttr('disabled');
                icon.removeClass('animate-spin');
                showToast(res.message || 'Sitemap indexed successfully!', 'success');
                setTimeout(() => window.location.reload(), 400);
            },
            error: function(xhr) {
                $(btn).removeAttr('disabled');
                icon.removeClass('animate-spin');
                showToast(xhr.responseJSON?.message || 'Failed to crawl sitemap.', 'error');
            }
        });
    }

    function deleteClient(clientId) {
        if (!confirm('Are you sure you want to delete this agency client?')) return;

        $.ajax({
            url: "/clients/" + clientId,
            type: 'DELETE',
            success: function(res) {
                showToast(res.message || 'Client deleted successfully.', 'success');
                setTimeout(() => window.location.reload(), 300);
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.message || 'Error deleting client.', 'error');
            }
        });
    }
</script>
@endpush
