<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\AddressProvinceService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class CarrierDirectoryController extends Controller
{
    public function index(Request $request, AddressProvinceService $provinces)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:100'],
            'carrier' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'in:'.implode(',', array_keys(Order::regions()))],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $rows = Order::query()->where(function ($query) {
            $query->whereNotNull('carrier')->orWhereNotNull('address');
        })->select('carrier', 'address', 'region')
            ->selectRaw('COUNT(*) as order_count, MAX(id) as latest_order_id')
            ->groupBy('carrier', 'address', 'region')->get()
            ->filter(fn ($row) => filled(trim($row->carrier ?? '')) || filled(trim($row->address ?? '')))
            ->map(function ($row) use ($provinces) {
                return [
                    'carrier' => trim($row->carrier ?? ''), 'address' => trim($row->address ?? ''),
                    'region' => $row->region, 'province' => $provinces->detect($row->address),
                    'order_count' => (int) $row->order_count, 'latest_order_id' => $row->latest_order_id,
                ];
            })->groupBy(fn ($row) => json_encode([
                AddressProvinceService::normalize($row['carrier']),
                AddressProvinceService::normalize($row['address']), $row['region'],
            ]))->map(function ($duplicates) {
                $row = $duplicates->sortByDesc('latest_order_id')->first();
                $row['order_count'] = $duplicates->sum('order_count');
                return $row;
            })->values();

        $provinceOptions = $rows->pluck('province')->unique()->sort()->values();
        $rows = $rows->filter(function ($row) use ($filters) {
            foreach (['province', 'region'] as $field) {
                if (filled($filters[$field] ?? null) && $row[$field] !== $filters[$field]) return false;
            }
            $carrier = AddressProvinceService::normalize($filters['carrier'] ?? '');
            $query = AddressProvinceService::normalize($filters['q'] ?? '');
            return ($carrier === '' || str_contains(AddressProvinceService::normalize($row['carrier']), $carrier))
                && ($query === '' || str_contains(AddressProvinceService::normalize(
                    $row['carrier'].' '.$row['address'].' '.$row['province']), $query));
        })->sortBy(fn ($row) => ($row['province'] === 'Chưa xác định' ? 'zzzz' : AddressProvinceService::normalize($row['province']))
            .' '.AddressProvinceService::normalize($row['carrier'].' '.$row['address']))->values();

        $orderCount = $rows->sum('order_count');
        $provinceCount = $rows->pluck('province')->unique()->count();
        $entries = new LengthAwarePaginator($rows->forPage((int) ($filters['page'] ?? 1), 60)->values(),
            $rows->count(), 60, (int) ($filters['page'] ?? 1), ['path' => $request->url(), 'query' => $request->query()]);

        return view('carrier_directory.index', compact('entries', 'provinceOptions', 'filters', 'orderCount', 'provinceCount'));
    }
}
