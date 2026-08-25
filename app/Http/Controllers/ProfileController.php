<?php

namespace App\Http\Controllers;

use App\Services\MediaOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
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
