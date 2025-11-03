<?php

namespace App\Http\Controllers;

use App\Models\TimeOffRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class TimeOffRequestController extends Controller
{
    /**
     * Return all entries, flattened.
     * GET /api/time-off-requests
     */
    public function index()
{
    $items = TimeOffRequest::query()
        ->orderByDesc('date_submitted')
        ->get()
        ->map(fn ($r) => $this->flattenOne($r));

    return response()->json($items);
}

    /**
     * Create a new entry (from webhook "Submit").
     * POST /api/time-off-requests
     */
    public function store(Request $request)
    {
        $attrs = $this->extractAttributes($request);

        if (empty($attrs['full_id'])) {
            return response()->json(['message' => 'full_id (root Id) is required in payload'], 422);
        }

        if (TimeOffRequest::where('full_id', $attrs['full_id'])->exists()) {
            return response()->json(['message' => 'Entry already exists for this full_id'], 409);
        }

        $created = TimeOffRequest::create($attrs);

        return response()->json($this->flattenOne($created), 201);
    }

    /**
     * Update an existing entry (from webhook "Update").
     * PUT /api/time-off-requests
     */
    public function updateFromWebhook(Request $request)
    {
        $attrs = $this->extractAttributes($request);

        if (empty($attrs['full_id'])) {
            return response()->json(['message' => 'full_id (root Id) is required in payload'], 422);
        }

        $model = TimeOffRequest::where('full_id', $attrs['full_id'])->first();

        if (!$model) {
            return response()->json(['message' => 'No entry found for this full_id'], 404);
        }

        $model->update($attrs);

        return response()->json($this->flattenOne($model->refresh()));
    }

    /**
     * Delete an entry by full_id (from webhook "deleted" event).
     * DELETE /api/time-off-requests
     *
     * Expecting JSON body: { "Id": "54-562" } (i.e., root Id).
     */
    public function destroy(Request $request)
    {
        $fullId = data_get($request->all(), 'Id');

        if (empty($fullId)) {
            return response()->json(['message' => 'full_id (root Id) is required in payload'], 422);
        }

        $model = TimeOffRequest::where('full_id', $fullId)->first();

        if (!$model) {
            return response()->json(['message' => 'No entry found for this full_id'], 404);
        }

        $model->delete();

        return response()->json(['message' => 'Deleted', 'full_id' => $fullId]);
    }

    /* ----------------- Helpers ----------------- */

private function flattenOne(TimeOffRequest $r): array
{
    return [
        'full_id'              => $r->full_id,
        'full_name'            => $r->full_name,
        'date_submitted'       => optional($r->date_submitted)->toIso8601String(),
        'time_off_type'        => $r->time_off_type,
        'acceptance_rejection' => $r->acceptance_rejection,
        'dates' => [
            optional($r->day1)?->toDateString(),
            optional($r->day2)?->toDateString(),
            optional($r->day3)?->toDateString(),
            optional($r->day4)?->toDateString(),
            optional($r->day5)?->toDateString(),
            optional($r->day6)?->toDateString(),
            optional($r->day7)?->toDateString(),
        ],
    ];
}

    /**
     * Extracts only the fields you care about from the webhook payloads.
     */
    private function extractAttributes(Request $request): array
{
    $p = $request->all();

    // Name: prefer Name.FirstAndLast, fallback to "First Last"
    $firstAndLast = data_get($p, 'Name.FirstAndLast');
    $fallbackName = trim(
        trim((string) data_get($p, 'Name.First', '')) . ' ' .
        trim((string) data_get($p, 'Name.Last', ''))
    );
    $fullName = $firstAndLast ?: $fallbackName;

    // Pull both groups (ensure arrays)
    $requested = (array) (data_get($p, 'RequestedTimeOff') ?: []);
    $paid      = (array) (data_get($p, 'PaidTimeOff') ?: []);

    // Choose the group by which one has a non-null DateOfFirstDayOff
    $requestedFirst = Arr::get($requested, 'DateOfFirstDayOff');
    $paidFirst      = Arr::get($paid, 'DateOfFirstDayOff');

    if (!empty($requestedFirst)) {
        $group = $requested;
    } elseif (!empty($paidFirst)) {
        $group = $paid;
    } else {
        // Fallback (neither has a first date) – keep empty to produce nulls
        $group = [];
    }

    $dateKeys = [
        'DateOfFirstDayOff',
        'DateOfSecondDayOff',
        'DateOfThirdDayOff',
        'DateOfForthDayOff',
        'DateOfFifthDayOff',
        'DateOfSixthDayOff',
        'DateOfSeventhDayOff',
    ];

    $parsedDates = [];
    foreach ($dateKeys as $idx => $key) {
        $val = Arr::get($group, $key);
        $parsedDates[$idx] = $this->parseDateOrNull($val); // returns Y-m-d or null
    }

    return [
        'full_id'              => data_get($p, 'Id'), // e.g. "54-562"
        'full_name'            => $fullName,
        'date_submitted'       => $this->parseDateTimeOrNull(data_get($p, 'Entry.DateSubmitted')),
        'time_off_type'        => data_get($p, 'WhatTypeOfTimeOffAreYouRequesting'),
        'day1'                 => $parsedDates[0],
        'day2'                 => $parsedDates[1],
        'day3'                 => $parsedDates[2],
        'day4'                 => $parsedDates[3],
        'day5'                 => $parsedDates[4],
        'day6'                 => $parsedDates[5],
        'day7'                 => $parsedDates[6],
        'acceptance_rejection' => data_get($p, 'CorrespondenceInternalUseOnly.AcceptanceRejection'),
    ];
}


    private function parseDateOrNull($value): ?string
    {
        if (!$value) return null;
        try {
            return Carbon::parse($value)->toDateString(); // Y-m-d
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function parseDateTimeOrNull($value): ?string
    {
        if (!$value) return null;
        try {
            return Carbon::parse($value)->toDateTimeString(); // Y-m-d H:i:s
        } catch (\Throwable $e) {
            return null;
        }
    }
}
