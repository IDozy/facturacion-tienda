import { useEffect, useState } from 'react';
import { Package, Users, FileText, TrendingUp } from 'lucide-react';
import api from '../services/api';

export default function Dashboard() {
  const [stats, setStats] = useState({
    productos: 0,
    clientes: 0,
    ventas: 0,
    users: 0,
  });

  useEffect(() => {
    loadStats();
  }, []);

  const loadStats = async () => {
    try {
      const [productosRes, clientesRes, ventasRes, userRes] = await Promise.allSettled([
        api.get('/inventario/productos'),
        api.get('/clientes'),
        api.get('/facturacion/comprobantes-estadisticas'),
        api.get("/users")
      ]);

      // Extrae el total de productos
      const productosTotal = productosRes.status === 'fulfilled'
        ? productosRes.value.data.total || 0
        : 0;

      // Extrae el total de clientes
      const clientesTotal = clientesRes.status === 'fulfilled'
        ? clientesRes.value.data.total || 0
        : 0;

      // Extrae el total de ventas (si el endpoint existe)
      const ventasTotal = ventasRes.status === 'fulfilled'
        ? ventasRes.value.data.total_facturado || 0
        : 0;

      const usersTotal = userRes.status === 'fulfilled' ? userRes.value.data.total || 0 : 0;

      setStats({
        productos: productosTotal,
        clientes: clientesTotal,
        ventas: ventasTotal,
        users: usersTotal,
      });

    } catch (error) {
      console.error('❌ Error cargando estadísticas:', error);
    }
  };

  return (
    <div>
      <h1 className="text-3xl font-bold text-gray-800 mb-8">Dashboard</h1>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {/* Card Productos */}
        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-500 text-sm">Productos</p>
              <p className="text-3xl font-bold text-gray-800">{stats.productos}</p>
            </div>
            <div className="bg-blue-100 p-3 rounded-full">
              <Package className="w-6 h-6 text-blue-600" />
            </div>
          </div>
        </div>

        {/* Card Clientes */}
        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-500 text-sm">Clientes</p>
              <p className="text-3xl font-bold text-gray-800">{stats.clientes}</p>
            </div>
            <div className="bg-green-100 p-3 rounded-full">
              <Users className="w-6 h-6 text-green-600" />
            </div>
          </div>
        </div>

        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-500 text-sm">Usarios</p>
              <p className="text-3xl font-bold text-gray-800">{stats.users}</p>
            </div>
            <div className="bg-green-100 p-3 rounded-full">
              <Users className="w-6 h-6 text-green-600" />
            </div>
          </div>
        </div>

        {/* Card Ventas */}
        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-500 text-sm">Ventas</p>
              <p className="text-3xl font-bold text-gray-800">S/. { stats.ventas}</p>
            </div>
            <div className="bg-purple-100 p-3 rounded-full">
              <FileText className="w-6 h-6 text-purple-600" />
            </div>
          </div>
        </div>

        {/* Card Total */}
        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-500 text-sm">Total Hoy</p>
              <p className="text-3xl font-bold text-gray-800">S/ 0.00</p>
            </div>
            <div className="bg-yellow-100 p-3 rounded-full">
              <TrendingUp className="w-6 h-6 text-yellow-600" />
            </div>
          </div>
        </div>
      </div>

      {/* Bienvenida */}
      <div className="mt-8 bg-white p-6 rounded-lg shadow">
        <h2 className="text-xl font-semibold text-gray-800 mb-2">
          ¡Bienvenido al Sistema de Facturación!
        </h2>
        <p className="text-gray-600">
          Selecciona una opción del menú lateral para comenzar.
        </p>
      </div>
    </div>
  );
}