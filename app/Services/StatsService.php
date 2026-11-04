<?php

namespace App\Services;

use App\Models\Workspace;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StatsService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(Workspace $workspace, CarbonInterface $from, CarbonInterface $to): array
    {
        $lists = $this->completedListsQuery($workspace, $from, $to)
            ->selectRaw('COUNT(*) as lists_count')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_spent')
            ->selectRaw('SUM(CASE WHEN manual_total IS NOT NULL THEN 1 ELSE 0 END) as lists_with_manual_total')
            ->first();

        $items = $this->itemTotals($workspace, $from, $to);

        $listsCount = (int) $lists->lists_count;
        $totalSpent = round((float) $lists->total_spent, 2);

        return [
            'range' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'lists_count' => $listsCount,
            'total_spent' => $totalSpent,
            'breakdown_total' => $items['breakdown_total'],
            'lists_with_manual_total' => (int) $lists->lists_with_manual_total,
            'priced_items_count' => $items['priced_items_count'],
            'unpriced_items_count' => $items['unpriced_items_count'],
            'breakdown_coverage' => $items['breakdown_coverage'],
            'average_per_list' => $listsCount === 0 ? 0.0 : round($totalSpent / $listsCount, 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function monthly(Workspace $workspace, int $year): array
    {
        $from = Carbon::create($year, 1, 1)->startOfDay();
        $to = Carbon::create($year, 12, 31)->endOfDay();

        $lists = $this->completedListsQuery($workspace, $from, $to)
            ->get(['id', 'completed_at', 'total_amount']);

        $breakdownByList = $this->breakdownByList($lists->pluck('id')->all());

        $months = [];

        for ($month = 1; $month <= 12; $month++) {
            $key = sprintf('%04d-%02d', $year, $month);

            $months[$key] = [
                'month' => $key,
                'total_spent' => 0.0,
                'lists_count' => 0,
                'breakdown_total' => 0.0,
            ];
        }

        foreach ($lists as $list) {
            $key = Carbon::parse($list->completed_at)->format('Y-m');

            if (! isset($months[$key])) {
                continue;
            }

            $months[$key]['total_spent'] += (float) $list->total_amount;
            $months[$key]['breakdown_total'] += $breakdownByList[$list->id] ?? 0.0;
            $months[$key]['lists_count']++;
        }

        foreach ($months as &$bucket) {
            $bucket['total_spent'] = round($bucket['total_spent'], 2);
            $bucket['breakdown_total'] = round($bucket['breakdown_total'], 2);
        }

        unset($bucket);

        return [
            'year' => $year,
            'months' => array_values($months),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function categories(Workspace $workspace, CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = $this->completedItemsQuery($workspace, $from, $to)
            ->whereNotNull('shopping_list_items.unit_price')
            ->selectRaw('shopping_list_items.category_name as category_name')
            ->selectRaw('COALESCE(SUM(shopping_list_items.unit_price * shopping_list_items.quantity), 0) as amount')
            ->selectRaw('COUNT(*) as items_count')
            ->selectRaw('COUNT(DISTINCT shopping_lists.id) as lists_count')
            ->groupBy('shopping_list_items.category_name')
            ->orderByDesc('amount')
            ->get();

        $categories = $rows->map(fn ($row): array => [
            'category_name' => $row->category_name ?? 'Sin categoría',
            'amount' => round((float) $row->amount, 2),
            'items_count' => (int) $row->items_count,
            'lists_count' => (int) $row->lists_count,
        ])->all();

        return [
            'categories' => $categories,
            ...$this->itemTotals($workspace, $from, $to),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function topProducts(Workspace $workspace, CarbonInterface $from, CarbonInterface $to, int $limit): array
    {
        $identityRows = $this->completedItemsQuery($workspace, $from, $to)
            ->selectRaw('shopping_list_items.product_id as product_id')
            ->selectRaw('shopping_list_items.product_name as product_name')
            ->selectRaw('COUNT(DISTINCT shopping_lists.id) as times_purchased')
            ->selectRaw('MIN(shopping_lists.completed_at) as first_purchased_at')
            ->selectRaw('MAX(shopping_lists.completed_at) as last_purchased_at')
            ->groupBy('shopping_list_items.product_id', 'shopping_list_items.product_name')
            ->get();

        $quantityRows = $this->completedItemsQuery($workspace, $from, $to)
            ->selectRaw('shopping_list_items.product_id as product_id')
            ->selectRaw('shopping_list_items.product_name as product_name')
            ->selectRaw("COALESCE(shopping_list_items.unit, 'unit') as unit")
            ->selectRaw('COALESCE(SUM(shopping_list_items.quantity), 0) as quantity')
            ->groupBy(
                'shopping_list_items.product_id',
                'shopping_list_items.product_name',
                'shopping_list_items.unit',
            )
            ->get();

        $products = [];

        foreach ($identityRows as $row) {
            $key = $this->productKey($row->product_id, $row->product_name);

            if (! isset($products[$key])) {
                $products[$key] = [
                    'product_id' => $row->product_id === null ? null : (int) $row->product_id,
                    'product_name' => $row->product_name,
                    'times_purchased' => 0,
                    'quantities' => [],
                    'first_purchased_at' => $row->first_purchased_at,
                    'last_purchased_at' => $row->last_purchased_at,
                ];
            }

            $isMostRecent = $row->last_purchased_at >= $products[$key]['last_purchased_at'];

            $products[$key]['times_purchased'] += (int) $row->times_purchased;
            $products[$key]['first_purchased_at'] = min(
                $products[$key]['first_purchased_at'],
                $row->first_purchased_at,
            );
            $products[$key]['last_purchased_at'] = max(
                $products[$key]['last_purchased_at'],
                $row->last_purchased_at,
            );

            if ($isMostRecent) {
                $products[$key]['product_name'] = $row->product_name;
            }
        }

        foreach ($quantityRows as $row) {
            $key = $this->productKey($row->product_id, $row->product_name);

            if (! isset($products[$key])) {
                continue;
            }

            $unit = $row->unit ?: 'unit';
            $current = $products[$key]['quantities'][$unit] ?? 0.0;

            $products[$key]['quantities'][$unit] = round($current + (float) $row->quantity, 3);
        }

        $products = array_values($products);

        usort($products, function (array $first, array $second): int {
            return $second['times_purchased'] <=> $first['times_purchased']
                ?: strcasecmp($first['product_name'], $second['product_name']);
        });

        $products = array_slice($products, 0, $limit);

        $result = array_map(function (array $product): array {
            $times = $product['times_purchased'];
            $first = Carbon::parse($product['first_purchased_at']);
            $last = Carbon::parse($product['last_purchased_at']);

            return [
                'product_id' => $product['product_id'],
                'product_name' => $product['product_name'],
                'times_purchased' => $times,
                'quantities' => $product['quantities'],
                'last_purchased_at' => $last->toDateString(),
                'average_days_between' => $times > 1
                    ? round($first->diffInDays($last) / ($times - 1), 1)
                    : null,
            ];
        }, $products);

        return [
            'products' => $result,
            'limit' => $limit,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function itemTotals(Workspace $workspace, CarbonInterface $from, CarbonInterface $to): array
    {
        $row = $this->completedItemsQuery($workspace, $from, $to)
            ->selectRaw('COUNT(*) as items_count')
            ->selectRaw('SUM(CASE WHEN shopping_list_items.unit_price IS NOT NULL THEN 1 ELSE 0 END) as priced_items_count')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN shopping_list_items.unit_price IS NOT NULL THEN shopping_list_items.unit_price * shopping_list_items.quantity ELSE 0 END), 0) as breakdown_total',
            )
            ->first();

        $itemsCount = (int) $row->items_count;
        $pricedItemsCount = (int) $row->priced_items_count;

        return [
            'breakdown_total' => round((float) $row->breakdown_total, 2),
            'priced_items_count' => $pricedItemsCount,
            'unpriced_items_count' => $itemsCount - $pricedItemsCount,
            'breakdown_coverage' => $itemsCount === 0
                ? 0.0
                : round($pricedItemsCount / $itemsCount, 4),
        ];
    }

    /**
     * @param  array<int, int>  $listIds
     * @return array<int, float>
     */
    private function breakdownByList(array $listIds): array
    {
        if ($listIds === []) {
            return [];
        }

        return DB::table('shopping_list_items')
            ->whereIn('shopping_list_id', $listIds)
            ->whereNotNull('unit_price')
            ->selectRaw('shopping_list_id, COALESCE(SUM(unit_price * quantity), 0) as breakdown_total')
            ->groupBy('shopping_list_id')
            ->pluck('breakdown_total', 'shopping_list_id')
            ->map(fn ($value): float => (float) $value)
            ->all();
    }

    private function completedListsQuery(
        Workspace $workspace,
        CarbonInterface $from,
        CarbonInterface $to,
    ): HasMany {
        $start = Carbon::parse($from->toDateString())->startOfDay();
        $end = Carbon::parse($to->toDateString())->endOfDay();

        return $workspace->shoppingLists()
            ->where('status', 'completed')
            ->where('completed_at', '>=', $start)
            ->where('completed_at', '<=', $end);
    }

    private function completedItemsQuery(
        Workspace $workspace,
        CarbonInterface $from,
        CarbonInterface $to,
    ): QueryBuilder {
        $start = Carbon::parse($from->toDateString())->startOfDay();
        $end = Carbon::parse($to->toDateString())->endOfDay();

        return DB::table('shopping_list_items')
            ->join('shopping_lists', 'shopping_lists.id', '=', 'shopping_list_items.shopping_list_id')
            ->where('shopping_lists.workspace_id', $workspace->id)
            ->where('shopping_lists.status', 'completed')
            ->where('shopping_lists.completed_at', '>=', $start)
            ->where('shopping_lists.completed_at', '<=', $end);
    }

    private function productKey(mixed $productId, string $productName): string
    {
        if ($productId !== null) {
            return 'id:'.$productId;
        }

        return 'name:'.mb_strtolower(trim($productName));
    }
}
