<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ChangeLog extends Model
{
    use HasFactory;

    public const EVENTS = [
        'created' => 'Tạo mới',
        'updated' => 'Cập nhật',
        'deleted' => 'Xóa',
    ];

    protected $fillable = [
        'loggable_type',
        'loggable_id',
        'event',
        'subject_label',
        'user_id',
        'user_name',
        'change_set',
    ];

    protected $casts = [
        'change_set' => 'array',
    ];

    /**
     * Các loại bản ghi đang được theo dõi, dùng cho bộ lọc.
     */
    public static function types(): array
    {
        return [
            Order::class => 'Đơn hàng',
            Product::class => 'Sản phẩm',
        ];
    }

    public function typeLabel(): string
    {
        return self::types()[$this->loggable_type] ?? class_basename($this->loggable_type);
    }

    public function eventLabel(): string
    {
        return self::EVENTS[$this->event] ?? $this->event;
    }

    /**
     * Người thực hiện: ưu tiên tên đã lưu kèm log để không mất khi user bị xóa.
     */
    public function actorName(): string
    {
        return $this->user_name ?: ($this->user?->name ?? 'Hệ thống');
    }

    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
