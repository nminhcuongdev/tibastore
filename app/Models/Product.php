<?php

namespace App\Models;

use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;
    use LogsChanges;

    protected $fillable = [
        'code',
        'name',
        'image_path',
        'stock_quantity',
        'expected_receive_date',
        'expected_receive_quantity',
        'expected_received_at',
        'fabric',
        'category',
        'size',
        'import_price',
        'rental_price',
    ];

    protected $casts = [
        'stock_quantity' => 'integer',
        'expected_receive_date' => 'date',
        'expected_receive_quantity' => 'integer',
        'expected_received_at' => 'datetime',
        'import_price' => 'decimal:2',
        'rental_price' => 'integer',
    ];

    public function changeLogLabels(): array
    {
        return [
            'code' => 'Mã hàng',
            'name' => 'Tên sản phẩm',
            'size' => 'Size',
            'fabric' => 'Vải',
            'category' => 'Danh mục',
            'stock_quantity' => 'Số lượng tồn',
            'import_price' => 'Giá nhập',
            'rental_price' => 'Giá thuê',
            'image_path' => 'Ảnh sản phẩm',
            'expected_receive_date' => 'Ngày nhập dự kiến',
            'expected_receive_quantity' => 'SL nhập dự kiến',
            'expected_received_at' => 'Thời điểm đã nhận',
        ];
    }

    public function changeLogSubject(): string
    {
        return $this->code . ' - size ' . $this->size;
    }

    public function formatChangeLogValue(string $field, $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($field === 'image_path') {
            // Đường dẫn file dài và vô nghĩa với người đọc, chỉ cần biết có đổi ảnh.
            return 'đã có ảnh';
        }

        if (in_array($field, ['import_price', 'rental_price'], true)) {
            return number_format((int) $value);
        }

        if ($field === 'expected_receive_date') {
            return $value instanceof \Illuminate\Support\Carbon
                ? $value->format('d/m/Y')
                : \Illuminate\Support\Carbon::parse($value)->format('d/m/Y');
        }

        if ($value instanceof \Illuminate\Support\Carbon) {
            return $value->format('d/m/Y H:i');
        }

        if (is_bool($value)) {
            return $value ? 'Có' : 'Không';
        }

        return (string) $value;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function stockImportHistories(): HasMany
    {
        return $this->hasMany(StockImportHistory::class);
    }

    public function expectedReceipts(): HasMany
    {
        return $this->hasMany(ProductExpectedReceipt::class);
    }
}
