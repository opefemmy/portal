<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Models\DeletedRecord;
use Illuminate\Http\Request;

class DeletedRecordController extends Controller
{
    use EnforcesPermission;

    public function index(Request $request)
    {
        $this->requirePermission('auditor.audit.deleted-records');

        $query = DeletedRecord::with('user');

        if ($request->table_name) {
            $query->where('table_name', $request->table_name);
        }

        if ($request->date) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        $records = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('auditor.deleted-records', compact('records'));
    }

    public function show(DeletedRecord $deletedRecord)
    {
        $this->requirePermission('auditor.audit.view');

        $deletedRecord->load('user');

        return view('auditor.deleted-record-show', compact('deletedRecord'));
    }
}