<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class RecuperacionPassword extends Model
{
    use HasFactory;

    protected $table = 'recuperaciones_password';
    protected $primaryKey = 'id_recuperacion';
    public $timestamps = false;
    protected $fillable = ['id_admin', 'token_hash', 'expira_en', 'usado_en', 'created_at'];
    protected $casts = ['expira_en' => 'datetime', 'usado_en' => 'datetime', 'created_at' => 'datetime'];

    public function administrador() {
        return $this->belongsTo(Administrador::class, 'id_admin', 'id_admin');
    }
}
