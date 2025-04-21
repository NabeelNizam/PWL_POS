@extends('layouts.template')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Stok</h3>
        <div class="card-tools">
            <button onclick="modalAction('{{ url('/stok/import') }}')" class="btn btn-info">Import Stok</button>
            <a href="{{ url('/stok/export_excel') }}" class="btn btn-primary"><i class="fa fa-file-excel"></i> Export Data</a>
            <a href="{{ url('/stok/export_pdf') }}" class="btn btn-warning"><i class="fa fa-file-pdf"></i> Export Data</a>
            <button onclick="modalAction('{{ url('/stok/create_ajax') }}')" class="btn btn-success">Tambah Data (Ajax)</button>
        </div>
    </div>

    <div class="card-body">
        <!-- Filter data -->
        

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <table class="table table-bordered table-sm table-striped table-hover" id="table_stok">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Barang</th>
                    <th>User Input</th>
                    <th>Supplier</th>
                    <th>Tanggal Stok</th>
                    <th >Jumlah</th>
                    <th >Aksi</th>
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

    var dataStok;

    $(document).ready(function () {
        // 1. CSRF token setup
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        dataStok = $('#table_stok').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ url('stok/list') }}",
                type: "POST",
                dataType: "json"
                
            },
            columns: [
                {
                    data: "DT_RowIndex",
                    className: "text-center",
                    orderable: false,
                    searchable: false
                },
                {
                    data: "barang.barang_nama",
                    orderable: true,
                    searchable: false
                },
                {
                    data: "user.username",
                    orderable: true,
                    searchable: true
                },
                {
                    data: "supplier.supplier_nama",
                    orderable: true,
                    searchable: true
                },
                {
                    data: "stok_tanggal",
                    className: "text-center",
                    orderable: true,
                    searchable: false
                },
                {
                    data: "jumlah",
                    className: "text-center",
                    orderable: false,
                    searchable: false
                },
                {
                    data: "aksi",
                    className: "text-center",
                    orderable: false,
                    searchable: false
                }
            ]
        });
    });

    function updateJumlah(stokId, perubahan) {
    $.ajax({
        url: '{{ url("stok/update_jumlah") }}',
        type: 'POST',
        data: {
            stok_id: stokId,
            perubahan: perubahan,
            _token: '{{ csrf_token() }}'
        },
        success: function (res) {
            if (res.success) {
                // reload datatable
                $('#table_stok').DataTable().ajax.reload(null, false);

                // Seleksi elemen angka stok berdasarkan data-id
                const el = document.querySelector('[data-id="' + stokId + '"]');
                if (el) {
                    // Simpan warna asli
                    const originalBg = el.style.backgroundColor;

                    // Atur warna background sementara
                    el.style.backgroundColor = (perubahan > 0) ? '#d4edda' : '#f8d7da';

                    // Transisi manual (via timeout)
                    setTimeout(() => {
                        el.style.backgroundColor = originalBg;
                    }, 1000);
                }

            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: res.message || 'Gagal update stok'
                });
            }
        },
        error: function () {
            Swal.fire({
                icon: 'error',
                title: 'Terjadi Kesalahan',
                text: 'Gagal terhubung ke server saat update stok'
            });
        }
    });
}


function resetStok(stokId) {
    Swal.fire({
        title: 'Reset stok?',
        text: "Stok akan diubah menjadi 0!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, reset!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ url("stok/reset_jumlah") }}',
                type: 'POST',
                data: {
                    stok_id: stokId,
                    _token: '{{ csrf_token() }}'
                },
                success: function (res) {
                    if (res.success) {
                        $('#table_stok').DataTable().ajax.reload(null, false);

                        const el = document.querySelector('[data-id="' + stokId + '"]');
                        if (el) {
                            const originalBg = el.style.backgroundColor;
                            el.style.backgroundColor = '#ffeeba';
                            setTimeout(() => {
                                el.style.backgroundColor = originalBg;
                            }, 1000);
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Stok berhasil di-reset ke 0',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire('Gagal!', res.message || 'Gagal reset stok', 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error!', 'Terjadi kesalahan saat reset stok', 'error');
                }
            });
        }
    });
}

$(document).on('click', '.editable-stok', function () {
    var span = $(this);
    var oldValue = span.text();
    var stokId = span.data('id');

    // Ganti span jadi input
    var input = $('<input type="number" class="form-control form-control-sm" style="width:70px; display:inline;" />')
        .val(oldValue);

    span.replaceWith(input);
    input.focus();

    // Saat blur atau tekan enter
    input.on('blur keyup', function (e) {
    if (e.type === 'blur' || e.key === 'Enter') {
        var newValue = input.val();
        if (newValue !== oldValue) {
            $.ajax({
                url: '{{ url("stok/update_jumlah_manual") }}',
                method: 'POST',
                data: {
                    stok_id: stokId,
                    stok_jumlah: newValue,
                },
                success: function (res) {
                    if (res.success) {
                        dataStok.ajax.reload(null, false); // reload tanpa reset pagination
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: res.message || 'Gagal update'
                        });
                        input.replaceWith('<span class="editable-stok" data-id="' + stokId + '" data-old="' + oldValue + '">' + oldValue + '</span>');
                    }
                },
                error: function () {
                    Swal.fire({
                        icon: 'error',
                        title: 'Terjadi Kesalahan',
                        text: 'Gagal terhubung ke server saat update stok'
                    });
                    input.replaceWith('<span class="editable-stok" data-id="' + stokId + '" data-old="' + oldValue + '">' + oldValue + '</span>');
                }
            });
        } else {
            input.replaceWith('<span class="editable-stok" data-id="' + stokId + '" data-old="' + oldValue + '">' + oldValue + '</span>');
        }
    }
});

});
</script>

@endpush

