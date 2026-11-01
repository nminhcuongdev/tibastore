<template id="workshop-new-row">
<tr data-edit-row data-new-row data-quantity="0" data-total="0">
    <td><input form="workshop-new-__NEW__" name="product_code" list="workshop-product-codes" placeholder="Nhập mã hàng…" maxlength="50" aria-label="Mã hàng" required><div class="muted" data-product-label>Chọn mã để hiện tên và ảnh</div></td>
    <td data-product-image><span class="thumb">CHƯA CÓ ẢNH</span></td>
    <td><input form="workshop-new-__NEW__" name="size_note" maxlength="255" placeholder="Size…" aria-label="Size"></td>
    <td class="num"><input form="workshop-new-__NEW__" name="quantity" type="number" min="1" step="1" value="1" required aria-label="Số lượng"></td>
    <td class="num"><input form="workshop-new-__NEW__" name="unit_price" type="number" min="0" step="1" value="0" aria-label="Đơn giá"></td>
    <td class="num strong" data-row-total>0</td>
    <td><textarea form="workshop-new-__NEW__" name="order_name" maxlength="255" placeholder="Tên đơn…" aria-label="Tên đơn" rows="1" wrap="soft"></textarea></td>
    <td><textarea form="workshop-new-__NEW__" name="buyer_note" maxlength="2000" rows="2" placeholder="Ghi chú…" aria-label="Ghi chú"></textarea></td>
    <td><textarea form="workshop-new-__NEW__" name="source" maxlength="255" placeholder="Nhà cung cấp…" aria-label="Nhà cung cấp" rows="1" wrap="soft"></textarea></td>
    <td><input form="workshop-new-__NEW__" name="ordered_date" type="date" aria-label="Ngày đặt hàng"></td>
    <td><input form="workshop-new-__NEW__" name="returned_date" type="date" aria-label="Ngày trả hàng"></td>
    <td><input form="workshop-new-__NEW__" name="buyer" maxlength="255" placeholder="Người mua…" aria-label="Người mua hàng"></td>
    <td><select form="workshop-new-__NEW__" name="status" class="status-select" aria-label="Trạng thái">@foreach($statuses as $value => $label)<option value="{{ $value }}" @selected($value === \App\Models\WorkshopOrder::DEFAULT_STATUS)>{{ $label }}</option>@endforeach</select></td>
    <td><form id="workshop-new-__NEW__" data-inline-edit method="POST" action="{{ route('workshop-orders.store') }}">@csrf<input type="hidden" name="supply_source" value="{{ $supplySource }}"><button type="submit" class="inline-save">Lưu dòng</button></form><span data-save-state role="status">Dòng mới</span></td>
    <td data-delete-cell><button type="button" class="danger" data-remove-new-row>Xóa dòng</button></td>
</tr>
</template>
