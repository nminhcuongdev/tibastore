<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkshopOrder extends Model
{
    use HasFactory;

    public const NO_PRODUCT_CODE = 'Chưa tạo mã';

    public const STATUS_NOT_YET = 'chua_ve';

    public const STATUS_ARRIVED = 'da_ve';

    public const STATUS_ORDERED = 'da_od';

    public const DEFAULT_STATUS = self::STATUS_NOT_YET;

    public const SUPPLY_WORKSHOP = 'xuong_may';

    public const SUPPLY_OUTSIDE = 'mua_ngoai';

    public const DEFAULT_SUPPLY_SOURCE = self::SUPPLY_WORKSHOP;

    protected $fillable = [
        'product_code',
        'customer_order_id',
        'purchase_plan',
        'size_quantities',
        'stocked_at',
        'image_path',
        'supply_source',
        'size_note',
        'quantity',
        'unit_price',
        'order_name',
        'buyer_note',
        'source',
        'ordered_date',
        'returned_date',
        'buyer',
        'status',
    ];

    protected $casts = [
        'customer_order_id' => 'integer',
        'purchase_plan' => 'array',
        'size_quantities' => 'array',
        'stocked_at' => 'datetime',
        'ordered_date' => 'date',
        'returned_date' => 'date',
        'quantity' => 'integer',
        'unit_price' => 'integer',
    ];

    /**
     * Thành tiền của dòng = đơn giá × số lượng. Tính lúc đọc thay vì lưu sẵn
     * để không bao giờ lệch với hai cột gốc.
     */
    public function getTotalAmountAttribute(): int
    {
        return (int) $this->quantity * (int) $this->unit_price;
    }

    public function getImageUrlAttribute(): ?string
    {
        $path = $this->image_path ?: $this->product?->image_path;

        return $path ? asset('storage/'.$path) : null;
    }

    /**
     * Nguồn hàng: hàng tự đặt xưởng may, hay mua sẵn từ bên ngoài.
     */
    public static function supplySources(): array
    {
        return [
            self::SUPPLY_WORKSHOP => 'Xưởng',
            self::SUPPLY_OUTSIDE => 'Mua ngoài',
        ];
    }

    public function supplySourceLabel(): string
    {
        return self::supplySources()[$this->supply_source] ?? $this->supply_source;
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_NOT_YET => 'Chưa od',
            self::STATUS_ORDERED => 'Đã od',
            self::STATUS_ARRIVED => 'Hàng đã về',
        ];
    }

    /**
     * Mã hàng ở kho được chia theo cặp mã + size, còn bảng này đặt theo cả mã
     * (một dòng gồm nhiều size), nên nối bằng code thay vì id của một size.
     * Dùng để lấy tên và ảnh hiển thị.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_code', 'code');
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }

    public function plannedSizeLines(): array
    {
        $lines = $this->size_quantities ?? $this->purchase_plan['lines'] ?? [];
        if ($lines || !filled($this->size_note)) return $lines;

        $sizes = Product::where('code', $this->product_code)->get(['id', 'size'])
            ->groupBy(fn ($product) => mb_strtolower(trim($product->size)));
        $quantities = [];
        foreach (preg_split('/[,;\r\n]+/u', trim($this->size_note)) as $entry) {
            if (!preg_match('/^\s*([^:]+):\s*([1-9][0-9]{0,8})\s*$/u', $entry, $match)) return [];
            $products = $sizes->get(mb_strtolower(trim($match[1])));
            if (!$products || $products->count() !== 1) return [];
            $id = $products->first()->id;
            $quantities[$id] = ($quantities[$id] ?? 0) + (int) $match[2];
        }
        if (array_sum($quantities) !== (int) $this->quantity) return [];

        $lines = [];
        foreach ($quantities as $id => $quantity) {
            $lines[] = ['product_id' => $id, 'quantity' => $quantity];
        }
        return $lines;
    }

    public function hasArrived(): bool
    {
        return $this->status === self::STATUS_ARRIVED;
    }

    public function getCustomerOrderUrlAttribute(): ?string
    {
        if ($this->customer_order_id) return route('orders.show', $this->customer_order_id);
        return trim((string) $this->order_name) !== '' ? route('orders.index', ['order_name' => $this->order_name]) : null;
    }
}
