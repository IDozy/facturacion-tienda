<?php

namespace App\Models;


use App\Models\Facturacion\GuiaRemision;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Empresa extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'razon_social',
        'ruc',
        'direccion',
        'telefono',
        'email',
        'certificado_digital',
        'clave_certificado',
        'usuario_sol',
        'clave_sol',
        'modo',
        'fecha_expiracion_certificado',
        'pse_autorizado',

    ];

    protected $casts = [
        'fecha_expiracion_certificado' => 'date',
        'pse_autorizado' => 'boolean',
    ];
    protected $hidden = [
        'certificado_digital',
        'clave_certificado',
        'usuario_sol',
        'clave_sol',
    ];

    // Mutators para encriptar datos sensibles
    public function setCertificadoDigitalAttribute($value)
    {
        if ($value) {
            $this->attributes['certificado_digital'] = Crypt::encryptString($value);
        }
    }

    public function getCertificadoDigitalAttribute($value)
    {
        if ($value) {
            return Crypt::decryptString($value);
        }
        return null;
    }

    public function setClaveCertificadoAttribute($value)
    {
        if ($value) {
            $this->attributes['clave_certificado'] = Crypt::encryptString($value);
        }
    }

    public function getClaveCertificadoAttribute($value)
    {
        if ($value) {
            return Crypt::decryptString($value);
        }
        return null;
    }

    public function setUsuarioSolAttribute($value)
    {
        if ($value) {
            $this->attributes['usuario_sol'] = Crypt::encryptString($value);
        }
    }

    public function getUsuarioSolAttribute($value)
    {
        if ($value) {
            return Crypt::decryptString($value);
        }
        return null;
    }

    public function setClaveSolAttribute($value)
    {
        if ($value) {
            $this->attributes['clave_sol'] = Crypt::encryptString($value);
        }
    }

    public function getClaveSolAttribute($value)
    {
        if ($value) {
            return Crypt::decryptString($value);
        }
        return null;
    }

    // Relaciones
    public function usuarios()
    {
        return $this->hasMany(User::class);
    }

    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }

    public function proveedores()
    {
        return $this->hasMany(Compras\Proveedor::class);
    }

    public function productos()
    {
        return $this->hasMany(Inventario\Producto::class);
    }

    public function comprobantes()
    {
        return $this->hasMany(Facturacion\Comprobante::class);
    }

    public function compras()
    {
        return $this->hasMany(Compras\Compra::class);
    }

    public function almacenes()
    {
        return $this->hasMany(Inventario\Almacen::class);
    }

    public function series()
    {
        return $this->hasMany(Facturacion\Serie::class);
    }

    public function periodos_contables()
    {
        return $this->hasMany(Contabilidad\PeriodoContable::class);
    }

    public function plan_cuentas()
    {
        return $this->hasMany(Contabilidad\PlanCuenta::class);
    }

    public function diarios()
    {
        return $this->hasMany(Contabilidad\Diario::class);
    }

    public function configuracion()
    {
        return $this->hasOne(ConfiguracionEmpresa::class);
    }

    public function guias_remision()
    {
        return $this->hasMany(GuiaRemision::class);
    }

    public function categorias()
    {
        return $this->hasMany(Inventario\Categoria::class);
    }

    // Scopes
    public function scopeModoProduccion($query)
    {
        return $query->where('modo', 'produccion');
    }

    public function scopeModoPrueba($query)
    {
        return $query->where('modo', 'prueba');
    }

    public function scopePseAutorizado($query)
    {
        return $query->where('pse_autorizado', true);
    }

    // Métodos de validación
    public function validarRuc()
    {
        $ruc = $this->ruc;

        if (!is_numeric($ruc) || strlen($ruc) !== 11) {
            return false;
        }

        $factores = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;

        for ($i = 0; $i < 10; $i++) {
            $suma += intval($ruc[$i]) * $factores[$i];
        }

        $residuo = $suma % 11;
        $digito = 11 - $residuo;
        if ($digito === 10) $digito = 0;
        if ($digito === 11) $digito = 1;

        return intval($ruc[10]) === $digito;
    }


    public function certificadoVigente()
    {
        return $this->fecha_expiracion_certificado && $this->fecha_expiracion_certificado->isFuture();
    }

    public static function validarRucValor(string $ruc): bool
{
    if (!is_numeric($ruc) || strlen($ruc) !== 11) {
        return false;
    }

    $factores = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
    $suma = 0;

    for ($i = 0; $i < 10; $i++) {
        $suma += intval($ruc[$i]) * $factores[$i];
    }

    $residuo = $suma % 11;
    $digito = 11 - $residuo;
    if ($digito === 10) $digito = 0;
    if ($digito === 11) $digito = 1;

    return intval($ruc[10]) === $digito;
}

}
