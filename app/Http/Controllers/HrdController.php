<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\LeaveCategory;
use App\Models\LeaveQuota;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class HrdController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return redirect()->route('dashboard')->with('error', 'Halaman ini khusus untuk HRD / PGA Admin.');
        }

        $deptId = $request->query('department_id');
        $status = $request->query('status');
        $search = $request->query('search');

        $query = LeaveRequest::with(['user.department', 'category', 'approver']);

        if ($deptId) {
            $query->whereHas('user', function ($q) use ($deptId) {
                $q->where('department_id', $deptId);
            });
        }

        if ($status && in_array($status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%")
                          ->orWhere('nik', 'like', "%{$search}%");
                    });
            });
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $departments = Department::all();
        $categories = LeaveCategory::all();

        return Inertia::render('HRD/Index', [
            'requests' => $requests,
            'departments' => $departments,
            'categories' => $categories,
            'filters' => [
                'department_id' => $deptId,
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    public function employees()
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return redirect()->route('dashboard')->with('error', 'Akses khusus HRD.');
        }

        $employees = User::with(['department', 'manager', 'currentQuota'])
            ->orderBy('name', 'asc')
            ->get();

        $departments = Department::all();
        $managers = User::whereIn('role', ['manager', 'admin'])->get();

        return Inertia::render('HRD/Employees', [
            'employees' => $employees,
            'departments' => $departments,
            'managers' => $managers,
        ]);
    }

    public function updateQuota(Request $request, $userId)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return back()->with('error', 'Akses khusus HRD.');
        }

        $validated = $request->validate([
            'total_quota' => 'required|integer|min:0|max:100',
        ]);

        $currentYear = date('Y');
        $quota = LeaveQuota::firstOrCreate(
            ['user_id' => $userId, 'year' => $currentYear],
            ['total_quota' => 12, 'used_quota' => 0, 'remaining_quota' => 12]
        );

        $newTotal = $validated['total_quota'];
        $newRemaining = max(0, $newTotal - $quota->used_quota);

        $quota->update([
            'total_quota' => $newTotal,
            'remaining_quota' => $newRemaining,
        ]);

        return back()->with('success', 'Kuota cuti berhasil diperbarui.');
    }

    public function export(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isManager()) {
            return back()->with('error', 'Akses khusus HRD/Manager.');
        }

        $requests = LeaveRequest::with(['user.department', 'category', 'approver'])
            ->orderBy('created_at', 'desc')
            ->get();

        $fileName = 'Rekapitulasi_Pengajuan_Cuti_' . date('Ymd_His') . '.csv';

        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0",
        ];

        $callback = function () use ($requests) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'No Request',
                'NIK',
                'Nama Karyawan',
                'Departemen',
                'Kategori',
                'Mulai',
                'Selesai',
                'Jumlah',
                'Satuan',
                'Alasan',
                'Status',
                'Approver',
                'Catatan Approval',
                'Tanggal Approval',
            ]);

            foreach ($requests as $req) {
                fputcsv($file, [
                    $req->request_number,
                    $req->user->nik ?? '-',
                    $req->user->name,
                    $req->user->department->name ?? '-',
                    $req->category->name ?? '-',
                    $req->start_date ? $req->start_date->format('Y-m-d') : '-',
                    $req->end_date ? $req->end_date->format('Y-m-d') : '-',
                    $req->amount,
                    $req->unit,
                    $req->reason,
                    strtoupper($req->status),
                    $req->approver->name ?? '-',
                    $req->approval_note ?? '-',
                    $req->approved_at ? $req->approved_at->format('Y-m-d H:i') : '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
