<?php

namespace App\Http\Controllers;

use App\Models\BarangModel;
use App\Models\KategoriModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB as FacadesDB;
use Yajra\DataTables\Facades\DataTables;

class BarangController extends Controller
{
    public function index(){
        $breadcrumb = (object) [
            'title' => 'Barang',
            'list' => ['Home', 'Barang']
        ];

        $page = (object) [
            'title' => 'Daftar barang yang terdaftar dalam sistem'
        ];

        $activeMenu = 'barang';

        return view('barang.index', compact('breadcrumb', 'page', 'activeMenu'));
    }
    
    public function list(Request $request)
    {
        $barangs = BarangModel::with('kategori')->select('barang_id', 'kategori_id', 'barang_kode', 'barang_nama', 'harga_beli', 'harga_jual');

        return DataTables::of($barangs)
            ->addIndexColumn()
            ->addColumn('kategori', function ($barang) {
                return $barang->kategori->kategori_nama;
            })
            ->addColumn('aksi', function ($barang) {
                $btn = '<a href="'.url('/barang/' . $barang->barang_id).'" class="btn btn-info btn-sm">Detail</a> ';
                $btn .= '<a href="'.url('/barang/' . $barang->barang_id . '/edit').'" class="btn btn-warning btn-sm">Edit</a> ';
                $btn .= '<form class="d-inline-block" method="POST" action="'.url('/barang/'.$barang->barang_id).'">'.
                    csrf_field() . method_field('DELETE') .
                    '<button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Apakah Anda yakin menghapus data ini?\');">Hapus</button></form>';
                return $btn;
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }
    
    public function create()
    {
        $breadcrumb = (object)[
            'title' => 'Tambah Barang',
            'list' => ['Home', 'Barang', 'Tambah']
        ];
        
        $page = (object)[
            'title' => 'Tambah Barang Baru'
        ];
        
        $activeMenu = 'barang';
        $kategoris = KategoriModel::all();
        
        return view('barang.create', compact('breadcrumb', 'page', 'activeMenu', 'kategoris'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kategori_id' => 'required|exists:m_kategori,kategori_id',
            'barang_kode' => 'required|string|max:20|unique:m_barang,barang_kode',
            'barang_nama' => 'required|string|max:100',
            'harga_beli' => 'required|integer|min:0',
            'harga_jual' => 'required|integer|min:0'
        ]);
        
        BarangModel::create($request->all());
        
        return redirect('/barang')->with('success', 'Data barang berhasil disimpan');
    }

    public function show($id)
    {
        $barang = BarangModel::with('kategori')->where('barang_id', $id)->firstOrFail();
        
        $breadcrumb = (object) [
            'title' => 'Detail Barang',
            'list' => ['Home', 'Barang', 'Detail']
        ];
        
        $page = (object) [
            'title' => 'Detail Barang'
        ];
        
        $activeMenu = 'barang';
        
        return view('barang.show', compact('breadcrumb', 'page', 'barang', 'activeMenu'));
    }

    public function edit($id)
    {
        $barang = BarangModel::where('barang_id', $id)->firstOrFail();
        
        $breadcrumb = (object) [
            'title' => 'Edit Barang',
            'list' => ['Home', 'Barang', 'Edit']
        ];
        
        $page = (object) [
            'title' => 'Edit Barang'
        ];
        
        $activeMenu = 'barang';
        $kategoris = KategoriModel::all();
        
        return view('barang.edit', compact('breadcrumb', 'page', 'barang', 'activeMenu', 'kategoris'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'kategori_id' => 'required|exists:m_kategori,kategori_id',
            'barang_kode' => 'required|string|max:20|unique:m_barang,barang_kode,' . $id . ',barang_id',
            'barang_nama' => 'required|string|max:100',
            'harga_beli' => 'required|integer|min:0',
            'harga_jual' => 'required|integer|min:0'
        ]);
        
        BarangModel::find($id)->update($request->all());
        
        return redirect('/barang')->with('success', 'Data barang berhasil diubah');
    }

    public function destroy($id)
    {
        $barang = BarangModel::where('barang_id', $id)->firstOrFail();

        if (!$barang) {
            return redirect('/barang')->with('error', 'Data barang tidak ditemukan.');
        }

        try {
            // Hapus data terkait di t_penjualan_detail
            FacadesDB::table('t_penjualan_detail')->where('barang_id', $id)->delete();
            
            // Hapus barang
            $barang->delete();

            return redirect('/barang')->with('success', 'Data barang dan relasi berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect('/barang')->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }



}
