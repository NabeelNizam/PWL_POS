<?php

namespace App\Http\Controllers;

use App\Models\BarangModel;
use App\Models\StokModel;
use App\Models\SupplierModel;
use App\Models\UserModel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Yajra\DataTables\Facades\DataTables;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class StokController extends Controller
{
    public function index()
    {
        $breadcrumb = (object) [
            'title' => 'Daftar Stok',
            'list' => ['Home', 'Stok']
        ];

        $page = (object) [
            'title' => 'Daftar Stok Barang yang terdaftar dalam sistem'
        ];

        $activeMenu = 'stok';

        $stok = StokModel::all();

        return view('stok.index', ['breadcrumb' => $breadcrumb, 'page' => $page, 'stok' => $stok, 'activeMenu' => $activeMenu]);
    }

    public function list(Request $request)
    {
        $stok = StokModel::select('stok_id','barang_id','user_id','supplier_id','stok_tanggal','stok_jumlah')->with('barang', 'user', 'supplier');


        return DataTables::of($stok)
            ->addIndexColumn()
            ->addColumn('jumlah', function ($s) {
                $btn = '<div class="d-flex justify-content-center align-items-center gap-1" style="min-width: 150px;">';

                $btn .= '<button onclick="updateJumlah(' . $s->stok_id . ', -1)" class="btn btn-sm btn-secondary">-</button>';

                $btn .= '<span 
                            class="mx-2 editable-stok" 
                            data-id="' . $s->stok_id . '" 
                            data-old="' . $s->stok_jumlah . '" 
                            style="min-width: 40px; display: inline-block; cursor: pointer;">
                            ' . $s->stok_jumlah . '
                        </span>';

                $btn .= '<button onclick="updateJumlah(' . $s->stok_id . ', 1)" class="btn btn-sm btn-secondary">+</button>';

                $btn .= '<button onclick="resetStok(' . $s->stok_id . ')" class="btn btn-sm btn-danger ml-2" title="Reset stok ke 0"><i class="fa fa-sync"></i></button>';


                $btn .= '</div>';

                return $btn;
            })

            ->addColumn('aksi', function ($stok) {
                //     $btn = '<a href="' . url('/level/' . $level->level_id) . '" class="btn btn-info btn-sm">Detail</a> ';
                //     $btn .= '<a href="' . url('/level/' . $level->level_id . '/edit') . '" class="btn btn-warning btn-sm">Edit</a> ';
                //     $btn .= '<form class="d-inline-block" method="POST" action="' . url('/level/' . $level->level_id) . '">'
                //         . csrf_field() . method_field('DELETE') .
                //         '<button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Apakah Anda yakin menghapus data ini?\')">Hapus</button>
                // </form>';
                $btn = '<button onclick="modalAction(\'' . url('/stok/' . $stok->stok_id . '/show_ajax') . '\')" class="btn btn-info btn-sm">Detail</button>';
                $btn .= '<button onclick="modalAction(\'' . url('/stok/' . $stok->stok_id . '/edit_ajax') . '\')" class="btn btn-warning btn-sm">Edit</button>';
                $btn .= '<button onclick="modalAction(\'' . url('/stok/' . $stok->stok_id . '/delete_ajax') . '\')" class="btn btn-danger btn-sm">Hapus</button>';

                return $btn;
            })
            ->rawColumns(['jumlah', 'aksi'])
            ->make(true);
    }
    public function updateJumlah(Request $request)
    {
        $request->validate([
            'stok_id' => 'required|integer|exists:t_stok,stok_id',
            'perubahan' => 'required|integer'
        ]);

        $stok = StokModel::find($request->stok_id);
        $stok->stok_jumlah += $request->perubahan;

        if ($stok->stok_jumlah < 0) {
            return response()->json(['success' => false, 'message' => 'Stok tidak boleh negatif']);
        }

        $stok->save();

        return response()->json(['success' => true]);
    }
    public function updateJumlahManual(Request $request)
    {
        $request->validate([
            'stok_id' => 'required|exists:t_stok,stok_id',
            'stok_jumlah' => 'required|integer|min:0'
        ]);

        try {
            $stok = StokModel::find($request->stok_id);
            $stok->stok_jumlah = $request->stok_jumlah;
            $stok->save();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan perubahan.']);
        }
    }
    public function resetJumlah(Request $request)
    {
        $stok = StokModel::find($request->stok_id);

        if (!$stok) {
            return response()->json(['success' => false, 'message' => 'Data stok tidak ditemukan']);
        }

        $stok->stok_jumlah = 0;
        $stok->save();

        return response()->json(['success' => true, 'message' => 'Stok berhasil di-reset']);
    }

    public function import()
    {
        return view('stok.import');
    }
    public function import_ajax(Request $request)
    {
        try {
            if ($request->ajax() || $request->wantsJson()) {
                $rules = [
                    'f' => ['required', 'mimes:xlsx', 'max:1024']
                ];

                $validator = Validator::make($request->all(), $rules);
                if ($validator->fails()) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Validasi Gagal',
                        'msgField' => $validator->errors()
                    ]);
                }

                $file = $request->file('f');

                $reader = IOFactory::createReader('Xlsx');
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($file);
                $sheet = $spreadsheet->getActiveSheet();
                $data = $sheet->toArray(null, false, true, true);

                $insert = [];
                if (count($data) > 1) {
                    foreach ($data as $baris => $value) {
                        if ($baris > 1 && !empty($value['A']) && !empty($value['B']) && !empty($value['C'])) {
                            $insert[] = [
                                'barang_id'    => $value['A'],
                                'user_id'      => $value['B'],
                                'supplier_id'  => $value['C'],
                                'stok_tanggal' => is_numeric($value['D'])
                                    ? Date::excelToDateTimeObject($value['D'])->format('Y-m-d')
                                    : date('Y-m-d', strtotime($value['D'])),
                                'stok_jumlah'  => (int) $value['E'],
                                'created_at'   => now(),
                            ];
                        }
                    }

                    if (count($insert) > 0) {
                        StokModel::insertOrIgnore($insert);
                    }

                    return response()->json([
                        'status' => true,
                        'message' => 'Data stok berhasil diimport'
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Tidak ada data stok yang diimport'
                    ]);
                }
            }

            return redirect('/stok');
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi error: ' . $e->getMessage()
            ], 500);
        }
    }
    public function export_excel()
    {
        $stok = StokModel::select("barang_id", "user_id", "supplier_id", "stok_tanggal", "stok_jumlah")
                        ->orderBy('barang_id')
                        ->with(['barang', 'user', 'supplier'])
                        ->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Barang');
        $sheet->setCellValue('C1', 'User');
        $sheet->setCellValue('D1', 'Supplier');
        $sheet->setCellValue('E1', 'Tanggal Stok');
        $sheet->setCellValue('F1', 'Jumlah');

        $sheet->getStyle("A1:F1")->getFont()->setBold(true);

        $no = 1;
        $baris = 2;
        foreach ($stok as $key => $value) {
            $sheet->setCellValue("A".$baris, $no);
            $sheet->setCellValue('B'.$baris, $value->barang->barang_nama ?? '');
            $sheet->setCellValue("C".$baris, $value->user->nama ?? '');
            $sheet->setCellValue('D'.$baris, $value->supplier->supplier_nama ?? '');
            $sheet->setCellValue("E".$baris, $value->stok_tanggal);
            $sheet->setCellValue('F'.$baris, $value->stok_jumlah);
            $baris++;
            $no++;
        }

        foreach (range('A', 'F') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $sheet->setTitle("Data Stok"); // set title sheet

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $filename = 'Data Stok ' . date("Y-m-d H:i:s") . '.xlsx';

        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header("Cache-Control: max-age=0");
        header("Cache-Control: max-age=1");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate('D, d M Y H:i:s') . ' GMT');
        header("Cache-Control: cache, must-revalidate");
        header("Pragma: public");

        $writer->save('php://output');
        exit;
    }
    public function export_pdf()
    {
        $stok = StokModel::select('barang_id', 'user_id', 'supplier_id', 'stok_tanggal', 'stok_jumlah')
                ->orderBy('barang_id')
                ->orderBy('stok_tanggal')
                ->with(['barang', 'user', 'supplier'])
                ->get();

        $pdf = Pdf::loadView('stok.export_pdf', ['stok' => $stok]);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption("isRemoteEnabled", true);
        $pdf->render();
        $stok = StokModel::with([
            'barang' => function($q) {
                $q->select('barang_id', 'barang_nama');
            },
            'user' => function($q) {
                $q->select('user_id', 'nama');
            },
            'supplier' => function($q) {
                $q->select('supplier_id', 'supplier_nama');
            }
        ])->whereNotNull('stok_tanggal')
        ->where('stok_tanggal', '!=', '0000-00-00')
        ->get();

        return $pdf->stream('Data Stok '.date('Y-m-d H:i:s').'.pdf');
    }
    public function create_ajax() {
        $barang = BarangModel::select('barang_id', 'barang_nama')->get();
        $supplier = SupplierModel::select('supplier_id', 'supplier_nama')->get();
        $user = UserModel::select('user_id', 'nama')->get();

        return view('stok.create_ajax')
                    ->with('barang', $barang)
                    ->with('supplier', $supplier)
                    ->with('user', $user);
    }
    public function store_ajax(Request $request) {
        if ($request->ajax() || $request->wantsJson()) {
            $rules = [
                'barang_id'    => 'required|exists:m_barang,barang_id',
                'supplier_id'  => 'required|exists:m_supplier,supplier_id',
                'stok_tanggal' => 'required|date',
                'stok_jumlah'  => 'required|integer|min:1',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'status'   => false,
                    'message'  => 'Validasi Gagal',
                    'msgField' => $validator->errors(),
                ]);
            }

            // Ambil user_id dari user login
            $data = $request->all();
            $data['user_id'] = auth()->id();

            StokModel::create($data);

            return response()->json([
                'status' => true,
                'message' => 'Data stok berhasil disimpan'
            ]);
        }

        return redirect('/');
    }
    public function show_ajax(string $id)
    {
        $stok = StokModel::find($id);

        return view('stok.show_ajax', ['stok' => $stok]);
    }
    public function edit_ajax(string $id)
    {
        $stok = StokModel::find($id);
        $barang = BarangModel::select('barang_id', 'barang_nama')->get();
        $supplier = SupplierModel::select('supplier_id', 'supplier_nama')->get();

        return view('stok.edit_ajax', ['stok' => $stok, 'barang' => $barang, 'supplier' => $supplier]);
    }
    public function update_ajax(Request $request, $id)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $rules = [
                'barang_id'     => 'required|exists:m_barang,barang_id',
                'supplier_id'   => 'required|exists:m_supplier,supplier_id',
                'stok_tanggal'  => 'required|date',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'status'   => false,
                    'message'  => 'Validasi gagal.',
                    'msgField' => $validator->errors()
                ]);
            }

            $check = StokModel::find($id);
            if ($check) {
                $check->update($request->all());
                return response()->json([
                    'status' => true,
                    'message' => 'Data berhasil diupdate'
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan'
                ]);
            }
        }
        return redirect('/');
    }

    public function destroy(string $id)
    {
        $check = StokModel::find($id);
        if (!$check) {
            return redirect('/stok')->with('error', 'Data stok tidak ditemukan');
        }

        try {
            StokModel::destroy($id);

            return redirect('/stok')->with('success', 'Data stok berhasil dihapus');
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect('/stok')->with('error', 'Data stok gagal dihapus karena masih terdapat tabel lain yang terkait dengan data ini');
        }
    }
    public function confirm_ajax(string $id)
    {
        $stok = StokModel::find($id);

        return view('stok.confirm_ajax', ['stok' => $stok]);
    }
    public function delete_ajax(Request $request, $id)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $stok = StokModel::find($id);
            if (!$stok) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data stok tidak ditemukan'
                ]);
            }

            try {
                $stok->delete();
                return response()->json([
                    'status' => true,
                    'message' => 'Data stok berhasil dihapus'
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data stok gagal dihapus karena masih terdapat tabel lain yang terkait dengan data ini'
                ]);
            }
        }

        return redirect('/');
    }


}
