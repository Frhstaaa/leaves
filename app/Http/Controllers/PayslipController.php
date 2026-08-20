<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Payslip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use ZipArchive;

class PayslipController extends Controller
{
    /**
     * Display payslips for the logged-in employee.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $selectedYear = (int) $request->query('year', date('Y'));

        $payslips = Payslip::where('user_id', $user->id)
            ->where('year', $selectedYear)
            ->where('status', 'published')
            ->orderBy('month', 'desc')
            ->get();

        // Get available years for dropdown
        $availableYears = Payslip::where('user_id', $user->id)
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        if (empty($availableYears)) {
            $availableYears = [(int) date('Y')];
        }

        $stats = [
            'total_this_year' => $payslips->count(),
            'unviewed_count' => $payslips->whereNull('viewed_at')->count(),
            'latest_month' => $payslips->first()?->month_name ?? '-',
        ];

        return Inertia::render('Payslips/Index', [
            'payslips' => $payslips,
            'selectedYear' => $selectedYear,
            'availableYears' => $availableYears,
            'stats' => $stats,
        ]);
    }

    /**
     * Preview PDF in browser / iframe.
     */
    public function preview($id)
    {
        $payslip = Payslip::with('user')->findOrFail($id);
        $user = Auth::user();

        // Authorization: only owner or admin
        if ($payslip->user_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'Anda tidak memiliki akses ke slip gaji ini.');
        }

        // Mark as viewed if accessed by owner
        if ($payslip->user_id === $user->id && is_null($payslip->viewed_at)) {
            $payslip->update(['viewed_at' => now()]);
        }

        $filePath = storage_path('app/public/' . $payslip->file_path);

        if (!file_exists($filePath)) {
            // Check in public_path as fallback
            $publicFallback = public_path('storage/' . $payslip->file_path);
            if (file_exists($publicFallback)) {
                $filePath = $publicFallback;
            } else {
                abort(404, 'File slip gaji tidak ditemukan di server.');
            }
        }

        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $payslip->original_filename . '"',
        ]);
    }

    /**
     * Download PDF file securely.
     */
    public function download($id)
    {
        $payslip = Payslip::with('user')->findOrFail($id);
        $user = Auth::user();

        // Authorization: only owner or admin
        if ($payslip->user_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'Anda tidak memiliki akses ke slip gaji ini.');
        }

        // Mark as viewed if accessed by owner
        if ($payslip->user_id === $user->id && is_null($payslip->viewed_at)) {
            $payslip->update(['viewed_at' => now()]);
        }

        $filePath = storage_path('app/public/' . $payslip->file_path);

        if (!file_exists($filePath)) {
            $publicFallback = public_path('storage/' . $payslip->file_path);
            if (file_exists($publicFallback)) {
                $filePath = $publicFallback;
            } else {
                abort(404, 'File slip gaji tidak ditemukan di server.');
            }
        }

        return response()->download($filePath, $payslip->original_filename);
    }

    /**
     * HRD Payslip Management Dashboard.
     */
    public function manage(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return redirect()->route('dashboard')->with('error', 'Akses khusus HRD.');
        }

        $month = (int) $request->query('month', date('n'));
        $year = (int) $request->query('year', date('Y'));
        $deptId = $request->query('department_id');
        $search = $request->query('search');

        $query = Payslip::with(['user.department', 'uploader'])
            ->where('month', $month)
            ->where('year', $year);

        if ($deptId) {
            $query->whereHas('user', function ($q) use ($deptId) {
                $q->where('department_id', $deptId);
            });
        }

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $payslips = $query->orderBy('created_at', 'desc')->get();

        $allEmployees = User::with('department')
            ->orderBy('name', 'asc')
            ->get();

        $departments = Department::orderBy('name', 'asc')->get();

        $totalEmployees = $allEmployees->count();
        $distributedCount = $payslips->count();
        $viewedCount = $payslips->whereNotNull('viewed_at')->count();

        $stats = [
            'total_employees' => $totalEmployees,
            'distributed_count' => $distributedCount,
            'viewed_count' => $viewedCount,
            'pending_count' => max(0, $totalEmployees - $distributedCount),
            'viewed_percentage' => $distributedCount > 0 ? round(($viewedCount / $distributedCount) * 100) : 0,
        ];

        return Inertia::render('HRD/Payslips', [
            'payslips' => $payslips,
            'employees' => $allEmployees,
            'departments' => $departments,
            'stats' => $stats,
            'filters' => [
                'month' => $month,
                'year' => $year,
                'department_id' => $deptId,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Bulk Upload & Auto-Distribution of Payslips.
     */
    public function bulkUpload(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return back()->with('error', 'Akses khusus HRD.');
        }

        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|between:2020,2050',
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:pdf|max:10240', // Max 10MB per PDF
            'zip_file' => 'nullable|file|mimes:zip|max:51200', // Max 50MB ZIP
        ]);

        $month = (int) $request->input('month');
        $year = (int) $request->input('year');
        $monthName = $this->getMonthName($month);
        $periodLabel = "{$monthName} {$year}";

        $allUsers = User::all();
        $matched = [];
        $unmatched = [];

        // Ensure storage directory exists
        $targetDir = "payslips/{$year}/" . str_pad($month, 2, '0', STR_PAD_LEFT);
        Storage::disk('public')->makeDirectory($targetDir);

        // 1. Process Direct PDF Files
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $origName = $file->getClientOriginalName();
                $matchedUser = $this->matchEmployee($origName, $allUsers);

                if ($matchedUser) {
                    $savedPath = $this->storePayslipFile($file, $matchedUser, $month, $year, $targetDir);

                    // Create or update payslip record
                    $payslip = Payslip::updateOrCreate(
                        [
                            'user_id' => $matchedUser->id,
                            'month' => $month,
                            'year' => $year,
                        ],
                        [
                            'period_label' => $periodLabel,
                            'file_path' => $savedPath,
                            'original_filename' => $origName,
                            'file_size' => $file->getSize(),
                            'status' => 'published',
                            'uploaded_by' => $user->id,
                            'viewed_at' => null, // Reset viewed on new update
                        ]
                    );

                    $matched[] = [
                        'filename' => $origName,
                        'employee_name' => $matchedUser->name,
                        'nik' => $matchedUser->nik,
                    ];
                } else {
                    $unmatched[] = $origName;
                }
            }
        }

        // 2. Process ZIP File
        if ($request->hasFile('zip_file')) {
            $zip = new ZipArchive();
            $zipFile = $request->file('zip_file');

            if ($zip->open($zipFile->getRealPath()) === true) {
                $tempExtractPath = storage_path('app/temp_zip_' . time() . '_' . Str::random(6));
                File::makeDirectory($tempExtractPath, 0755, true);
                $zip->extractTo($tempExtractPath);
                $zip->close();

                // Scan all PDF files in extracted folder
                $extractedFiles = File::allFiles($tempExtractPath);

                foreach ($extractedFiles as $extractedFile) {
                    if (strtolower($extractedFile->getExtension()) !== 'pdf') {
                        continue;
                    }

                    $origName = $extractedFile->getFilename();
                    $matchedUser = $this->matchEmployee($origName, $allUsers);

                    if ($matchedUser) {
                        $storedFileName = 'payslip_' . $matchedUser->nik . '_' . $year . '_' . $month . '_' . time() . '.pdf';
                        $relativeStoredPath = "{$targetDir}/{$storedFileName}";
                        $fullStoredPath = storage_path('app/public/' . $relativeStoredPath);

                        File::copy($extractedFile->getRealPath(), $fullStoredPath);

                        Payslip::updateOrCreate(
                            [
                                'user_id' => $matchedUser->id,
                                'month' => $month,
                                'year' => $year,
                            ],
                            [
                                'period_label' => $periodLabel,
                                'file_path' => $relativeStoredPath,
                                'original_filename' => $origName,
                                'file_size' => $extractedFile->getSize(),
                                'status' => 'published',
                                'uploaded_by' => $user->id,
                                'viewed_at' => null,
                            ]
                        );

                        $matched[] = [
                            'filename' => $origName,
                            'employee_name' => $matchedUser->name,
                            'nik' => $matchedUser->nik,
                        ];
                    } else {
                        $unmatched[] = $origName;
                    }
                }

                // Cleanup temp folder
                File::deleteDirectory($tempExtractPath);
            }
        }

        $matchedCount = count($matched);
        $unmatchedCount = count($unmatched);

        if ($matchedCount > 0 && $unmatchedCount === 0) {
            return back()->with('success', "Berhasil! {$matchedCount} slip gaji periode {$periodLabel} otomatis terdistribusi ke akun karyawan.");
        } elseif ($matchedCount > 0 && $unmatchedCount > 0) {
            $unmatchedList = implode(', ', array_slice($unmatched, 0, 5));
            if ($unmatchedCount > 5) $unmatchedList .= '...';
            return back()->with('success', "{$matchedCount} slip gaji berhasil terdistribusi. Namun ada {$unmatchedCount} file yang tidak cocok dengan NIK/Nama karyawan ({$unmatchedList}).");
        } elseif ($unmatchedCount > 0) {
            return back()->with('error', "Gagal mendistribusikan: {$unmatchedCount} file tidak ditemukan kecocokan dengan NIK atau Nama karyawan di sistem. Pastikan nama file memuat NIK karyawan (contoh: EMP-2026-001.pdf).");
        }

        return back()->with('error', 'Tidak ada file PDF yang valid ditemukan untuk diunggah.');
    }

    /**
     * Single Employee Payslip Upload.
     */
    public function singleUpload(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return back()->with('error', 'Akses khusus HRD.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|between:2020,2050',
            'file' => 'required|file|mimes:pdf|max:10240',
            'notes' => 'nullable|string|max:500',
        ]);

        $employee = User::findOrFail($request->user_id);
        $month = (int) $request->input('month');
        $year = (int) $request->input('year');
        $monthName = $this->getMonthName($month);
        $periodLabel = "{$monthName} {$year}";

        $targetDir = "payslips/{$year}/" . str_pad($month, 2, '0', STR_PAD_LEFT);
        Storage::disk('public')->makeDirectory($targetDir);

        $file = $request->file('file');
        $origName = $file->getClientOriginalName();
        $savedPath = $this->storePayslipFile($file, $employee, $month, $year, $targetDir);

        Payslip::updateOrCreate(
            [
                'user_id' => $employee->id,
                'month' => $month,
                'year' => $year,
            ],
            [
                'period_label' => $periodLabel,
                'file_path' => $savedPath,
                'original_filename' => $origName,
                'file_size' => $file->getSize(),
                'notes' => $request->input('notes'),
                'status' => 'published',
                'uploaded_by' => $user->id,
                'viewed_at' => null,
            ]
        );

        return back()->with('success', "Slip gaji {$employee->name} ({$employee->nik}) periode {$periodLabel} berhasil diunggah.");
    }

    /**
     * Delete a Payslip.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return back()->with('error', 'Akses khusus HRD.');
        }

        $payslip = Payslip::findOrFail($id);

        // Delete physical file
        if ($payslip->file_path && Storage::disk('public')->exists($payslip->file_path)) {
            Storage::disk('public')->delete($payslip->file_path);
        }

        $payslip->delete();

        return back()->with('success', 'Slip gaji berhasil dihapus.');
    }

    /**
     * Helper: Match filename against employees (by NIK, Email, or Name).
     */
    private function matchEmployee(string $filename, $employees): ?User
    {
        $cleanFilename = pathinfo($filename, PATHINFO_FILENAME);
        $normalizedFilename = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $cleanFilename));

        // 1. Match by exact or normalized NIK (Highest Priority)
        foreach ($employees as $emp) {
            if (!empty($emp->nik)) {
                // Exact NIK in filename
                if (stripos($cleanFilename, $emp->nik) !== false) {
                    return $emp;
                }

                // Normalized NIK (without dashes/spaces)
                $normalizedNik = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $emp->nik));
                if (!empty($normalizedNik) && strpos($normalizedFilename, $normalizedNik) !== false) {
                    return $emp;
                }
            }
        }

        // 2. Match by Email prefix
        foreach ($employees as $emp) {
            if (!empty($emp->email)) {
                $emailPrefix = strstr($emp->email, '@', true);
                if ($emailPrefix && strlen($emailPrefix) >= 3) {
                    if (stripos($cleanFilename, $emailPrefix) !== false) {
                        return $emp;
                    }
                }
            }
        }

        // 3. Match by Full Name
        foreach ($employees as $emp) {
            if (!empty($emp->name) && strlen($emp->name) >= 3) {
                $normalizedName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $emp->name));
                if (!empty($normalizedName) && strpos($normalizedFilename, $normalizedName) !== false) {
                    return $emp;
                }

                // Match name parts if multi-word name
                $nameParts = explode(' ', strtolower($emp->name));
                if (count($nameParts) >= 2) {
                    $allPartsFound = true;
                    foreach ($nameParts as $part) {
                        if (strlen($part) >= 3 && strpos(strtolower($cleanFilename), $part) === false) {
                            $allPartsFound = false;
                            break;
                        }
                    }
                    if ($allPartsFound) {
                        return $emp;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Helper: Store payslip file to disk.
     */
    private function storePayslipFile($file, User $employee, int $month, int $year, string $targetDir): string
    {
        $cleanNik = preg_replace('/[^a-zA-Z0-9]/', '_', $employee->nik ?: 'EMP_' . $employee->id);
        $fileName = "payslip_{$cleanNik}_{$year}_" . str_pad($month, 2, '0', STR_PAD_LEFT) . '_' . time() . '.pdf';
        
        return $file->storeAs($targetDir, $fileName, 'public');
    }

    /**
     * Helper: Get month name in Indonesian.
     */
    private function getMonthName(int $month): string
    {
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return $months[$month] ?? "Bulan {$month}";
    }
}
