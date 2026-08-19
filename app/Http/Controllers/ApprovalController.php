<?php

namespace App\Http\Controllers;

use App\Models\LeaveQuota;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->isManager() && !$user->isAdmin()) {
            return redirect()->route('dashboard')->with('error', 'Akses hanya untuk Atasan / Manager & HRD.');
        }

        $rawStatus = $request->query('status');

        if ($request->has('status')) {
            $status = ($rawStatus === null || $rawStatus === '' || $rawStatus === 'all') ? 'all' : $rawStatus;
        } else {
            $status = 'pending';
        }

        $query = LeaveRequest::with(['user.department', 'category', 'approver']);

        if ($user->isManager()) {
            // Managers view subordinates or department members
            $subordinateIds = User::where('manager_id', $user->id)
                ->orWhere('department_id', $user->department_id)
                ->pluck('id');

            $query->whereIn('user_id', $subordinateIds)->where('user_id', '!=', $user->id);
        }

        if (in_array($status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $status);
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('Approvals/Index', [
            'requests' => $requests,
            'filters' => [
                'status' => $status,
            ],
        ]);
    }

    public function approve(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->isManager() && !$user->isAdmin()) {
            return back()->with('error', 'Anda tidak memiliki hak akses persetujuan.');
        }

        $leaveRequest = LeaveRequest::with(['category', 'user'])->findOrFail($id);

        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Pengajuan ini sudah tidak lagi berstatus pending.');
        }

        $note = $request->input('note');

        // Approve transaction
        $leaveRequest->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approval_note' => $note,
            'approved_at' => now(),
        ]);

        // Deduct quota if Cuti Tahunan and unit = hari
        if (strtolower($leaveRequest->category->name) === 'cuti tahunan' && $leaveRequest->unit === 'hari') {
            $currentYear = date('Y');
            $quota = LeaveQuota::firstOrCreate(
                ['user_id' => $leaveRequest->user_id, 'year' => $currentYear],
                ['total_quota' => 12, 'used_quota' => 0, 'remaining_quota' => 12]
            );

            $newUsed = $quota->used_quota + (int) $leaveRequest->amount;
            $newRemaining = max(0, $quota->total_quota - $newUsed);

            $quota->update([
                'used_quota' => $newUsed,
                'remaining_quota' => $newRemaining,
            ]);
        }

        return back()->with('success', 'Pengajuan cuti ' . $leaveRequest->request_number . ' milik ' . $leaveRequest->user->name . ' berhasil disetujui (Approved)!');
    }

    public function reject(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->isManager() && !$user->isAdmin()) {
            return back()->with('error', 'Anda tidak memiliki hak akses persetujuan.');
        }

        $request->validate([
            'note' => 'required|string|min:3|max:500',
        ], [
            'note.required' => 'Alasan penolakan pengajuan wajib diisi.',
        ]);

        $leaveRequest = LeaveRequest::with('user')->findOrFail($id);

        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Pengajuan ini sudah tidak lagi berstatus pending.');
        }

        $leaveRequest->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'approval_note' => $request->input('note'),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Pengajuan cuti ' . $leaveRequest->request_number . ' telah ditolak (Rejected). Catatan telah disimpan.');
    }
}
