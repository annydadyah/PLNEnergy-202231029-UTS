@extends('layouts.app')

@section('title', 'Transaction History - Customer Portal PLN')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Energy Usage Transaction History</h1>
    <a href="{{ route('transactions.create') }}" class="btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-plus fa-sm text-white-50"></i> Add New Transaction
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">×</span>
    </button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">×</span>
    </button>
</div>
@endif

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Transaction List</h6>
    </div>
    <div class="card-body">
        {{-- Filter Inputs (Tanpa Filter Tanggal) --}}
        <div class="row mb-3">
            <div class="col-md-4"> {{-- Lebar kolom disesuaikan --}}
                <label for="filter-payment-method">Payment Method:</label>
                <select id="filter-payment-method" class="form-control form-control-sm">
                    <option value="">All</option>
                    <option value="E-Wallet">E-Wallet</option>
                    <option value="Virtual Account">Virtual Account</option>
                    {{-- Tambahkan metode lain jika ada --}}
                </select>
            </div>

            <div class="col-md-4"> {{-- Lebar kolom disesuaikan --}}
                <label for="filter-status">Status:</label>
                <select id="filter-status" class="form-control form-control-sm">
                    <option value="">All</option>
                    <option value="paid">Paid</option>
                    <option value="owing">Owing</option>
                    <option value="failed">Failed</option>
                    {{-- Tambahkan status lain jika relevan --}}
                </select>
            </div>
            {{-- Kolom kosong untuk sisa ruang jika diperlukan, atau hapus jika tidak ingin ada spasi --}}
            <div class="col-md-4"></div>
        </div>
        {{-- Tombol Clear Date Filters dihapus karena tidak ada filter tanggal --}}

        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Token</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions ?? collect([]) as $transaction)
                    <tr>
                        <td>{{ $transaction->transaction_date->format('d M Y H:i') }}</td>
                        <td>Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                        <td>{{ $transaction->payment_method }}</td>
                        {{-- Anda sudah menambahkan data-status, ini bagus untuk Pilihan 2 filter status jika diperlukan,
                             tapi untuk Pilihan 1 (contains search) tidak wajib. Saya akan tetap biarkan. --}}
                        <td data-status="{{ $transaction->status }}">
                            <span class="badge badge-{{
                                        $transaction->status == 'pending' || $transaction->status == 'owing' ? 'warning' :
                                        ($transaction->status == 'success' || $transaction->status == 'paid' ? 'success' : 'danger')
                                    }}">
                                {{ ucfirst($transaction->status) }}
                            </span>
                        </td>
                        <td>{{ $transaction->generated_token ?? '-' }}</td>
                        <td>
                            <a href="{{ route('transactions.show', $transaction->transaction_id) }}" class="btn btn-info btn-sm" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">No transaction data available.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="{{ asset('template/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
{{-- CSS untuk DataTables Buttons --}}
<link href="{{ asset('template/vendor/datatables-buttons/css/buttons.bootstrap4.min.css') }}" rel="stylesheet">
@endpush

@push('scripts')

{{-- Library utama DataTables --}}
<script src="{{ asset('template/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('template/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>

{{-- Library untuk DataTables Buttons --}}
<script src="{{ asset('template/vendor/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('template/vendor/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
{{-- JSZip diperlukan untuk ekspor CSV --}}
<script src="{{ asset('template/vendor/jszip/jszip.min.js') }}"></script>
{{-- Skrip untuk mengaktifkan tombol HTML5 (termasuk CSV) --}}
<script src="{{ asset('template/vendor/datatables-buttons/js/buttons.html5.min.js') }}"></script>

<script>
    const userInfo = {
        name: "{{ Auth::check() ? Auth::user()->name : 'Guest User' }}",
        email: "{{ Auth::check() ? Auth::user()->email : 'N/A' }}",
        kwhMeter: "{{ Auth::check() ? Auth::user()->kwh_meter_code : 'N/A' }}" // Mengambil langsung dari Auth::user()->kwh_meter_code
    };

    $(document).ready(function() {

        var table = $('#dataTable').DataTable({
            "destroy": true,
            "order": [
                [0, "desc"]
            ],
            "columnDefs": [{
                "type": "date",
                "targets": 0
            }],
            "pagingType": "simple",
            "pageLength": 10,
            "lengthMenu": [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "All"]
            ],
            dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>" +
                "<'row'<'col-sm-12 mt-3'B>>",
            buttons: [{
                extend: 'csvHtml5',
                text: '<i class="fas fa-file-csv"></i> Export ALL Transactions',
                titleAttr: 'Export all historical transaction data to CSV, ignoring current filters',
                className: 'btn btn-primary btn-sm',
                filename: 'all_historical_transactions_' + new Date().toISOString().slice(0, 10).replace(/-/g, ''),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4],
                    modifier: {
                        search: 'none',
                        page: 'all'
                    },
                    format: {
                        body: function(data, row, column, node) {
                            if (column === 3) { // Kolom status
                                return $(node).find('span.badge').text().trim();
                            }
                            if (column === 1 && typeof data === 'string') {
                                return data.replace(/Rp|\./g, '').trim();
                            }
                            return data;
                        }
                    }
                },
                customize: function(csv) {
                    let accountInfoCsv = `Nama Akun,"${userInfo.name}"\n`;
                    accountInfoCsv += `Email,"${userInfo.email}"\n`;
                    accountInfoCsv += `No. kWh Meter,"${userInfo.kwhMeter}"\n`;
                    accountInfoCsv += `\n`;

                    return accountInfoCsv + csv;
                }
            }]
        });

        // Event listener untuk filter status
        $('#filter-status').on('change', function() {
            var statusValue = $(this).val();
            if (statusValue) {
                var searchString = statusValue.charAt(0).toUpperCase() + statusValue.slice(1);
                table.column(3).search(searchString, false, true).draw();
            } else {
                table.column(3).search('').draw();
            }
        });

        // Event listener untuk filter metode pembayaran
        $('#filter-payment-method').on('change', function() {
            var paymentMethodValue = $(this).val();
            if (paymentMethodValue) {
                table.column(2).search('^' + paymentMethodValue + '$', true, false).draw();
            } else {
                table.column(2).search('').draw();
            }
        });
    });
</script>
@endpush
