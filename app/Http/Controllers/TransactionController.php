<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode; // Import QrCode Facade

class TransactionController extends Controller
{
    // Anda sebaiknya menggunakan middleware auth di sini jika halaman ini
    // hanya untuk pengguna yang sudah login.
    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }

    public function index()
    {
        try {
            // Ambil customer_id dari user yang sedang login
            // Jika halaman ini bisa diakses oleh admin yang melihat semua transaksi,
            // maka logika pengambilan customer_id perlu disesuaikan.
            // Untuk saat ini, kita asumsikan ini untuk user yang sedang login.
            $customerId = Auth::id();

            if (!$customerId) {
                // Jika tidak ada user yang login, dan halaman ini butuh user
                // redirect ke login atau tampilkan error.
                // Atau jika ini halaman admin, logika akan berbeda.
                // Untuk contoh ini, kita akan mengembalikan koleksi kosong jika tidak ada user.
                // Sebaiknya redirect ke login.
                return redirect()->route('login')->with('error', 'Silakan login untuk melihat riwayat transaksi.');
            }

            // ---- PERBAIKAN UTAMA ADA DI SINI ----
            // Ambil SEMUA transaksi milik customer tersebut, diurutkan berdasarkan tanggal.
            // Hapus ->paginate(10) dan ganti dengan ->get()
            $transactions = Transaction::where('customer_id', $customerId) // Filter berdasarkan customer_id
                ->orderBy('transaction_date', 'desc')
                ->get();
            // ------------------------------------

            return view('pages.transaction.index', compact('transactions'));
        } catch (\Exception $e) {
            // Tangkap error dan tampilkan pesan yang jelas
            // Juga log errornya untuk debugging di server
            \Illuminate\Support\Facades\Log::error('Error fetching transactions: ' . $e->getMessage());
            return view('pages.transaction.index')
                ->with('error', 'Terjadi kesalahan saat memuat riwayat transaksi. Silakan coba lagi nanti.')
                ->with('transactions', collect([])); // Kirim koleksi kosong agar tidak undefined
        }
    }

    public function create()
    {
        // Pastikan user sudah login untuk membuat transaksi
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan login untuk membuat transaksi baru.');
        }
        $paymentMethods = ['E-Wallet', 'Virtual Account'];
        return view('pages.transaction.create', compact('paymentMethods'));
    }

    public function store(Request $request)
    {
        // Pastikan user sudah login
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Sesi Anda telah berakhir. Silakan login kembali.');
        }

        $validatedData = $request->validate([
            'amount'         => 'required|integer|min:10000', // Minimal amount, sesuaikan
            'payment_method' => 'required|string|in:E-Wallet,Virtual Account', // Pastikan nilainya sesuai
        ], [
            'amount.min' => 'Jumlah minimal transaksi adalah Rp 10.000.',
            'payment_method.in' => 'Metode pembayaran tidak valid.'
        ]);

        $customerId = Auth::id();

        try {
            $transaction = new Transaction();
            $transaction->customer_id = $customerId; // Gunakan ID user yang login
            $transaction->transaction_date = Carbon::now();
            $transaction->amount = $validatedData['amount'];
            $transaction->status = 'owing'; // Status awal 'owing'
            $transaction->payment_method = $validatedData['payment_method'];

            $paymentCode = null;
            $qrCodeData = null; // Variabel untuk data QR Code yang akan di-pass ke view
            
            if ($validatedData['payment_method'] === 'E-Wallet') {
                $paymentCodeContent = 'PLN_PAYMENT|' . $transaction->amount . '|' . time() . '|' . Str::random(8);
                $transaction->payment_code = $paymentCodeContent;
                
                try {
                    // Gunakan driver svg yang tidak memerlukan Imagick
                    $qrCodeData = base64_encode(QrCode::format('svg')->size(200)->generate($paymentCodeContent));
                } catch (\Exception $qrEx) {
                    // Fallback: jika gagal generate QR code, gunakan payment code biasa
                    \Illuminate\Support\Facades\Log::warning('Failed to generate QR code: ' . $qrEx->getMessage());
                    // Tetap lanjutkan proses tanpa QR code
                }
            } elseif ($validatedData['payment_method'] === 'Virtual Account') {
                $paymentCode = '99' . str_pad(mt_rand(1, 99999999999999), 14, '0', STR_PAD_LEFT);
                $transaction->payment_code = $paymentCode;
            }

            $transaction->save();

            // Redirect ke halaman show, agar user bisa melihat QR Code atau VA Number
            // Kita akan pass QR code (jika ada) melalui session flash
            $redirect = redirect()->route('transactions.show', $transaction->transaction_id)
                ->with('success', 'Transaksi berhasil dibuat. Status: Menunggu Pembayaran. Silakan selesaikan pembayaran Anda.');

            if ($qrCodeData) {
                $redirect->with('qrCodeData', $qrCodeData); // Pass base64 encoded QR code
            }

            return $redirect;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error storing transaction: ' . $e->getMessage());
            return redirect()->route('transactions.create')
                ->with('error', 'Gagal membuat transaksi: ' . $e->getMessage())
                ->withInput();
        }
    }


    public function show($id)
    {
        try {
            // Pastikan user hanya bisa melihat transaksinya sendiri, kecuali admin
            $transaction = Transaction::where('transaction_id', $id)
                ->where('customer_id', Auth::id()) // Filter berdasarkan customer_id
                ->firstOrFail(); // Gunakan firstOrFail untuk otomatis 404 jika tidak ditemukan atau bukan milik user

            // Jika ada qrCodeData di session (dari proses store), pass ke view
            $qrCodeData = session('qrCodeData');

            // Jika tidak ada QR code data di session tetapi ini metode E-Wallet, coba generate ulang
            if (!$qrCodeData && $transaction->payment_method === 'E-Wallet' && $transaction->payment_code) {
                try {
                    $qrCodeData = base64_encode(QrCode::format('svg')->size(200)->generate($transaction->payment_code));
                } catch (\Exception $qrEx) {
                    // Abaikan error, tampilkan halaman tanpa QR code
                    \Illuminate\Support\Facades\Log::warning('Failed to regenerate QR code: ' . $qrEx->getMessage());
                }
            }

            return view('pages.transaction.show', compact('transaction', 'qrCodeData'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('transactions.index')
                ->with('error', 'Transaksi tidak ditemukan atau Anda tidak memiliki akses.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error showing transaction ' . $id . ': ' . $e->getMessage());
            return redirect()->route('transactions.index')
                ->with('error', 'Terjadi kesalahan saat menampilkan detail transaksi.');
        }
    }

    public function updateStatus(Request $request, $id)
    {
        // Sebaiknya operasi ini hanya bisa dilakukan oleh admin atau sistem pembayaran
        // Untuk contoh, kita tidak membatasi siapa yang bisa update, tapi dalam produksi ini perlu perhatian
        $request->validate([
            'status' => 'required|in:owing,paid,failed,success',
        ]);

        try {
            // Idealnya, admin tidak perlu filter customer_id, tapi jika user bisa update status sendiri, maka perlu:
            // $transaction = Transaction::where('transaction_id', $id)
            //                          ->where('customer_id', Auth::id())
            //                          ->firstOrFail();
            $transaction = Transaction::findOrFail($id); // Untuk admin atau sistem callback

            $oldStatus = $transaction->status;
            $newStatus = $request->status;

            // Hanya update jika status benar-benar berubah
            if ($oldStatus !== $newStatus) {
                $transaction->status = $newStatus;

                // Generate token hanya jika status berubah menjadi paid/success dari status BUKAN paid/success
                if (($newStatus == 'paid' || $newStatus == 'success') &&
                    !in_array($oldStatus, ['paid', 'success'])
                ) {
                    $transaction->generated_token = $this->generateNumericToken(20);
                }

                // Jika status diubah kembali dari paid/success ke owing/failed, mungkin token perlu di-null kan? Tergantung logika bisnis.
                // if (in_array($newStatus, ['owing', 'failed']) && in_array($oldStatus, ['paid', 'success'])) {
                //     $transaction->generated_token = null;
                // }

                $transaction->save();
            }


            if ($request->wantsJson()) { // Jika request adalah AJAX
                return response()->json([
                    'success' => true,
                    'message' => 'Status transaksi berhasil diupdate.',
                    'data' => $transaction->fresh(), // Kirim data transaksi yang sudah terupdate
                    'redirectTo' => ($newStatus === 'failed' && $request->has('from_payment_page')) ? route('transactions.index') : null
                ]);
            }

            return redirect()->route('transactions.show', $transaction->transaction_id)
                ->with('success', 'Status transaksi berhasil diupdate.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Transaksi tidak ditemukan.'], 404);
            }
            return redirect()->route('transactions.index')->with('error', 'Transaksi tidak ditemukan.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error updating transaction status ' . $id . ': ' . $e->getMessage());
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal update status: Terjadi kesalahan pada server.'
                ], 500);
            }

            return redirect()->route('transactions.index') // Atau kembali ke halaman sebelumnya jika memungkinkan
                ->with('error', 'Gagal update status: Terjadi kesalahan pada server.');
        }
    }

    /**
     * Generate a numeric token with specified length.
     *
     * @param  int  $length
     * @return string
     */
    private function generateNumericToken($length = 20)
    {
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= mt_rand(0, 9);
        }
        return $result;
    }
}