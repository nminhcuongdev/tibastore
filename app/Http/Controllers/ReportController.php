<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Các mốc ngày có thể dùng để xếp đơn vào kỳ báo cáo.
     * Mặc định "ngày tạo đơn" vì đó là lúc doanh thu được chốt trên hệ thống.
     */
    private const DATE_BASES = [
        'created_at' => 'Ngày tạo đơn',
        'pickup_date' => 'Ngày lấy',
        'event_date' => 'Ngày diễn',
        'return_date' => 'Ngày trả',
    ];

    private const MODES = [
        'range' => 'Cả khoảng ngày',
        'day' => 'Từng ngày',
        'week' => 'Từng tuần trong tháng',
    ];

    public function revenue(Request $request): View
    {
        $mode = (string) $request->query('mode', 'range');

        if (! array_key_exists($mode, self::MODES)) {
            $mode = 'range';
        }

        $basis = (string) $request->query('basis', 'created_at');

        if (! array_key_exists($basis, self::DATE_BASES)) {
            $basis = 'created_at';
        }

        // Chế độ "tuần" lấy khoảng ngày từ tháng được chọn, các chế độ khác lấy from/to.
        $month = $this->parseMonth($request->query('month'));

        if ($mode === 'week') {
            $dateFrom = $month->copy()->startOfMonth();
            $dateTo = $month->copy()->endOfMonth();
        } else {
            [$dateFrom, $dateTo] = $this->parseRange($request);
        }

        $orders = Order::query()
            ->whereDate($basis, '>=', $dateFrom->toDateString())
            ->whereDate($basis, '<=', $dateTo->toDateString())
            ->get(['id', 'source', 'total_amount', 'compensation_amount', $basis]);

        $sources = Order::sources();
        $buckets = $this->buckets($mode, $dateFrom, $dateTo);

        return view('reports.revenue', [
            'mode' => $mode,
            'modes' => self::MODES,
            'basis' => $basis,
            'dateBases' => self::DATE_BASES,
            'month' => $month,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'sources' => $sources,
            'summary' => $this->summaryBySource($orders, $sources),
            'rows' => $this->rowsByBucket($orders, $buckets, $sources, $basis),
            'grandTotal' => $this->totals($orders),
        ]);
    }

    /**
     * Doanh thu gộp cả kỳ, tách theo từng nguồn hàng.
     */
    private function summaryBySource(Collection $orders, array $sources): array
    {
        $summary = [];

        foreach ($sources as $key => $label) {
            $ofSource = $orders->where('source', $key);
            $summary[$key] = ['label' => $label] + $this->totals($ofSource);
        }

        // Đơn có nguồn lạ (dữ liệu cũ) vẫn phải hiện ra, không được nuốt mất doanh thu.
        $unknown = $orders->filter(fn ($order) => ! array_key_exists($order->source, $sources));

        if ($unknown->isNotEmpty()) {
            $summary['__unknown'] = ['label' => 'Nguồn khác'] + $this->totals($unknown);
        }

        return $summary;
    }

    /**
     * Mỗi kỳ con (ngày hoặc tuần) một dòng, trong đó tách doanh thu theo nguồn.
     */
    private function rowsByBucket(Collection $orders, array $buckets, array $sources, string $basis): array
    {
        $rows = [];

        foreach ($buckets as $bucket) {
            $inBucket = $orders->filter(function ($order) use ($bucket, $basis) {
                $date = $this->dateOf($order, $basis);

                return $date !== null
                    && $date->gte($bucket['from'])
                    && $date->lte($bucket['to']);
            });

            $bySource = [];

            foreach (array_keys($sources) as $key) {
                $bySource[$key] = $this->totals($inBucket->where('source', $key));
            }

            $rows[] = [
                'label' => $bucket['label'],
                'sub' => $bucket['sub'],
                'by_source' => $bySource,
                'total' => $this->totals($inBucket),
            ];
        }

        return $rows;
    }

    /**
     * Doanh thu = tiền hàng (giá thuê x số lượng của mọi dòng).
     * Tiền bồi thường để riêng một cột tham khảo, KHÔNG cộng vào doanh thu.
     */
    private function totals(Collection $orders): array
    {
        return [
            'orders' => $orders->count(),
            'compensation' => (int) $orders->sum('compensation_amount'),
            'total' => (int) $orders->sum('total_amount'),
        ];
    }

    /**
     * Danh sách kỳ con để dựng các dòng của bảng chi tiết.
     */
    private function buckets(string $mode, Carbon $dateFrom, Carbon $dateTo): array
    {
        if ($mode === 'range') {
            return [];
        }

        if ($mode === 'week') {
            return $this->weekBuckets($dateFrom, $dateTo);
        }

        $buckets = [];

        for ($date = $dateFrom->copy(); $date->lte($dateTo); $date->addDay()) {
            $buckets[] = [
                'label' => $date->format('d/m/Y'),
                'sub' => $this->weekdayLabel($date),
                'from' => $date->copy()->startOfDay(),
                'to' => $date->copy()->startOfDay(),
            ];
        }

        return $buckets;
    }

    /**
     * Tuần trong tháng: cắt theo tuần lịch (thứ 2 → chủ nhật) và kẹp trong tháng,
     * nên tuần đầu và tuần cuối có thể ngắn hơn 7 ngày.
     */
    private function weekBuckets(Carbon $dateFrom, Carbon $dateTo): array
    {
        $buckets = [];
        $cursor = $dateFrom->copy()->startOfDay();
        $index = 1;

        while ($cursor->lte($dateTo)) {
            $weekEnd = $cursor->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay();

            if ($weekEnd->gt($dateTo)) {
                $weekEnd = $dateTo->copy()->startOfDay();
            }

            $buckets[] = [
                'label' => 'Tuần ' . $index,
                'sub' => $cursor->format('d/m') . ' - ' . $weekEnd->format('d/m'),
                'from' => $cursor->copy(),
                'to' => $weekEnd->copy(),
            ];

            $cursor = $weekEnd->copy()->addDay();
            $index++;
        }

        return $buckets;
    }

    private function dateOf($order, string $basis): ?Carbon
    {
        $value = $order->{$basis};

        if ($value === null) {
            return null;
        }

        return $value instanceof Carbon
            ? $value->copy()->startOfDay()
            : Carbon::parse($value)->startOfDay();
    }

    private function weekdayLabel(Carbon $date): string
    {
        return ['CN', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'][$date->dayOfWeek];
    }

    private function parseMonth($value): Carbon
    {
        $value = trim((string) $value);

        if (preg_match('/^\d{4}-\d{2}$/', $value)) {
            return Carbon::createFromFormat('Y-m-d', $value . '-01')->startOfDay();
        }

        return now()->startOfMonth();
    }

    /**
     * Mặc định 30 ngày gần nhất; giới hạn 366 ngày để bảng không phình quá lớn.
     */
    private function parseRange(Request $request): array
    {
        $from = $this->parseDate($request->query('from')) ?? now()->startOfDay()->subDays(29);
        $to = $this->parseDate($request->query('to')) ?? now()->startOfDay();

        if ($to->lt($from)) {
            $to = $from->copy();
        }

        if ($from->diffInDays($to) > 365) {
            $to = $from->copy()->addDays(365);
        }

        return [$from, $to];
    }

    private function parseDate($value): ?Carbon
    {
        $value = trim((string) $value);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)
            ? Carbon::parse($value)->startOfDay()
            : null;
    }
}
