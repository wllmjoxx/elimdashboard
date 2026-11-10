<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Audit log viewer for Superadmin (PRD 4.8, 4.13): who did what to which
 * record, newest first. The log itself is never edited here.
 */
class AuditLogController extends Controller
{
    /**
     * Readable names for the actions modules record. Unknown actions show
     * their key.
     */
    private const LABELS = [
        'prayer.week.viewed' => 'Membuka daftar doa mingguan',
        'prayer.week.exported' => 'Mencetak daftar doa',
        'prayer.status.changed' => 'Mengubah status pokok doa',
        'prayer.deleted' => 'Menghapus pokok doa',
        'members.role.changed' => 'Mengubah role akun',
        'members.invited' => 'Mengundang akun staf',
        'core.church.created' => 'Menambah cabang',
        'core.church.updated' => 'Mengubah cabang',
    ];

    public function index(Request $request): Response
    {
        $action = (string) $request->query('action');
        $timezone = 'Asia/Jakarta';

        $logs = AuditLog::query()
            ->with(['user:id,name,email'])
            ->when($action !== '', fn ($query) => $query->where('action', $action))
            ->latest('id')
            ->paginate(50)
            ->withQueryString()
            ->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'label' => self::LABELS[$log->action] ?? $log->action,
                'user' => $log->user ? ['name' => $log->user->name, 'email' => $log->user->email] : null,
                'subject' => $log->subject_type ? class_basename($log->subject_type).' #'.$log->subject_id : null,
                'metadata' => $log->metadata ? Str::limit((string) json_encode($log->metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 300) : null,
                'ip' => $log->ip,
                'at' => $log->created_at->setTimezone($timezone)->locale('id')->translatedFormat('j M Y, H.i.s'),
            ]);

        $actions = AuditLog::query()->distinct()->orderBy('action')->pluck('action')
            ->map(fn (string $value) => ['value' => $value, 'label' => self::LABELS[$value] ?? $value])
            ->values();

        return Inertia::render('core/audit', [
            'logs' => $logs,
            'actions' => $actions,
            'filters' => ['action' => $action !== '' ? $action : null],
        ]);
    }
}
