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
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Yajra\DataTables\Facades\DataTables;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


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

    try {
        $penjualan = PenjualanModel::create([
            'user_id' => $request->user_id,
            'pembeli' => $request->pembeli,
            'penjualan_kode' => 'PJ-' . strtoupper(Str::random(6)),
            'penjualan_tanggal' => $request->penjualan_tanggal,
        ]);

        $dataDetail = [];
        foreach ($request->barang_id as $i => $barangId) {
            $dataDetail[] = new DetailPenjualanModel([
                'barang_id' => $barangId,
                'harga' => $request->harga[$i],
                'jumlah' => $request->jumlah[$i],
            ]);
        }

        $penjualan->detail()->saveMany($dataDetail);

        return response()->json([
            'status' => 'success',
            'message' => 'Transaksi berhasil disimpan.',
        ]);
    } catch (QueryException $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Gagal menyimpan transaksi. ' . $e->getMessage(),
        ], 500);
    }
}
public function edit_ajax($id)
{
    // Ambil data penjualan berdasarkan ID
    $penjualan = PenjualanModel::find($id);
    
    if (!$penjualan) {
        return response()->json([
            'status' => false,
            'message' => 'Data penjualan tidak ditemukan.'
        ]);
    }

    // Data referensi untuk form edit
    $barang = BarangModel::select('barang_id', 'barang_kode', 'barang_nama', 'harga_jual')->get();
    $user = UserModel::select('user_id', 'nama', 'username')->get();

    // Kembalikan view modal edit
    return view('penjualan.edit_ajax')
                ->with('barang', $barang)
                ->with('user', $user)
                ->with('penjualan', $penjualan);
}

public function update_ajax(Request $request, $id)
{
    if (!($request->ajax() || $request->wantsJson())) {
        return redirect('/');
    }

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
    

    try {
        $penjualan = PenjualanModel::find($id);

        if (!$penjualan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data penjualan tidak ditemukan.',
            ], 404);
        }

        $penjualan->update([
            'user_id' => $request->user_id,
            'pembeli' => $request->pembeli,
            'penjualan_tanggal' => $request->penjualan_tanggal,
        ]);

        // Hapus semua detail sebelumnya lalu tambah ulang
        $penjualan->detail()->delete();

        $dataDetail = [];
        foreach ($request->barang_id as $i => $barangId) {
            $dataDetail[] = new DetailPenjualanModel([
                'barang_id' => $barangId,
                'harga' => $request->harga[$i],
                'jumlah' => $request->jumlah[$i],
            ]);
        }

        $penjualan->detail()->saveMany($dataDetail);

        return response()->json([
            'status' => 'success',
            'message' => 'Data penjualan berhasil diperbarui.',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Gagal memperbarui data. ' . $e->getMessage(),
        ], 500);
    }
}
public function confirm_ajax(string $id) {
    // Mengambil data penjualan beserta detail dan barang yang terkait
    $penjualan = PenjualanModel::with(['detail.barang'])->find($id);

    // Cek jika data penjualan tidak ditemukan
    if (!$penjualan) {
        return response()->json([
            'status' => false,
            'message' => 'Data penjualan tidak ditemukan'
        ], 404);
    }

    // Mengembalikan tampilan konfirmasi dengan data penjualan
    return view('penjualan.confirm_ajax', ['penjualan' => $penjualan]);
}
public function delete_ajax(Request $request, $id)
{
    if ($request->ajax() || $request->wantsJson()) {
        $penjualan = PenjualanModel::find($id);

        if (!$penjualan) {
            return response()->json([
                'status' => false,
                'message' => 'Data penjualan tidak ditemukan'
            ]);
        }

        // Menghapus detail penjualan terlebih dahulu
        try {
            // Menghapus semua detail penjualan terkait
            foreach ($penjualan->detail as $detail) {
                $detail->delete();
            }

            // Menghapus penjualan
            $penjualan->delete();

            return response()->json([
                'status' => true,
                'message' => 'Data penjualan berhasil dihapus beserta detailnya'
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Data penjualan gagal dihapus karena masih terdapat tabel lain yang terkait dengan data ini'
            ]);
        }
    }

    return redirect('/');
}
public function destroy(string $id)
{
    $penjualan = PenjualanModel::find($id);

    if (!$penjualan) {
        return redirect('/penjualan')->with('error', 'Data penjualan tidak ditemukan');
    }

    // Menghapus detail penjualan terlebih dahulu
    try {
        // Menghapus semua detail penjualan terkait
        foreach ($penjualan->detail as $detail) {
            $detail->delete();
        }

        // Menghapus penjualan setelah detailnya dihapus
        $penjualan->delete();

        return redirect('/penjualan')->with('success', 'Data penjualan berhasil dihapus beserta detailnya');
    } catch (\Illuminate\Database\QueryException $e) {
        return redirect('/penjualan')->with('error', 'Data penjualan gagal dihapus karena masih terdapat tabel lain yang terkait dengan data ini');
    }
}

public function export_excel()
{
    $penjualan = PenjualanModel::with(['detail', 'detail.barang'])
                               ->orderBy('penjualan_tanggal')
                               ->get();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set header row
    $sheet->setCellValue('A1', 'No');
    $sheet->setCellValue('B1', 'Kode Penjualan');
    $sheet->setCellValue('C1', 'Tanggal Penjualan');
    $sheet->setCellValue('D1', 'Barang Kode');
    $sheet->setCellValue('E1', 'Barang Nama');
    $sheet->setCellValue('F1', 'Jumlah');
    $sheet->setCellValue('G1', 'Harga');
    $sheet->setCellValue('H1', 'Total Harga');

    // Apply bold font to header row
    $sheet->getStyle("A1:H1")->getFont()->setBold(true);

    $no = 1;
    $baris = 2;
    foreach ($penjualan as $penjualanItem) {
        foreach ($penjualanItem->detail as $detail) {
            $sheet->setCellValue("A" . $baris, $no);
            $sheet->setCellValue('B' . $baris, $penjualanItem->penjualan_kode);
            $sheet->setCellValue("C" . $baris, $penjualanItem->penjualan_tanggal);
            $sheet->setCellValue('D' . $baris, $detail->barang->barang_kode);
            $sheet->setCellValue("E" . $baris, $detail->barang->barang_nama);
            $sheet->setCellValue('F' . $baris, $detail->jumlah);
            $sheet->setCellValue("G" . $baris, $detail->harga);
            $sheet->setCellValue('H' . $baris, $detail->jumlah * $detail->harga);
            $baris++;
            $no++;
        }
    }

    // Auto size columns
    foreach (range('A', 'H') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    $sheet->setTitle("Data Penjualan");

    // Write to output
    $writer = new Xlsx($spreadsheet);
    $filename = 'Data_Penjualan_' . date("Y-m-d_H-i-s") . '.xlsx';

    // Set headers to download the file
    return response()->stream(
        function () use ($writer) {
            $writer->save('php://output');
        },
        200,
        [
            "Content-Type" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "Content-Disposition" => "attachment; filename=$filename",
            "Cache-Control" => "max-age=1", // Tentukan hanya satu nilai di sini
            "Expires" => "Mon, 26 Jul 1997 05:00:00 GMT",
            "Last-Modified" => gmdate('D, d M Y H:i:s') . ' GMT',
            "Pragma" => "public"
        ]
    );
}


public function export_pdf()
{
    $penjualan = PenjualanModel::with(['detail', 'detail.barang'])
                               ->orderBy('penjualan_tanggal')
                               ->get();

    // Memuat tampilan PDF
    $pdf = Pdf::loadView('penjualan.export_pdf', ['penjualan' => $penjualan]);

    // Mengatur ukuran dan orientasi kertas
    $pdf->setPaper('a4', 'portrait'); // Bisa diganti 'landscape' jika diperlukan

    // Opsional: Mengaktifkan gambar remote jika perlu
    $pdf->setOption("isRemoteEnabled", true);

    // Menyediakan file PDF untuk diunduh
    return $pdf->stream('Data_Penjualan_' . date('Y-m-d_H-i-s') . '.pdf');
}

}