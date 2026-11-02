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

    public function hasArrived(): bool
    {
        return $this->status === self::STATUS_ARRIVED;
    }
}
