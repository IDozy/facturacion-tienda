<?php
// app/Models/Empresa.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Empresa extends Model {
    use SoftDeletes;
    protected $fillable = ['ruc', 'razon_social', 'nombre_comercial', 'direccion', 'urbanizacion', 'distrito', 'provincia', 'departamento', 'ubigeo', 'telefono', 'email', 'web', 'usuario_sol', 'clave_sol', 'certificado_digital', 'clave_certificado', 'modo_prueba', 'logo', 'activo'];
    
    public function usuarios() { return $this->hasMany(User::class); }
    public function clientes() { return $this->hasMany(Cliente::class); }
    public function productos() { return $this->hasMany(Producto::class); }
    public function almacenes() { return $this->hasMany(Almacen::class); }
    public function categorias() { return $this->hasMany(Categoria::class); }
    public function proveedores() { return $this->hasMany(Proveedor::class); }
    public function series() { return $this->hasMany(Serie::class); }
    public function comprobantes() { return $this->hasMany(Comprobante::class); }
    public function compras() { return $this->hasMany(Compra::class); }
    public function recepciones() { return $this->hasMany(Recepcion::class); }
    public function movimientos() { return $this->hasMany(MovimientoStock::class); }
    public function planCuentas() { return $this->hasMany(PlanCuenta::class); }
    public function asientos() { return $this->hasMany(Asiento::class); }
    public function auditorias() { return $this->hasMany(Auditoria::class); }
}