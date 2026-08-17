<?php

namespace App\Http\Controllers;

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
        $webpPath = $this->convertToWebpAndStore($file, 'avatars');

        $user->update(['avatar' => $webpPath]);

        return Redirect::back()->with('success', 'Foto profil berhasil dikonversi ke format WebP & disimpan!');
    }

    /**
     * Convert uploaded image to WebP format automatically
     */
    private function convertToWebpAndStore($file, string $folder = 'avatars', int $quality = 80): string
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

        $filename = $folder . '/' . uniqid('avatar_') . '_' . time() . '.webp';

        if ($image && function_exists('imagewebp')) {
            imagealphablending($image, true);
            imagesavealpha($image, true);

            ob_start();
            imagewebp($image, null, $quality);
            $webpContent = ob_get_clean();
            imagedestroy($image);

            Storage::disk('public')->put($filename, $webpContent);
            return $filename;
        }

        // Fallback standard storage if GD function not available
        return $file->store($folder, 'public');
    }
}
