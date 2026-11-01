<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;


class Certificado extends Model
{
    use HasFactory;

    protected $table = 'certificados';
    protected $primaryKey = 'id_certificado';
    protected $fillable = ['codigo', 'nombre', 'descripcion', 'imagen', 'tipo_mime', 'archivo_pdf', 'fecha_emision', 'fecha_vencimiento', 'organismo', 'url_organismo', 'id_admin'];
    protected $casts = ['fecha_emision' => 'date', 'fecha_vencimiento' => 'date'];

    public bool $codigoProvisional = false;

    // El código no se pide en el formulario: se asigna CERT-0001, CERT-0002... según el id.
    protected static function booted(): void
    {
        static::creating(function (Certificado $c) {
            if (blank($c->codigo)) {
                $c->codigo = 'TMP-' . Str::uuid();
                $c->codigoProvisional = true;
            }
        });
        static::created(function (Certificado $c) {
            if ($c->codigoProvisional) {
                $c->codigo = 'CERT-' . str_pad((string) $c->id_certificado, 4, '0', STR_PAD_LEFT);
                $c->saveQuietly();
            }
        });
    }

    // Vigente: sin Fecha de Vencimiento o con una que aún no se cumple.
    public function scopeVigentes(Builder $q): Builder
    {
        $hoy = Carbon::today()->toDateString();
        return $q->where(fn ($w) => $w->whereNull('fecha_vencimiento')->orWhere('fecha_vencimiento', '>=', $hoy));
    }

    public function getEstadoAttribute(): string
    {
        return $this->fecha_vencimiento && $this->fecha_vencimiento->lt(Carbon::today()) ? 'vencido' : 'vigente';
    }

    public function administrador() { return $this->belongsTo(Administrador::class, 'id_admin', 'id_admin'); }
}
