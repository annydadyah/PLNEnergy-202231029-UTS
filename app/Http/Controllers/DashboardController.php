<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User; 

class DashboardController extends Controller
{

    public function index(Request $request)
    {
        if (!Auth::check()) {
            Log::warning('User not authenticated accessing dashboard');
            return redirect()->route('login')->with('error', 'Silakan login untuk mengakses dashboard.');
        }

        // Get user ID directly from the authenticated session
        $userId = Auth::id();
        
        if (!$userId) {
            $userId = session('login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d');
            Log::info('Trying to get user ID from session: ' . $userId);
        }
        
        if (!$userId && $request->user()) {
            $userId = $request->user()->id;
            Log::info('Got user ID from request: ' . $userId);
        }
        
        if (!$userId) {
            $lastUser = User::latest()->first();
            if ($lastUser) {
                $userId = $lastUser->id;
                Log::info('Using last user as fallback: ' . $userId);
            } else {
                Log::error('No users found in database');
                return view('pages.dashboard')->with('error', 'Tidak dapat mengidentifikasi pengguna. Silakan login ulang.');
            }
        }

        $currentMonth = date('m');
        $currentYear = date('Y');
        $pricePerKwh = 1699;

        Log::info('User ID yang digunakan: ' . $userId);

        $purchasesThisMonth = DB::table('transactions')
            ->where('customer_id', $userId)
            ->whereMonth('transaction_date', $currentMonth)
            ->whereYear('transaction_date', $currentYear)
            ->whereIn('status', ['paid', 'success'])
            ->sum('amount');

        Log::info('Total pembelian bulan ini: ' . $purchasesThisMonth);

        $tokensPurchasedThisMonth = ($pricePerKwh > 0) ? ($purchasesThisMonth / $pricePerKwh) : 0;

        $transactions = DB::table('transactions')
            ->where('customer_id', $userId)
            ->whereIn('status', ['paid', 'success'])
            ->orderBy('transaction_date', 'desc')
            ->get();

        Log::info('Jumlah transaksi berhasil: ' . count($transactions));
        
        $allTransactions = DB::table('transactions')->get();
        Log::info('Total semua transaksi di database: ' . count($allTransactions));
        
        if (count($allTransactions) > 0) {
            $sampleTransaction = $allTransactions->first();
            Log::info('Sample transaction: ' . json_encode($sampleTransaction));
        }

        $transactions = $transactions->map(function ($transaction) {
            if (isset($transaction->transaction_date)) {
                $transaction->transaction_date = Carbon::parse($transaction->transaction_date);
            }
            return $transaction;
        });

        return view('pages.dashboard', compact('purchasesThisMonth', 'tokensPurchasedThisMonth', 'transactions', 'userId'));
    }

    public function getEnergyUsageData(Request $request)
    {
        // Get user ID directly from the authenticated session
        $userId = Auth::id();
        
        if (!$userId) {
            $userId = session('login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d');
            Log::info('API - Trying to get user ID from session: ' . $userId);
        }

        if (!$userId && $request->user()) {
            $userId = $request->user()->id;
            Log::info('API - Got user ID from request: ' . $userId);
        }

        if (!$userId && $request->has('user_id')) {
            $userId = $request->input('user_id');
            Log::info('API - Using user_id from request parameters: ' . $userId);
        }

        if (!$userId) {
            $lastUser = User::latest()->first();
            if ($lastUser) {
                $userId = $lastUser->id;
                Log::info('API - Using last user as fallback: ' . $userId);
            }
        }

        if (!$userId) {
            Log::warning('getEnergyUsageData called but could not determine user ID');
            return response()->json(['error' => 'User not authenticated or ID not found.'], 401);
        }

        try {
            $pricePerKwh = 1699;

            // Debug info
            Log::info('getEnergyUsageData - User ID yang digunakan: ' . $userId);

            $endDate = Carbon::now()->endOfMonth();
            $startDate = Carbon::now()->subMonths(11)->startOfMonth();

            // Debug info
            Log::info('Date range: ' . $startDate->toDateString() . ' to ' . $endDate->toDateString());

            $monthsRange = [];
            $currentDate = $startDate->copy();

            while ($currentDate <= $endDate) {
                $yearMonth = $currentDate->format('Y-m');
                $monthName = $currentDate->format('M Y');
                $monthsRange[$yearMonth] = [
                    'year_month' => $yearMonth,
                    'month_name' => $monthName,
                    'total_purchased' => 0.0
                ];
                $currentDate->addMonth();
            }

            // Check database table and column names
            $hasTransactions = DB::select("SHOW TABLES LIKE 'transactions'");
            if (empty($hasTransactions)) {
                Log::error("Table 'transactions' does not exist!");
                return response()->json(['error' => 'Database configuration issue.'], 500);
            }
            
            $columns = DB::select("SHOW COLUMNS FROM transactions");
            $columnNames = array_map(function($col) { return $col->Field; }, $columns);
            Log::info("Transaction table columns: " . implode(", ", $columnNames));

            // Verify customer_id column exists
            if (!in_array('customer_id', $columnNames)) {
                Log::error("Column 'customer_id' does not exist in transactions table!");
                return response()->json(['error' => 'Database configuration issue.'], 500);
            }

            // Menggunakan query builder untuk mendapatkan data bulanan
            $monthlyPurchase = DB::table('transactions')
                ->select(
                    DB::raw("DATE_FORMAT(transaction_date, '%Y-%m') as year_month_db"),
                    DB::raw("DATE_FORMAT(transaction_date, '%b %Y') as month_name_db"),
                    DB::raw("SUM(amount) as total_amount_paid")
                )
                ->where('customer_id', $userId)
                ->whereIn('status', ['paid', 'success'])
                ->whereBetween('transaction_date', [$startDate->toDateTimeString(), $endDate->toDateTimeString()])
                ->groupBy('year_month_db', 'month_name_db')
                ->orderBy('year_month_db', 'asc')
                ->get();

            // Debug info
            Log::info('Monthly purchase data count: ' . count($monthlyPurchase));
            
            // Jika tidak ada data, periksa apakah ada transaksi sama sekali
            if (count($monthlyPurchase) == 0) {
                $transactionCount = DB::table('transactions')
                    ->where('customer_id', $userId)
                    ->count();
                Log::info('Total transaction count for user: ' . $transactionCount);
                
                // Periksa juga berdasarkan status
                $statusCounts = DB::table('transactions')
                    ->where('customer_id', $userId)
                    ->select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->get();
                Log::info('Status counts: ' . json_encode($statusCounts));
                
                // Jika tidak ada transaksi untuk customer_id ini, tampilkan beberapa transaksi lain sebagai debug
                if ($transactionCount == 0) {
                    $otherTransactions = DB::table('transactions')
                        ->take(5)
                        ->get();
                    if (count($otherTransactions) > 0) {
                        Log::info('Sample transactions from DB: ' . json_encode($otherTransactions));
                    } else {
                        Log::info('No transactions found in database');
                    }
                }
            }

            foreach ($monthlyPurchase as $month) {
                if (isset($monthsRange[$month->year_month_db])) {
                    $kwhPurchased = ($pricePerKwh > 0 && $month->total_amount_paid > 0) ? 
                        ($month->total_amount_paid / $pricePerKwh) : 0;
                    
                    $monthsRange[$month->year_month_db]['total_purchased'] = (float) number_format($kwhPurchased, 2, '.', '');
                    $monthsRange[$month->year_month_db]['month_name'] = $month->month_name_db;
                    
                    // Debug info
                    Log::info("Month: {$month->month_name_db}, Amount: {$month->total_amount_paid}, kWh: {$kwhPurchased}");
                }
            }

            $result = array_values($monthsRange);
            
            // Perbaikan: Kita menggunakan 'year_month' untuk pengurutan
            usort($result, function ($a, $b) {
                return strcmp($a['year_month'], $b['year_month']);
            });

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error in getEnergyUsageData: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to retrieve energy usage data. Please try again later.'], 500);
        }
    }
}