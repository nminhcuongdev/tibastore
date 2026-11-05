<?php

namespace App\Http\Middleware;

use App\Services\PersonalEditService;
use Closure;
use Illuminate\Http\Request;

class RecordPersonalEdit
{
    public function handle(Request $request, Closure $next)
    {
        $name = $request->route()?->getName() ?? '';
        [$section, $operation] = array_pad(explode('.', $name, 2), 2, '');
        if (!$request->user() || !in_array($section, ['products', 'orders', 'draft-invoices'], true)
            || !in_array($operation, ['store', 'update', 'destroy', 'status', 'payment-status', 'discontinued', 'inline-stock', 'note', 'code-dispatch'], true)) {
            return $next($request);
        }
        return app(PersonalEditService::class)->record($request->user()->id, $section, fn () => $next($request));
    }
}
