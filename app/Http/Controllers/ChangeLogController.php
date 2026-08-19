<?php

namespace App\Http\Controllers;

use App\Models\ChangeLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChangeLogController extends Controller
{
    public function index(Request $request): View
    {
        $types = ChangeLog::types();

        $type = (string) $request->query('type', '');
        $event = (string) $request->query('event', '');
        $query = trim((string) $request->query('q', ''));
        $actor = trim((string) $request->query('actor', ''));
        $from = $this->filterDate($request->query('from'));
        $to = $this->filterDate($request->query('to'));

        if (! array_key_exists($type, $types)) {
            $type = '';
        }

        if (! array_key_exists($event, ChangeLog::EVENTS)) {
            $event = '';
        }

        $logs = ChangeLog::query()
            ->with('user')
            ->when($type !== '', fn ($builder) => $builder->where('loggable_type', $type))
            ->when($event !== '', fn ($builder) => $builder->where('event', $event))
            ->when($actor !== '', fn ($builder) => $builder->where('user_name', 'like', "%{$actor}%"))
            ->when($query !== '', fn ($builder) => $builder->where('subject_label', 'like', "%{$query}%"))
            ->when($from, fn ($builder) => $builder->whereDate('created_at', '>=', $from))
            ->when($to, fn ($builder) => $builder->whereDate('created_at', '<=', $to))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $actors = ChangeLog::query()
            ->whereNotNull('user_name')
            ->where('user_name', '!=', '')
            ->distinct()
            ->orderBy('user_name')
            ->pluck('user_name');

        return view('change_logs.index', [
            'logs' => $logs,
            'types' => $types,
            'events' => ChangeLog::EVENTS,
            'filters' => [
                'type' => $type,
                'event' => $event,
                'q' => $query,
                'actor' => $actor,
                'from' => $from,
                'to' => $to,
            ],
            'actors' => $actors,
        ]);
    }

    private function filterDate($value): ?string
    {
        $value = trim((string) $value);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }
}
