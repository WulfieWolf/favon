<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $entityType = trim($request->string('entity_type')->toString());
        $action = trim($request->string('action')->toString());
        $source = trim($request->string('source')->toString());

        $logs = DB::table('audit_logs as al')
            ->leftJoin('users as actor', 'actor.id', '=', 'al.user_id')
            ->when($entityType !== '', fn ($query) => $query->where('al.entity_type', $entityType))
            ->when($action !== '', fn ($query) => $query->where('al.action', $action))
            ->when($source !== '', fn ($query) => $query->where('al.source', $source))
            ->orderByDesc('al.created_at')
            ->orderByDesc('al.id')
            ->select([
                'al.*',
                'actor.name as actor_name',
                'actor.email as actor_email',
            ])
            ->paginate(50)
            ->withQueryString();

        $entityTypes = DB::table('audit_logs')
            ->select('entity_type')
            ->distinct()
            ->orderBy('entity_type')
            ->pluck('entity_type');

        $actions = DB::table('audit_logs')
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $sources = DB::table('audit_logs')
            ->select('source')
            ->distinct()
            ->orderBy('source')
            ->pluck('source');

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'entityTypes' => $entityTypes,
            'actions' => $actions,
            'sources' => $sources,
            'entityType' => $entityType,
            'action' => $action,
            'source' => $source,
        ]);
    }
}
