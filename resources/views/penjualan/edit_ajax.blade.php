@empty($penjualan)
<div id="modal-master" class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Kesalahan</h5>
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
            <div class="alert alert-danger">
                <h5><i class="icon fas fa-ban"></i> Kesalahan!!!</h5>
                Data penjualan yang Anda cari tidak ditemukan.
            </div>
            <a href="{{ url('/penjualan') }}" class="btn btn-warning">Kembali</a>
        </div>
    </div>
</div>
@else
<form id="form-edit">
    @csrf
    @method('PUT')
    <div id="modal-master" class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Data Penjualan</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">

                <!-- Data Umum -->
                <div class="form-group">
                    <label for="user_id">Nama User</label>
                    <select name="user_id" id="user_id" class="form-control" required>
                        <option value="">- Pilih User -</option>
                        @foreach ($user as $u)
                            <option value="{{ $u->user_id }}" {{ $penjualan->user_id == $u->user_id ? 'selected' : '' }}>
                                {{ $u->username }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="pembeli">Nama Pembeli</label>
                    <input type="text" name="pembeli" id="pembeli" class="form-control" value="{{ $penjualan->pembeli}}" required>
                </div>
                
                <div class="form-group">
                    <label for="penjualan_tanggal">Tanggal Penjualan</label>
                    <input type="date" name="penjualan_tanggal" id="penjualan_tanggal" class="form-control" 
                     value="{{ \Carbon\Carbon::parse($penjualan->penjualan_tanggal)->format('Y-m-d') }}" required>
                </div>

                

                <!-- Detail Barang -->
                <h5>Detail Barang</h5>
                <table class="table table-bordered" id="table-barang">
                    <thead class="thead-light">
                        <tr>
                            <th>Barang</th>
                            <th>Harga (Rp)</th>
                            <th>Jumlah</th>
                            <th>Subtotal (Rp)</th>
                            <th>
                                <button type="button" class="btn btn-success btn-sm" id="addRow">+</button>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($penjualan->detail as $detail)
                        <tr>
                            <td>
                                <select name="barang_id[]" class="form-control barang-select" required>
                                    <option value="">Pilih Barang</option>
                                    @foreach ($barang as $b)
                                        <option value="{{ $b->barang_id }}" data-harga="{{ $b->harga_jual }}"
                                            {{ $detail->barang_id == $b->barang_id ? 'selected' : '' }}>
                                            {{ $b->barang_kode }} - {{ $b->barang_nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" name="harga[]" class="form-control harga" value="{{ $detail->harga }}" readonly>
                            </td>
                            <td>
                                <input type="number" name="jumlah[]" class="form-control jumlah" value="{{ $detail->jumlah }}" min="1" required>
                            </td>
                            <td>
                                <input type="number" name="subtotal[]" class="form-control subtotal" value="{{ $detail->subtotal }}" readonly>
                            </td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm removeRow">-</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="text-right mt-3">
                    <strong>Total: <span id="total" class="text-primary">Rp {{ number_format($penjualan->total_harga, 0, ',', '.') }}</span></strong>
                </div>

            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
            </div>
        </div>
    </div>
</form>

<script>
$(document).ready(function () {
    function formatRupiah(angka) {
        return 'Rp ' + angka.toLocaleString('id-ID');
    }

    function hitungSubtotal(row) {
        let harga = parseFloat(row.find('.harga').val()) || 0;
        let jumlah = parseInt(row.find('.jumlah').val()) || 0;
        let subtotal = harga * jumlah;
        row.find('.subtotal').val(subtotal);
        return subtotal;
    }

    function hitungTotal() {
        let total = 0;
        $('#table-barang tbody tr').each(function () {
            total += hitungSubtotal($(this));
        });
        $('#total').text(formatRupiah(total));
    }

    $('#table-barang').on('change', '.barang-select', function () {
        let harga = parseFloat($(this).find(':selected').data('harga')) || 0;
        let row = $(this).closest('tr');
        row.find('.harga').val(harga);
        hitungSubtotal(row);
        hitungTotal();
    });

    $('#table-barang').on('input', '.jumlah', function () {
        let row = $(this).closest('tr');
        hitungSubtotal(row);
        hitungTotal();
    });

    $('#addRow').click(function () {
        let newRow = $('#table-barang tbody tr:first').clone();
        newRow.find('select').val('');
        newRow.find('input').val('');
        newRow.find('.jumlah').val(1);
        $('#table-barang tbody').append(newRow);
    });

    $('#table-barang').on('click', '.removeRow', function () {
        if ($('#table-barang tbody tr').length > 1) {
            $(this).closest('tr').remove();
            hitungTotal();
        }
    });

    hitungTotal();

    // HANDLE SUBMIT
    $('#form-edit').on('submit', function (e) {
        e.preventDefault();

        let form = $(this);
        let actionUrl = "{{ url('/penjualan/' . $penjualan->penjualan_id . '/update_ajax') }}";

        $.ajax({
            url: actionUrl,
            type: 'POST',
            data: form.serialize(),
            headers: {
                'X-CSRF-TOKEN': $('input[name="_token"]').val(),
                'X-HTTP-Method-Override': 'PUT'
            },
            success: function (response) {
                if (response.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.href = "{{ url('/penjualan') }}";
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: response.message
                    });
                }
            },
            error: function (xhr) {
                let message = "Terjadi kesalahan. Silakan coba lagi.";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message
                });
            }
        });
    });
});
</script>
@endempty
