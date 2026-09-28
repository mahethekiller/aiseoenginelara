<?php

namespace App\Http\Controllers;

use App\Models\AiUsageLog;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiUsageLogController extends Controller
{
    /**
     * Display dual-currency token usage & cost summary reports (USD $ / INR Rs. ₹)
     */
    public function index(Request $request)
    {
        $clientId = $request->query('client_id');
        $provider = $request->query('provider');

        $query = AiUsageLog::with(['client:id,name', 'user:id,name']);

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        if ($provider) {
            $query->where('provider', $provider);
        }

        $logs = $query->orderBy('id', 'desc')->paginate(30);

        // Aggregate Totals
        $totals = DB::table('ai_usage_logs')
            ->when($clientId, function ($q) use ($clientId) {
                $q->where('client_id', $clientId);
            })
            ->selectRaw('
                SUM(prompt_tokens) as total_prompt_tokens,
                SUM(completion_tokens) as total_completion_tokens,
                SUM(total_tokens) as grand_total_tokens,
                SUM(estimated_cost_usd) as grand_total_cost_usd,
                SUM(estimated_cost_inr) as grand_total_cost_inr
            ')->first();

        $totalsData = [
            'total_prompt_tokens' => (int) ($totals->total_prompt_tokens ?? 0),
            'total_completion_tokens' => (int) ($totals->total_completion_tokens ?? 0),
            'grand_total_tokens' => (int) ($totals->grand_total_tokens ?? 0),
            'grand_total_cost_usd' => round((float) ($totals->grand_total_cost_usd ?? 0), 4),
            'grand_total_cost_inr' => round((float) ($totals->grand_total_cost_inr ?? 0), 2),
            'usd_to_inr_rate' => (float) env('USD_TO_INR_RATE', 84.00),
        ];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'totals' => $totalsData,
                'logs' => $logs,
            ]);
        }

        $clients = Client::orderBy('name')->get();

        return view('pages.reports.token-usage', compact('logs', 'totalsData', 'clients'));
    }
}
