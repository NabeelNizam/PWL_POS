<?php

namespace App\Http\Controllers;

use App\Models\LevelModel;
use App\Models\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    
    public function index()
{
    $breadcrumb = (object) [
        'title' => 'TAMPILAN PROFILE',
        'list' => ['Home', 'Profile']
    ];

    $page = (object) [
        'title' => 'Profile'
    ];

    $activeMenu = 'profile';

    $user = Auth::user();

    return view('profile.index', ['breadcrumb' => $breadcrumb, 'page' => $page,'user' => $user,'activeMenu' => $activeMenu ]);
}
public function import()
{
    $user = Auth::user();
    return view('profile.import', compact('user'));
}
public function import_ajax(Request $request)
{
    try {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = Auth::user();
        $username = $user->username ?? 'user';
        $folderPath = public_path('profil/' . $username);

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');

            // Buat folder jika belum ada
            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0777, true);
            }

            $filePath = $folderPath . '/foto.png';

            // Hapus foto lama jika ada
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Simpan foto baru
            $file->move($folderPath, 'foto.png');

            return response()->json([
                'status' => true,
                'message' => 'Foto profil berhasil diunggah.'
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Tidak ada file yang diunggah.'
        ], 400);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'status' => false,
            'message' => $e->validator->errors()->first('foto')
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ], 500);
    }
}










    

}
