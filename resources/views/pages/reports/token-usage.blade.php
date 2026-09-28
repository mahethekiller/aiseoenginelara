@extends('layouts.app')

@section('title', 'Token Usage & Cost Reports')
@section('page_title', 'Token Usage & Costs')
@section('page_badge', 'Dual-Currency Financial Ledger')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="badge badge-primary badge-outline badge-sm font-mono">Financial Telemetry</span>
                <span class="text-xs text-base-content/60">Live Dual-Currency Accounting (USD $ & INR ₹)</span>
            </div>
            <h1 class="text-xl font-black text-base-content mt-1">AI Token Usage & API Cost Ledger</h1>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5">
        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs">
            <div class="text-xs text-base-content/60 font-semibold">Prompt Tokens</div>
            <div class="text-2xl font-black text-base-content mt-1">{{ number_format($totalsData['total_prompt_tokens'] ?? 0) }}</div>
        </div>
        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs">
            <div class="text-xs text-base-content/60 font-semibold">Completion Tokens</div>
            <div class="text-2xl font-black text-base-content mt-1">{{ number_format($totalsData['total_completion_tokens'] ?? 0) }}</div>
        </div>
        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs">
            <div class="text-xs text-base-content/60 font-semibold">Total Tokens Consumed</div>
            <div class="text-2xl font-black text-indigo-500 mt-1">{{ number_format($totalsData['grand_total_tokens'] ?? 0) }}</div>
        </div>
        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs">
            <div class="text-xs text-base-content/60 font-semibold">Total Cost (USD)</div>
            <div class="text-2xl font-black text-emerald-500 mt-1">${{ number_format($totalsData['grand_total_cost_usd'] ?? 0, 4) }}</div>
        </div>
        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs">
            <div class="text-xs text-base-content/60 font-semibold">Total Cost (INR)</div>
            <div class="text-2xl font-black text-amber-500 mt-1">₹{{ number_format($totalsData['grand_total_cost_inr'] ?? 0, 2) }}</div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl overflow-hidden">
        <div class="p-4 border-b border-base-300 flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider text-base-content/70">Historical Token Usage Logs</h3>
            <span class="text-xs font-mono text-base-content/50">Base Rate: $1.00 USD = ₹{{ $totalsData['usd_to_inr_rate'] ?? 84.0 }} INR</span>
        </div>

        <div class="table-responsive overflow-x-auto">
            <table class="table table-hover align-middle mb-0 text-xs border-top w-full">
                <thead class="bg-base-200 text-base-content/70 border-b border-base-300 font-mono uppercase text-[11px] tracking-wider">
                    <tr>
                        <th class="text-nowrap">ID</th>
                        <th class="text-nowrap">Provider / Model</th>
                        <th class="text-nowrap">Prompt Tokens</th>
                        <th class="text-nowrap">Completion Tokens</th>
                        <th class="text-nowrap">Total Tokens</th>
                        <th class="text-nowrap">Cost ($ USD)</th>
                        <th class="text-nowrap">Cost (₹ INR)</th>
                        <th class="text-nowrap">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-300/50">
                    @forelse($logs as $log)
                    <tr class="hover:bg-base-200/40 transition-colors font-mono">
                        <td>#{{ $log->id }}</td>
                        <td class="font-sans font-bold">
                            <span class="badge badge-sm badge-neutral">{{ $log->provider }}</span>
                            <span class="ml-1 text-base-content/80">{{ $log->model }}</span>
                        </td>
                        <td>{{ number_format($log->prompt_tokens) }}</td>
                        <td>{{ number_format($log->completion_tokens) }}</td>
                        <td class="font-bold text-primary">{{ number_format($log->total_tokens) }}</td>
                        <td class="text-emerald-500 font-bold">${{ number_format($log->estimated_cost_usd, 5) }}</td>
                        <td class="text-amber-500 font-bold">₹{{ number_format($log->estimated_cost_inr, 2) }}</td>
                        <td class="text-base-content/60">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-8 text-base-content/50 font-sans">
                            No token usage recorded yet. Generate articles to populate financial logs.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-base-300">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
