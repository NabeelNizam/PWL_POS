@empty($penjualan)
    <div id="modal-master" class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kesalahan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <h5><i class="icon fas fa-ban"></i> Kesalahan!!!</h5>
                    Data yang anda cari tidak ditemukan
                </div>
                <a href="{{ url('/penjualan') }}" class="btn btn-warning">Kembali</a>
            </div>
        </div>
    </div>
@else
    <div id="modal-master" class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Penjualan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-sm table-bordered mb-4">
                    <tr>
                        <th class="text-right col-3">ID Penjualan :</th>
                        <td class="col-9">{{ $penjualan->penjualan_id }}</td>
                    </tr>
                    <tr>
                        <th class="text-right">Kode Penjualan :</th>
                        <td>{{ $penjualan->penjualan_kode }}</td>
                    </tr>
                    <tr>
                        <th class="text-right">Tanggal :</th>
                        <td>{{ $penjualan->penjualan_tanggal }}</td>
                    </tr>
                    <tr>
                        <th class="text-right">Pembeli :</th>
                        <td>{{ $penjualan->pembeli }}</td>
                    </tr>
                    <tr>
                        <th class="text-right">User :</th>
                        <td>{{ $penjualan->user->username ?? '-' }}</td>
                    </tr>
                </table>

                <h5>Detail Barang Terjual</h5>
                <table class="table table-bordered table-sm table-striped">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>Harga</th>
                            <th>Jumlah</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 1; $total = 0; @endphp
                        @foreach($penjualan->detail as $detail)
                            <tr>
                                <td>{{ $no++ }}</td>
                                <td>{{ $detail->barang->barang_kode }}</td>
                                <td>{{ $detail->barang->barang_nama }}</td>
                                <td>{{ number_format($detail->barang->harga_jual) }}</td> 
                                <td>{{ $detail->jumlah }}</td>
                                <td>{{ number_format($detail->barang->harga_jual * $detail->jumlah) }}</td>
                                @php $total += $detail->harga * $detail->jumlah; @endphp
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="5" class="text-right font-weight-bold">Total</td>
                            <td class="font-weight-bold">{{ number_format($total) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endempty
