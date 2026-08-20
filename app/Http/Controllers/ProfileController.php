<?php

namespace App\Http\Controllers;

use App\Services\MediaOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;

class ProfileController extends Controller
{
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240',
        ]);

        $user = $request->user();

        // Delete old avatar
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $file = $request->file('avatar');
        $webpPath = MediaOptimizer::convertImageToWebp($file, 'avatars', 85, 400, 400);

        $user->update(['avatar' => $webpPath]);

        return Redirect::back()->with('success', 'Foto profil berhasil dikonversi ke format WebP & disimpan!');
    }
}
