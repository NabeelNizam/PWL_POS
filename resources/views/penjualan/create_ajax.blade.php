<form action="{{ url('/penjualan/ajax') }}" method="POST" id="form-penjualan">
    @csrf
    <div id="modal-master" class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transaksi Penjualan</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {{-- Data Umum --}}
                <div class="form-group">
                    <label for="pembeli">Nama Pembeli</label>
                    <input type="text" name="pembeli" id="pembeli" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="user_id">Pilih User</label>
                    <select class="form-control" id="user_id" name="user_id" required>
                        <option value="">- Pilih User -</option>
                        @foreach ($user as $item)
                            <option value="{{ $item->user_id }}">
                                {{ $item->username }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <small class="form-text text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="penjualan_tanggal">Tanggal Pembelian</label>
                    <input type="date" name="penjualan_tanggal" id="penjualan_tanggal" class="form-control" required>
                    <small id="error-penjualan_tanggal" class="error-text form-text text-danger"></small>
                </div>

                <hr>

                {{-- Detail Penjualan --}}
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
                        <tr>
                            <td>
                                <select name="barang_id[]" class="form-control barang-select" required>
                                    <option value="">Pilih Barang</option>
                                    @foreach ($barang as $b)
                                        <option value="{{ $b->barang_id }}" data-harga="{{ $b->harga_jual }}">
                                            {{ $b->barang_kode }} - {{ $b->barang_nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" name="harga[]" class="form-control harga" readonly>
                            </td>
                            <td>
                                <input type="number" name="jumlah[]" class="form-control jumlah" min="1" value="1" required>
                            </td>
                            <td>
                                <input type="number" name="subtotal[]" class="form-control subtotal" readonly>
                            </td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm removeRow">-</button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="text-right mt-3">
                    <strong>Total: <span id="total" class="text-primary">Rp 0</span></strong>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Simpan Transaksi</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
            </div>
        </div>
    </div>
</form>

<script>
    $('#form-penjualan').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: $(this).attr('action'),
            method: "POST",
            data: $(this).serialize(),
            success: function (res) {
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: res.message,
                    }).then(() => {
                        location.reload(); // atau reset form
                    });
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Peringatan',
                        text: res.message,
                    });
                }
            },
            error: function (xhr) {
                let msg = xhr.responseJSON?.message || 'Terjadi kesalahan.';
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: msg,
                });
            }
        });
    });
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
});
</script>
