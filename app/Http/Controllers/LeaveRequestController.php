<?php

namespace App\Http\Controllers;

use App\Models\LeaveCategory;
use App\Models\LeaveQuota;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $status = $request->query('status');

        $query = LeaveRequest::with(['category', 'approver', 'approver1', 'approver2', 'approverHrd'])
            ->where('user_id', $user->id);

        if ($status && in_array($status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $status);
        }

        $requests = $query->latest()->paginate(10)->withQueryString();

        $currentYear = date('Y');
        $quota = LeaveQuota::where('user_id', $user->id)->where('year', $currentYear)->first();

        return Inertia::render('LeaveRequests/Index', [
            'requests' => $requests,
            'filters' => [
                'status' => $status,
            ],
            'quota' => $quota,
        ]);
    }

    public function create()
    {
        $user = Auth::user()->load(['department', 'manager', 'approver1', 'approver2']);
        $categories = LeaveCategory::all();

        $currentYear = date('Y');
        $quota = LeaveQuota::firstOrCreate(
            ['user_id' => $user->id, 'year' => $currentYear],
            ['total_quota' => 12, 'used_quota' => 0, 'remaining_quota' => 12]
        );

        // Build Approval Chain Steps based on user configuration
        $approvalChain = [];
        if ($user->approver1) {
            $approvalChain[] = [
                'level' => 1,
                'role_title' => 'Approval 1 (Supervisor / Atasan 1)',
                'name' => $user->approver1->name,
                'department' => $user->approver1->department?->name ?? 'Direct',
            ];
        }
        if ($user->approver2) {
            $approvalChain[] = [
                'level' => 2,
                'role_title' => 'Approval 2 (Manager / Atasan 2)',
                'name' => $user->approver2->name,
                'department' => $user->approver2->department?->name ?? 'Departemen',
            ];
        } elseif (!$user->approver1 && $user->manager) {
            $approvalChain[] = [
                'level' => 2,
                'role_title' => 'Approval 2 (Manager Atasan)',
                'name' => $user->manager->name,
                'department' => $user->manager->department?->name ?? 'Departemen',
            ];
        }
        // Final tier is always HRD
        $approvalChain[] = [
            'level' => 3,
            'role_title' => 'Approval HRD / PGA Admin',
            'name' => 'HRD / PGA Admin',
            'department' => 'Human Resources & PGA',
        ];

        return Inertia::render('LeaveRequests/Create', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'nik' => $user->nik ?? 'EMP-' . str_pad($user->id, 3, '0', STR_PAD_LEFT),
                'department_name' => $user->department ? $user->department->name : 'General',
                'approval_chain' => $approvalChain,
            ],
            'categories' => $categories,
            'quota' => $quota,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user()->load(['approver1', 'approver2', 'manager']);

        $validated = $request->validate([
            'submission_type' => 'required|in:PEMBERITAHUAN,PERMOHONAN',
            'approval_agreed' => 'required|in:Ya,1,true',
            'leave_category_id' => 'required|exists:leave_categories,id',
            'unit' => 'required|in:hari,jam',
            'amount' => 'required|numeric|min:0.5',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|min:3|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,png,jpg,jpeg|max:10240', // Max 10MB
        ], [
            'submission_type.required' => 'Silakan pilih jenis pengajuan (PEMBERITAHUAN / PERMOHONAN).',
            'approval_agreed.required' => 'Anda harus menyetujui persetujuan kepala departemen untuk melanjutkan.',
            'approval_agreed.in' => 'Anda harus menyetujui persetujuan kepala departemen untuk melanjutkan.',
            'leave_category_id.required' => 'Silakan pilih jenis permohonan / tidak bekerja.',
            'start_date.required' => 'Tanggal permohonan wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal akhir harus sama atau setelah tanggal mulai.',
            'reason.required' => 'Detail alasan cuti / ketidakhadiran wajib diisi.',
            'reason.min' => 'Detail alasan cuti / ketidakhadiran minimal 3 karakter.',
            'attachment.max' => 'Ukuran file lampiran tidak boleh melebihi 10 MB.',
            'attachment.mimes' => 'Format lampiran harus berupa PDF, PNG, JPG, atau JPEG.',
        ]);

        $category = LeaveCategory::findOrFail($validated['leave_category_id']);

        // Check attachment requirement
        if ($category->requires_attachment && !$request->hasFile('attachment')) {
            return back()->withErrors(['attachment' => 'Kategori ' . $category->name . ' wajib melampirkan file dokumen pendukung (Surat Dokter/dll).']);
        }

        // Quota check for Cuti Tahunan
        $currentYear = date('Y');
        $quota = LeaveQuota::firstOrCreate(
            ['user_id' => $user->id, 'year' => $currentYear],
            ['total_quota' => 12, 'used_quota' => 0, 'remaining_quota' => 12]
        );

        if (strtolower($category->name) === 'cuti tahunan' && $validated['unit'] === 'hari') {
            if ($quota->remaining_quota < $validated['amount']) {
                return back()->withErrors(['amount' => 'Sisa kuota cuti tahunan Anda (' . $quota->remaining_quota . ' hari) tidak mencukupi untuk pengajuan ' . $validated['amount'] . ' hari.']);
            }
        }

        // Upload attachment (With Automatic WebP Conversion for Images)
        $attachmentPath = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();

            $mime = $file->getMimeType();
            if (str_contains($mime, 'image')) {
                $attachmentPath = $this->convertToWebpAndStore($file, 'attachments');
            } else {
                $attachmentPath = $file->store('attachments', 'public');
            }
        }

        // Determine Initial Approval Stage:
        // Case 1: Has Approver 1 -> starts at 'approval_1'
        // Case 2: Has Approver 2 (or manager) -> starts at 'approval_2'
        // Case 3: Has neither -> starts at 'hrd'
        if ($user->approver_1_id) {
            $initialStage = 'approval_1';
        } elseif ($user->approver_2_id || $user->manager_id) {
            $initialStage = 'approval_2';
        } else {
            $initialStage = 'hrd';
        }

        // Generate Request Number
        $latestCount = LeaveRequest::whereYear('created_at', date('Y'))->whereMonth('created_at', date('m'))->count() + 1;
        $requestNumber = ($validated['submission_type'] === 'PEMBERITAHUAN' ? 'NOTIF-' : 'CUTI-') . date('Ym') . '-' . str_pad($latestCount, 4, '0', STR_PAD_LEFT);

        LeaveRequest::create([
            'request_number' => $requestNumber,
            'submission_type' => $validated['submission_type'],
            'approval_agreed' => true,
            'user_id' => $user->id,
            'leave_category_id' => $validated['leave_category_id'],
            'unit' => $validated['unit'],
            'amount' => $validated['amount'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'],
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'status' => 'pending',
            'current_stage' => $initialStage,
        ]);

        return redirect()->route('leave-requests.index')->with('success', 'Pengajuan cuti berhasil dikirim! Status saat ini: Menunggu Persetujuan (' . strtoupper(str_replace('_', ' ', $initialStage)) . ').');
    }

    public function show($id)
    {
        $user = Auth::user();
        $requestItem = LeaveRequest::with(['user.department', 'category', 'approver', 'approver1', 'approver2', 'approverHrd'])
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('approved_by_1', $user->id)
                    ->orWhere('approved_by_2', $user->id)
                    ->orWhere('approved_by_hrd', $user->id)
                    ->orWhere('approved_by', $user->id)
                    ->orWhereRaw('? = "admin"', [$user->role])
                    ->orWhereRaw('? = "manager"', [$user->role]);
            })
            ->findOrFail($id);

        return response()->json($requestItem);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $leaveRequest = LeaveRequest::where('user_id', $user->id)->where('status', 'pending')->findOrFail($id);

        $leaveRequest->delete();

        return redirect()->route('leave-requests.index')->with('success', 'Pengajuan cuti berhasil dibatalkan dan dihapus.');
    }

    private function convertToWebpAndStore($file, string $folder = 'attachments', int $quality = 80): string
    {
        $mime = $file->getMimeType();
        $realPath = $file->getRealPath();

        $image = match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($realPath),
            'image/png' => @imagecreatefrompng($realPath),
            'image/webp' => @imagecreatefromwebp($realPath),
            'image/gif' => @imagecreatefromgif($realPath),
            default => null,
        };

        $filename = $folder . '/' . uniqid('attach_') . '_' . time() . '.webp';

        if ($image && function_exists('imagewebp')) {
            imagealphablending($image, true);
            imagesavealpha($image, true);

            ob_start();
            imagewebp($image, null, $quality);
            $webpContent = ob_get_clean();
            imagedestroy($image);

            \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $webpContent);
            return $filename;
        }

        return $file->store($folder, 'public');
    }
}
