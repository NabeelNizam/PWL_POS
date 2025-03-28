<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StokSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [];

        for ($i = 1; $i <= 15; $i++) {
            $data[] = [
                'supplier_id' => rand(1, 3),
                'barang_id' => $i,
                'user_id' => rand(1, 2),
                'stok_tanggal' => now(),
                'stok_jumlah' => rand(1, 100),
                'created_at' => now(),
            ];
        }

        DB::table('t_stok')->insert($data);
    }
}
