<?php

namespace App\Models\Concerns;

use App\Models\ChangeLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * Ghi lại mọi thay đổi của bản ghi: đổi từ giá trị nào sang giá trị nào.
 *
 * Model dùng trait này cần khai báo:
 * - changeLogLabels(): tên cột -> nhãn tiếng Việt
 * - changeLogSubject(): mô tả ngắn của bản ghi (mã, tên...)
 * và có thể ghi đè changeLogIgnored() / formatChangeLogValue().
 */
trait LogsChanges
{
    public static function bootLogsChanges(): void
    {
        static::created(function ($model) {
            $model->writeChangeLog('created', $model->changeLogCreatedEntries());
        });

        static::updated(function ($model) {
            $entries = $model->changeLogUpdatedEntries();

            // Chỉ đụng vào cột bị bỏ qua (vd updated_at) thì không ghi log rỗng.
            if ($entries !== []) {
                $model->writeChangeLog('updated', $entries);
            }
        });

        static::deleted(function ($model) {
            $model->writeChangeLog('deleted', []);
        });
    }

    public function changeLogs(): MorphMany
    {
        return $this->morphMany(ChangeLog::class, 'loggable')->latest('id');
    }

    /**
     * Cột không cần theo dõi: dấu thời gian và các cột hệ thống tự set.
     */
    public function changeLogIgnored(): array
    {
        return ['id', 'created_at', 'updated_at'];
    }

    public function changeLogLabels(): array
    {
        return [];
    }

    public function changeLogSubject(): string
    {
        return class_basename($this) . ' #' . $this->getKey();
    }

    /**
     * Đưa giá trị thô về dạng người đọc được (nhãn trạng thái, ngày, tiền...).
     */
    public function formatChangeLogValue(string $field, $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'Có' : 'Không';
        }

        if ($value instanceof Carbon) {
            return $value->format('d/m/Y H:i');
        }

        return (string) $value;
    }

    private function changeLogCreatedEntries(): array
    {
        $entries = [];
        $labels = $this->changeLogLabels();

        foreach ($this->getAttributes() as $field => $value) {
            if (in_array($field, $this->changeLogIgnored(), true)) {
                continue;
            }

            $new = $this->formatChangeLogValue($field, $this->getAttribute($field));

            // Tạo mới thì chỉ liệt kê những gì thực sự có giá trị.
            if ($new === null) {
                continue;
            }

            $entries[] = [
                'field' => $field,
                'label' => $labels[$field] ?? $field,
                'old' => null,
                'new' => $new,
            ];
        }

        return $entries;
    }

    private function changeLogUpdatedEntries(): array
    {
        $entries = [];
        $labels = $this->changeLogLabels();

        foreach (array_keys($this->getChanges()) as $field) {
            if (in_array($field, $this->changeLogIgnored(), true)) {
                continue;
            }

            $old = $this->formatChangeLogValue($field, $this->getOriginalCasted($field));
            $new = $this->formatChangeLogValue($field, $this->getAttribute($field));

            // Sau khi định dạng mà giống nhau thì coi như không đổi (vd 0 và "0").
            if ($old === $new) {
                continue;
            }

            $entries[] = [
                'field' => $field,
                'label' => $labels[$field] ?? $field,
                'old' => $old,
                'new' => $new,
            ];
        }

        return $entries;
    }

    /**
     * Giá trị cũ đã áp cast (ngày ra Carbon, số ra int) để định dạng giống giá trị mới.
     */
    private function getOriginalCasted(string $field)
    {
        try {
            return $this->getOriginal($field);
        } catch (\Throwable $e) {
            return $this->getRawOriginal($field);
        }
    }

    public function writeChangeLog(string $event, array $entries): void
    {
        $user = auth()->user();

        ChangeLog::create([
            'loggable_type' => static::class,
            'loggable_id' => $this->getKey(),
            'event' => $event,
            'subject_label' => $this->changeLogSubject(),
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'change_set' => $entries,
        ]);
    }
}
