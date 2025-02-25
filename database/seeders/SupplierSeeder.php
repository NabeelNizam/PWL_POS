<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['supplier_kode' => 'SPL001', 'supplier_nama' => 'Agus', 'supplier_alamat' =>'Malang'],
            ['supplier_kode' => 'SPL002', 'supplier_nama' => 'Eli', 'supplier_alamat' =>'Jakarta Pusat'],
            ['supplier_kode' => 'SPL003', 'supplier_nama' => 'Maurin','supplier_alamat' =>'Jakarta TImur'],
        ];

        DB::table('m_supplier')->insert($data);
    }
}
