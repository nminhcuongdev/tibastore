<?php

namespace App\Http\Controllers;

use App\Services\PersonalEditService;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class PersonalEditController extends Controller
{
    public function replay(Request $request, string $section, string $direction)
    {
        abort_unless(in_array($section, ['products', 'orders', 'draft-invoices'], true)
            && in_array($direction, ['undo', 'redo'], true), 404);
        try {
            $invoiceId = app(PersonalEditService::class)->replay($request->user()->id, $section, $direction === 'redo');
        } catch (QueryException $exception) {
            report($exception);
            throw \Illuminate\Validation\ValidationException::withMessages([
                'history' => 'Dữ liệu đang liên kết với thay đổi khác nên chưa thể hoàn tác hoặc làm lại.']);
        }
        $redirect = $section === 'draft-invoices'
            ? (\App\Models\DraftInvoice::whereKey($invoiceId)->exists()
                ? route('draft-invoices.edit', $invoiceId) : route('draft-invoices.index')) : null;
        return response()->json(['message' => $direction === 'redo' ? 'Đã làm lại.' : 'Đã hoàn tác.', 'redirect' => $redirect]);
    }
}
