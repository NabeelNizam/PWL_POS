@extends('layouts.template')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Transaksi Penjualan</h3>
            <div class="card-tools">
                <a href="{{ url('/penjualan/export_excel') }}" class="btn btn-primary" ><i class="fa fa-file-excel"></i>Export Data</a>
                <a href="{{ url('/penjualan/export_pdf') }}" class="btn btn-warning" ><i class="fa fa-file-pdf"></i>Export Data</a>
                <button onclick="modalAction('{{ url('/penjualan/create_ajax') }}')" class="btn btn-success">Tambah Data (Ajax)</button>
            </div>
        </div>

        <div class="card-body">

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <table class="table table-bordered table-sm table-striped table-hover" id="table_penjualan">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode</th>
                        <th>Tanggal</th>
                        <th>Pembeli</th>
                        <th>User</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div id="myModal" class="modal fade animate shake" tabindex="-1" data-backdrop="static" data-keyboard="false" data-width="75%"></div>
@endsection

@push('css')
@endpush

@push('js')
    <script>
        function modalAction(url = '') {
            $('#myModal').load(url, function () {
                $('#myModal').modal('show');
            });
        }

        var dataPenjualan;

        $(document).ready(function () {
            window.dataPenjualan = $('#table_penjualan').DataTable({
                serverSide: true,
                processing: true,
                ajax: {
                    url: "{{ url('penjualan/list') }}",
                    dataType: "json",
                    type: "POST",
                },
                columns: [
                    {
                        data: "DT_RowIndex",
                        className: "text-center",
                        width: "5%",
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: "penjualan_kode",
                        width: "12%",
                        orderable: true
                    },
                    {
                        data: "penjualan_tanggal",
                        width: "15%",
                        orderable: true
                    },
                    {
                        data: "pembeli",
                        width: "20%",
                        orderable: true
                    },
                    {
                        data: "user.username",
                        width: "15%",
                        orderable: false
                    },
                    {
                        data: "aksi",
                        className: "text-center",
                        width: "18%",
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            $('#table_penjualan_filter input').unbind().bind('keyup', function (e) {
                if (e.keyCode === 13) {
                    dataPenjualan.search(this.value).draw();
                }
            });
        });
    </script>
@endpush
