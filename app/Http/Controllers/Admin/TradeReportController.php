<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TradeReport;
use App\Support\TradeSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class TradeReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(TradeSchema::ready() && Schema::hasTable('seo_trade_reports'), 404);

        $status = trim((string) $request->query('status', ''));

        $reports = TradeReport::query()
            ->with([
                'reporter:id,username,email,profile_id',
                'reportedUser:id,username,email,profile_id',
                'listing:id,public_id',
            ])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return response()->json([
            'reports' => $reports->map(fn (TradeReport $report): array => $this->serialize($report))->all(),
        ]);
    }

    public function update(Request $request, int $report): JsonResponse
    {
        abort_unless(TradeSchema::ready() && Schema::hasTable('seo_trade_reports'), 404);

        $row = TradeReport::query()->findOrFail($report);
        $data = $request->validate([
            'status' => ['required', Rule::in(['open', 'reviewing', 'resolved', 'dismissed'])],
            'resolution_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $row->status = $data['status'];
        if (array_key_exists('resolution_note', $data)) {
            $row->resolution_note = $data['resolution_note'];
        }
        $row->reviewed_at = now();
        $row->save();

        return response()->json([
            'report' => $this->serialize(
                $row->load([
                    'reporter:id,username,email,profile_id',
                    'reportedUser:id,username,email,profile_id',
                    'listing:id,public_id',
                ])
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(TradeReport $report): array
    {
        return [
            'id' => $report->id,
            'public_id' => $report->public_id,
            'listing_public_id' => $report->listing?->public_id,
            'reporter_username' => $report->reporter?->username,
            'reporter_profile_id' => $report->reporter?->profile_id,
            'reported_username' => $report->reportedUser?->username,
            'reported_profile_id' => $report->reportedUser?->profile_id,
            'reason' => $report->reason,
            'description' => $report->description,
            'status' => $report->status,
            'resolution_note' => $report->resolution_note,
            'reviewed_at' => $report->reviewed_at,
            'created_at' => $report->created_at,
        ];
    }
}
