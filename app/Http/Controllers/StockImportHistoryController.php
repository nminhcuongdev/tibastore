<?php

namespace App\Http\Controllers;

use App\Models\StockImportHistory;
use App\Services\OrderInventoryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockImportHistoryController extends Controller
{
    public function __construct(private OrderInventoryService $inventory)
    {
    }

    public function index(Request $request): View
    {
        // Truoc day dua vao View composer; nay moi controller tu goi mot lan.
        $this->inventory->syncDueOrders();

        $query = trim((string) $request->query('q', ''));

        $histories = StockImportHistory::query()
            ->with(['product', 'user'])
            ->join('products', 'products.id', '=', 'stock_import_histories.product_id')
            ->select('stock_import_histories.*')
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($search) use ($query) {
                    $search->where('products.code', 'like', "%{$query}%")
                        ->orWhere('products.name', 'like', "%{$query}%")
                        ->orWhere('products.size', 'like', "%{$query}%");
                });
            })
            ->latest('stock_import_histories.created_at')
            ->paginate(30)
            ->withQueryString();

        return view('stock_import_histories.index', [
            'histories' => $histories,
            'query' => $query,
        ]);
    }
}
