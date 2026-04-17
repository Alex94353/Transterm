<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::query()
            ->with([
                'actor:id,name,username,email',
                'targetUser:id,name,username,email',
            ]);

        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . trim((string) $request->input('action')) . '%');
        }

        if ($request->filled('actor_id')) {
            $query->where('actor_id', $request->integer('actor_id'));
        }

        if ($request->filled('target_user_id')) {
            $query->where('target_user_id', $request->integer('target_user_id'));
        }

        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', trim((string) $request->input('auditable_type')));
        }

        if ($request->filled('auditable_id')) {
            $query->where('auditable_id', $request->integer('auditable_id'));
        }

        $order = strtolower((string) $request->input('id_order', 'desc'));
        $order = in_array($order, ['asc', 'desc'], true) ? $order : 'desc';

        $logs = $query
            ->orderBy('id', $order)
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        return response()->json($logs);
    }
}
