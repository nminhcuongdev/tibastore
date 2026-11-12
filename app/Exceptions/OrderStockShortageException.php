<?php

namespace App\Exceptions;

use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class OrderStockShortageException extends ValidationException
{
    public $productId;

    public function __construct(Product $product, int $quantity)
    {
        $this->productId = $product->id;
        $validator = Validator::make([], []);
        $missing = $quantity - $product->stock_quantity;
        $validator->errors()->add('items', "Mã {$product->code} - size {$product->size}: cần {$quantity}, kho thực tế còn {$product->stock_quantity}, thiếu {$missing}. Sửa size/số lượng trong đơn theo hàng sẽ giao; nếu hàng đã có thực tế, cập nhật Kho rồi đổi trạng thái lại.");
        parent::__construct($validator);
    }
}
