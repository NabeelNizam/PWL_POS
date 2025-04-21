<?php

namespace App\Http\Controllers;

use App\Models\PenjualanModel;
use Illuminate\Http\Request;

use App\Models\BarangModel;
use App\Models\DetailPenjualanModel;
use Illuminate\Support\Str;
use App\Models\StokModel;
use App\Models\SupplierModel;
use App\Models\UserModel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Yajra\DataTables\Facades\DataTables;
use PhpOffice\PhpSpreadsheet\Shared\Date;


class PenjualanController extends Controller
{
    public function index()
    {
        $breadcrumb = (object) [
            'title' => 'Daftar Penjualan',
            'list' => ['Home', 'Penjualan']
        ];

        $page = (object) [
            'title' => 'Daftar Penjualan Barang yang terdaftar dalam sistem'
        ];

        $activeMenu = 'penjualan';

        $penjualan = PenjualanModel::all();

        return view('penjualan.index', ['breadcrumb' => $breadcrumb, 'page' => $page, 'penjualan' => $penjualan, 'activeMenu' => $activeMenu]);
    }
    public function list(Request $request)
{
    $penjualan = PenjualanModel::select('penjualan_id', 'penjualan_kode', 'pembeli', 'penjualan_tanggal', 'user_id')
        ->with('user'); // Relasi ke m_user

    $user_id = $request->input('filter_user');
    if (!empty($user_id)) {
        $penjualan->where('user_id', $user_id);
    }

    return DataTables::of($penjualan)
        ->addIndexColumn()
        ->addColumn('aksi', function ($penjualan) {
            $btn = '<button onclick="modalAction(\'' . url('/penjualan/' . $penjualan->penjualan_id . '/show_ajax') . '\')" class="btn btn-info btn-sm">Detail</button>';
            $btn .= '<button onclick="modalAction(\'' . url('/penjualan/' . $penjualan->penjualan_id . '/edit_ajax') . '\')" class="btn btn-warning btn-sm">Edit</button>';
            $btn .= '<button onclick="modalAction(\'' . url('/penjualan/' . $penjualan->penjualan_id . '/delete_ajax') . '\')" class="btn btn-danger btn-sm">Hapus</button>';
            return $btn;
        })
        ->rawColumns(['aksi'])
        ->make(true);
}
public function show_ajax(string $id)
{
    $penjualan = PenjualanModel::with(['user', 'detail.barang'])->find($id);

    return view('penjualan.show_ajax', ['penjualan' => $penjualan]);
}
public function create_ajax() {
    $barang = BarangModel::select('barang_id', 'barang_kode', 'barang_nama', 'harga_jual')->get();
    $user = UserModel::select('user_id', 'nama', 'username')->get();

    return view('penjualan.create_ajax')
                ->with('barang', $barang)
                ->with('user', $user);
}

public function store_ajax(Request $request)
    {
        $request->validate([
            'pembeli' => 'required|string|max:255',
            'user_id' => 'required|exists:m_user,user_id',
            'penjualan_tanggal' => 'required|date',
            'barang_id' => 'required|array|min:1',
            'barang_id.*' => 'required|exists:m_barang,barang_id',
            'harga' => 'required|array',
            'harga.*' => 'required|numeric|min:0',
            'jumlah' => 'required|array',
            'jumlah.*' => 'required|integer|min:1',
        ]);

        // Buat data penjualan
        $penjualan = PenjualanModel::create([
            'user_id' => $request->user_id,
            'pembeli' => $request->pembeli,
            'penjualan_kode' => 'PJ-' . strtoupper(Str::random(6)),
            'penjualan_tanggal' => $request->penjualan_tanggal,
        ]);

        // Masukkan semua detail barang
        $dataDetail = [];
        foreach ($request->barang_id as $i => $barangId) {
            $dataDetail[] = new DetailPenjualanModel([
                'barang_id' => $barangId,
                'harga' => $request->harga[$i],
                'jumlah' => $request->jumlah[$i],
            ]);
        }

        // Simpan relasi detail sekaligus
        $penjualan->detail()->saveMany($dataDetail);

        return response()->json([
            'status' => 'success',
            'message' => 'Transaksi berhasil disimpan.',
            'penjualan_id' => $penjualan->penjualan_id,
        ]);
    }

}