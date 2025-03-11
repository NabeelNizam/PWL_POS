<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierModel extends Model
{
    protected $table = 'm_supplier';
    protected $primaryKey = 'supplier_id';
    
    protected $fillable = [
        'supplier_kode', 
        'supplier_nama',
        'supplier_alamat'
    ];
}
