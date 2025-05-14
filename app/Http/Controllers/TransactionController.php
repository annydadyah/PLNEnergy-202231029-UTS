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

    public function index()
    {
        try {
            $customerId = Auth::id();

            if (!$customerId) {

                return redirect()->route('login')->with('error', 'Please log in to make a new transaction.');
            }
            
            $transactions = Transaction::where('customer_id', $customerId) // Filter berdasarkan customer_id
                ->orderBy('transaction_date', 'desc')
                ->get();
            // ------------------------------------

            return view('pages.transaction.index', compact('transactions'));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error fetching transactions: ' . $e->getMessage());
            return view('pages.transaction.index')
                ->with('error', 'There was an error loading transaction history. Please try again later.')
                ->with('transactions', collect([])); // Kirim koleksi kosong agar tidak undefined
        }
    }

    public function create()
    {
        // Pastikan user sudah login untuk membuat transaksi
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please log in to view your transaction history.');
        }
        $paymentMethods = ['E-Wallet', 'Virtual Account'];
        return view('pages.transaction.create', compact('paymentMethods'));
    }

    public function store(Request $request)
    {
        // Pastikan user sudah login
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Your session has expired. Please log in again.');
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

            $redirect = redirect()->route('transactions.show', $transaction->transaction_id)
                ->with('success', 'Your transaction was created successfully. Status: Awaiting Payment. Please complete your payment.');

            if ($qrCodeData) {
                $redirect->with('qrCodeData', $qrCodeData); // Pass base64 encoded QR code
            }

            return $redirect;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error storing transaction: ' . $e->getMessage());
            return redirect()->route('transactions.create')
                ->with('error', 'Failed to create transaction: ' . $e->getMessage())
                ->withInput();
        }
    }


    public function show($id)
    {
        try {
            $transaction = Transaction::where('transaction_id', $id)
                ->where('customer_id', Auth::id()) // Filter berdasarkan customer_id
                ->firstOrFail(); 
            
            $qrCodeData = session('qrCodeData');

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
                ->with('error', 'Transaction not found or you do not have access.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error showing transaction ' . $id . ': ' . $e->getMessage());
            return redirect()->route('transactions.index')
                ->with('error', 'There was an error displaying the transaction details.');
        }
    }

    public function updateStatus(Request $request, $id)
    {
        
        $request->validate([
            'status' => 'required|in:owing,paid,failed,success',
        ]);

        try {

            $transaction = Transaction::findOrFail($id); 

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
                ->with('success', 'Transaction status updated successfully.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
            }
            return redirect()->route('transactions.index')->with('error', 'Transaction not found.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error updating transaction status ' . $id . ': ' . $e->getMessage());
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update status: An error occurred on the server.'
                ], 500);
            }

            return redirect()->route('transactions.index')
                ->with('error', 'Failed to update status: An error occurred on the server.');
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
