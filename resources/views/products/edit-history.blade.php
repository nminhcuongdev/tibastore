<section id="edit-history" style="margin-top:28px">
    <h2>Lịch sử chỉnh sửa</h2>
    <p>Các thay đổi của mọi size thuộc mã {{ $product->code }}, gồm cả những lần sửa trước đây.</p>
    <div class="table-shell" style="overflow-x:auto">
        <table>
            <thead><tr><th>Thời gian</th><th>Mã · Size</th><th>Người thực hiện</th><th>Hành động</th><th>Nội dung thay đổi</th></tr></thead>
            <tbody>
                @forelse ($productEditHistory as $log)
                    <tr>
                        <td style="white-space:nowrap">{{ $log->created_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s') }}</td>
                        <td>{{ $log->subject_label }}</td>
                        <td>{{ $log->actorName() }}</td>
                        <td>{{ $log->eventLabel() }}</td>
                        <td style="min-width:260px;overflow-wrap:anywhere">
                            @forelse ($log->change_set ?? [] as $change)
                                <div style="margin:6px 0">
                                    <strong>{{ $change['label'] ?? $change['field'] }}:</strong>
                                    <span style="background:#fdecec;padding:3px 6px;border-radius:4px">{{ $change['old'] ?? '(trống)' }}</span>
                                    → <span style="background:#e8f7ef;padding:3px 6px;border-radius:4px">{{ $change['new'] ?? '(trống)' }}</span>
                                </div>
                            @empty
                                {{ $log->event === 'deleted' ? 'Đã xóa size này.' : 'Đã ghi nhận thay đổi.' }}
                            @endforelse
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">Chưa có lịch sử chỉnh sửa cho mã này.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $productEditHistory->fragment('edit-history')->links() }}
</section>
