<?php

namespace App\Http\Controllers;

use App\Models\User; // Pastikan path model User Anda benar
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;     // Untuk logging
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Menampilkan halaman profil.
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        return $this->show();
    }

    /**
     * Menampilkan profil pengguna.
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->withErrors('Silakan login untuk melihat profil.');
        }
        return view('pages.profile.show', compact('user'));
    }

    /**
     * Menampilkan form untuk mengedit profil pengguna.
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->withErrors('Silakan login untuk mengedit profil.');
        }
        return view('pages.profile.edit', compact('user'));
    }

    /**
     * Memperbarui profil pengguna.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->withErrors('Sesi Anda telah berakhir, silakan login kembali untuk memperbarui profil.');
        }

        // Log data request awal untuk debugging
        Log::info('Profile Update Attempt - User ID: ' . $user->id . ' - Request Data:', $request->all());
        Log::info('Profile Update Attempt - User ID: ' . $user->id . ' - Original User Data:', $user->getOriginal());

        // Membersihkan input (opsional tapi direkomendasikan untuk field tertentu)
        $request->merge([
            'email' => trim($request->input('email')),
            'kwh_meter_code' => trim($request->input('kwh_meter_code')),
        ]);
        // Log data request setelah trim
        Log::info('Profile Update Attempt - User ID: ' . $user->id . ' - Trimmed Request Data:', $request->only(['name', 'email', 'kwh_meter_code']));


        $primaryKeyColumn = $user->getKeyName(); // Mendapatkan nama kolom primary key secara dinamis

        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->{$primaryKeyColumn}, $primaryKeyColumn),
            ],
            'kwh_meter_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'kwh_meter_code')->ignore($user->{$primaryKeyColumn}, $primaryKeyColumn),
            ],
            'password' => [
                'nullable', // Boleh kosong jika tidak ingin diubah
                'confirmed', // Harus ada field password_confirmation
                Password::min(8)->mixedCase()->letters()->numbers()->symbols(),
            ],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email ini sudah digunakan oleh pengguna lain.',
            'kwh_meter_code.required' => 'Kode KWH meter wajib diisi.',
            'kwh_meter_code.unique' => 'Kode KWH meter ini sudah digunakan oleh pengguna lain.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            'password.min' => 'Kata sandi baru minimal harus 8 karakter dan memenuhi kriteria kompleksitas.',
        ]);

        Log::info('Profile Update - User ID: ' . $user->id . ' - Validation Passed. Validated Data:', $validatedData);

        // Tetapkan nilai baru ke model
        $user->name = $validatedData['name'];
        $user->email = $validatedData['email'];
        $user->kwh_meter_code = $validatedData['kwh_meter_code'];

        if ($request->filled('password')) {
            $user->password = Hash::make($validatedData['password']); // Laravel 9+ auto-hashes via $casts
            Log::info('Profile Update - User ID: ' . $user->id . ' - Password will be updated.');
        }

        // Cek apakah ada perubahan pada model SEBELUM save()
        if ($user->isDirty()) {
            Log::info('Profile Update - User ID: ' . $user->id . ' - User model is DIRTY. Changes to be saved:', $user->getDirty());
        } else {
            Log::warning('Profile Update - User ID: ' . $user->id . ' - User model is NOT DIRTY. No changes were detected. This might be okay if data was identical, or it could indicate an issue.');
             // Jika tidak ada perubahan, kita bisa langsung redirect atau tetap save (tergantung preferensi)
             // Untuk kasus ini, kita tetap coba save untuk konsistensi,
             // tapi log ini penting untuk diagnosis jika update "tidak terjadi" padahal seharusnya.
        }

        try {
            $saveResult = $user->save(); // Menyimpan perubahan ke database

            if ($saveResult) {
                Log::info('Profile Update - User ID: ' . $user->id . ' - user->save() executed successfully. Database should be updated.');
            } else {
                // Ini jarang terjadi jika tidak ada exception, tapi bisa jika event listener (saving/updating) mengembalikan false
                Log::error('Profile Update - User ID: ' . $user->id . ' - user->save() returned false. Update might have been prevented by an event listener or other model logic.');
                // Pertimbangkan untuk memberi tahu pengguna bahwa update mungkin tidak berhasil
            }
        } catch (\Exception $e) {
            Log::error('Profile Update - User ID: ' . $user->id . ' - EXCEPTION during user->save(): ' . $e->getMessage(), [
                'exception_trace' => $e->getTraceAsString() // Untuk detail lebih lanjut jika perlu
            ]);
            // Kembalikan ke form dengan error
            return Redirect::back()
                ->withInput() // Mengembalikan input sebelumnya ke form
                ->withErrors('Terjadi kesalahan teknis saat mencoba menyimpan profil. Silakan coba lagi atau hubungi administrator.');
        }
        
        // Optional: Muat ulang data dari database untuk memastikan dan log
        // $user->refresh();
        // Log::info('Profile Update - User ID: ' . $user->id . ' - User data after save attempt (refreshed):', $user->toArray());


        return Redirect::route('profile.index')->with('success', 'Profil berhasil diperbarui.');
    }

    /**
     * Menghapus akun pengguna.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->withErrors('Tidak dapat menghapus akun, sesi tidak ditemukan.');
        }

        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/login')->with('success', 'Akun Anda telah berhasil dihapus.');
    }
}