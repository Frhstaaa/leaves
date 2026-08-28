<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use App\Services\MediaOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Tampilkan halaman Form Data Diri Karyawan.
     */
    public function biodata(Request $request): Response
    {
        $user = $request->user()->load('department');
        $departments = Department::select('id', 'name', 'code')->orderBy('name')->get();

        return Inertia::render('Profile/Biodata', [
            'user' => $user,
            'departments' => $departments,
        ]);
    }

    /**
     * Update Biodata / Form Data Diri Karyawan.
     */
    public function updateBiodata(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return redirect()->route('login');
            }

            // Normalize gender & blood_type before validation
            $input = $request->all();
            if (isset($input['gender']) && is_string($input['gender'])) {
                $g = strtoupper(trim($input['gender']));
                if (in_array($g, ['L', 'LAKI-LAKI', 'LAKI - LAKI', 'PRIA', 'MALE'])) {
                    $input['gender'] = 'Laki-laki';
                } elseif (in_array($g, ['P', 'PEREMPUAN', 'WANITA', 'FEMALE'])) {
                    $input['gender'] = 'Perempuan';
                }
            }
            if (isset($input['blood_type']) && is_string($input['blood_type'])) {
                $input['blood_type'] = strtoupper(trim($input['blood_type']));
            }
            $request->merge($input);

            $validated = $request->validate([
                // Data Pribadi
                'ktp_number' => 'nullable|string|max:30',
                'gender' => 'nullable|string|max:50',
                'birth_place' => 'nullable|string|max:100',
                'birth_date' => 'nullable|date',
                'phone_number' => 'nullable|string|max:30',
                'ktp_address' => 'nullable|string|max:1000',
                'domicile_address' => 'nullable|string|max:1000',
                'marital_status' => 'nullable|string|max:50',
                'mother_maiden_name' => 'nullable|string|max:150',
                'kk_number' => 'nullable|string|max:30',
                'blood_type' => 'nullable|string|max:10',
                'education' => 'nullable|string|max:100',

                // Keuangan & BPJS
                'npwp' => 'nullable|string|max:50',
                'bpjs_kesehatan_number' => 'nullable|string|max:50',
                'bpjs_health_facility' => 'nullable|string|max:150',
                'bpjs_ketenagakerjaan_number' => 'nullable|string|max:50',
                'bank_name' => 'nullable|string|max:50',
                'bank_account_number' => 'nullable|string|max:50',

                // Logistik & Operasional
                'vehicle_plate_number' => 'nullable|string|max:30',
                'sim_number' => 'nullable|string|max:50',
                'sim_valid_until' => 'nullable|date',
                'shoe_size' => 'nullable|string|max:10',

                // Kontak Darurat
                'emergency_contact_name' => 'nullable|string|max:150',
                'emergency_contact_relationship' => 'nullable|string|max:50',
                'emergency_contact_phone' => 'nullable|string|max:30',
                'emergency_contact_address' => 'nullable|string|max:1000',

                // Data Pasangan & Anak
                'spouse_name' => 'nullable|string|max:150',
                'spouse_ktp_number' => 'nullable|string|max:30',
                'spouse_birth_place' => 'nullable|string|max:100',
                'spouse_birth_date' => 'nullable|date',
                'child_1_name' => 'nullable|string|max:150',
                'child_2_name' => 'nullable|string|max:150',
                'child_3_name' => 'nullable|string|max:150',
            ]);

            $user->fill($validated);

            // Tandai selesai jika persentase kelengkapan memadai (>= 75%)
            try {
                $completeness = $user->profile_completeness;
                $user->is_profile_completed = ($completeness >= 75);
            } catch (\Throwable $e) {}

            $user->save();

            return redirect()->back()->with('success', 'Data diri Anda berhasil diperbarui dan disimpan ke sistem!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan data diri: ' . $e->getMessage());
        }
    }

    /**
     * Tampilan Cetak Form Data Diri resmi PT SUGIYAMA INDONESIA.
     */
    public function printBiodata(Request $request): Response
    {
        $user = $request->user()->load('department');

        return Inertia::render('Profile/PrintBiodata', [
            'employee' => $user,
        ]);
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240',
        ]);

        $user = $request->user();

        // Delete old avatar from cloud & local
        if ($user->avatar) {
            $disk = config('filesystems.default', 'public');
            @Storage::disk($disk)->delete($user->avatar);
            if ($disk !== 'public') {
                @Storage::disk('public')->delete($user->avatar);
            }
        }

        $file = $request->file('avatar');
        $webpPath = MediaOptimizer::convertImageToWebp($file, 'avatars', 85, 400, 400);

        $user->update(['avatar' => $webpPath]);

        return Redirect::back()->with('success', 'Foto profil berhasil dikonversi ke format WebP & disimpan!');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi saat ini salah.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $user = $request->user();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return Redirect::back()->with('success', 'Kata sandi Anda berhasil diperbarui!');
    }
}
