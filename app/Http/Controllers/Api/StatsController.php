<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MonthlyStatsRequest;
use App\Http\Requests\StatsRangeRequest;
use App\Services\StatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class StatsController extends Controller
{
    public function __construct(private readonly StatsService $stats) {}

    public function summary(StatsRangeRequest $request): JsonResponse
    {
        [$from, $to] = $this->resolveRange($request);

        return response()->json([
            'status' => true,
            'message' => 'Resumen de gastos obtenido correctamente.',
            'data' => $this->stats->summary(
                $request->user()->currentWorkspace(),
                $from,
                $to,
            ),
        ]);
    }

    public function monthly(MonthlyStatsRequest $request): JsonResponse
    {
        $year = (int) ($request->validated('year') ?? now()->year);

        return response()->json([
            'status' => true,
            'message' => 'Gastos mensuales obtenidos correctamente.',
            'data' => $this->stats->monthly(
                $request->user()->currentWorkspace(),
                $year,
            ),
        ]);
    }

    public function categories(StatsRangeRequest $request): JsonResponse
    {
        [$from, $to] = $this->resolveRange($request);

        return response()->json([
            'status' => true,
            'message' => 'Gastos por categoría obtenidos correctamente.',
            'data' => $this->stats->categories(
                $request->user()->currentWorkspace(),
                $from,
                $to,
            ),
        ]);
    }

    public function products(StatsRangeRequest $request): JsonResponse
    {
        [$from, $to] = $this->resolveRange($request);
        $limit = (int) ($request->validated('limit') ?? 10);

        return response()->json([
            'status' => true,
            'message' => 'Productos más comprados obtenidos correctamente.',
            'data' => $this->stats->topProducts(
                $request->user()->currentWorkspace(),
                $from,
                $to,
                $limit,
            ),
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveRange(StatsRangeRequest $request): array
    {
        $to = $request->filled('to')
            ? Carbon::parse($request->validated('to'))
            : Carbon::today();

        $from = $request->filled('from')
            ? Carbon::parse($request->validated('from'))
            : $to->copy()->startOfYear();

        return [$from, $to];
    }
}
