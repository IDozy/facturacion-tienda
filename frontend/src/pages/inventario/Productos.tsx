import { useCallback, useEffect, useMemo, useState } from 'react';
import { AlertTriangle, Circle, Package, Search, Filter, RefreshCw } from 'lucide-react';
import productoService from '@/services/productoService';
import type { Producto, ProductoFilters, ProductoResumen } from '@/types/Producto';
import type { PaginatedResponse } from '@/types/Almacen';
import { toast } from 'react-hot-toast';

const initialResumen: ProductoResumen = {
  total: 0,
  activos: 0,
  inactivos: 0,
  bajo_stock: 0,
  stock_total: 0,
  valor_inventario: 0,
};

export default function ProductosPage() {
  const [productos, setProductos] = useState<Producto[]>([]);
  const [resumen, setResumen] = useState<ProductoResumen>(initialResumen);
  const [loading, setLoading] = useState(false);

  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [perPage, setPerPage] = useState(10);

  const [filters, setFilters] = useState<ProductoFilters>({
    search: '',
    sort_by: 'nombre',
    sort_order: 'asc',
  });

  const debouncedSearch = useMemo(() => filters.search, [filters.search]);

  const loadProductos = useCallback(async () => {
    try {
      setLoading(true);
      const response = (await productoService.getProductos({
        ...filters,
        page: currentPage,
        per_page: perPage,
      })) as PaginatedResponse<Producto>;

      setProductos(response.data);
      setLastPage(response.last_page);
      setCurrentPage(response.current_page);
    } catch (error: unknown) {
      const message =
        (error as { response?: { data?: { message?: string } } })?.response?.data?.message ||
        'No se pudieron cargar los productos';
      toast.error(message);
    } finally {
      setLoading(false);
    }
  }, [currentPage, filters, perPage]);

  useEffect(() => {
    const timer = setTimeout(() => {
      loadProductos();
    }, 300);

    return () => clearTimeout(timer);
  }, [debouncedSearch, filters.estado, filters.bajo_stock, currentPage, perPage, loadProductos]);

  useEffect(() => {
    loadResumen();
  }, []);

  const loadResumen = async () => {
    try {
      const data = await productoService.getResumen();
      setResumen(data);
    } catch (error) {
      console.error(error);
    }
  };

  const handleSearchChange = (value: string) => {
    setFilters((prev) => ({ ...prev, search: value }));
    setCurrentPage(1);
  };

  const handleEstadoChange = (value: 'activo' | 'inactivo' | '') => {
    setFilters((prev) => ({ ...prev, estado: value || undefined }));
    setCurrentPage(1);
  };

  const handleToggleBajoStock = () => {
    setFilters((prev) => ({ ...prev, bajo_stock: !prev.bajo_stock }));
    setCurrentPage(1);
  };

  const handleToggleEstado = async (producto: Producto) => {
    try {
      const response = await productoService.toggleEstado(producto.id);
      toast.success(response.message || 'Estado actualizado');
      await Promise.all([loadProductos(), loadResumen()]);
    } catch (error: unknown) {
      const message =
        (error as { response?: { data?: { message?: string } } })?.response?.data?.message ||
        'No se pudo actualizar el estado';
      toast.error(message);
    }
  };

  const estadoBadge = (estado: string) =>
    estado === 'activo' ? (
      <span className="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full">
        <Circle className="w-3 h-3 fill-green-500 text-green-500" /> Activo
      </span>
    ) : (
      <span className="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold text-amber-700 bg-amber-100 rounded-full">
        <Circle className="w-3 h-3 fill-amber-500 text-amber-500" /> Inactivo
      </span>
    );

  return (
    <div className="p-6 max-w-7xl mx-auto">
      <div className="flex items-center justify-between mb-6">
        <div className="flex items-center gap-3">
          <div className="p-3 bg-indigo-100 rounded-lg">
            <Package className="w-6 h-6 text-indigo-600" />
          </div>
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Productos</h1>
            <p className="text-sm text-gray-500">Gestiona tu catálogo de productos y monitorea su stock</p>
          </div>
        </div>
        <button
          onClick={() => loadProductos()}
          className="flex items-center gap-2 px-4 py-2 text-sm bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50"
        >
          <RefreshCw className="w-4 h-4" />
          Actualizar
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
          <p className="text-sm text-gray-500">Total productos</p>
          <p className="text-2xl font-bold text-gray-900">{resumen.total}</p>
        </div>
        <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
          <p className="text-sm text-gray-500">Activos</p>
          <p className="text-2xl font-bold text-green-600">{resumen.activos}</p>
        </div>
        <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">Bajo stock</p>
              <p className="text-2xl font-bold text-amber-600">{resumen.bajo_stock}</p>
            </div>
            <AlertTriangle className="w-6 h-6 text-amber-500" />
          </div>
        </div>
        <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
          <p className="text-sm text-gray-500">Valor inventario</p>
          <p className="text-2xl font-bold text-indigo-600">
            S/. {resumen.valor_inventario.toLocaleString('es-PE', { minimumFractionDigits: 2 })}
          </p>
        </div>
      </div>

      <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-4">
        <div className="flex items-center gap-2 mb-3">
          <Filter className="w-4 h-4 text-gray-500" />
          <h3 className="font-semibold text-gray-700">Filtros</h3>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
          <div className="col-span-2 flex items-center gap-2">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4" />
              <input
                type="text"
                placeholder="Buscar por código o nombre"
                value={filters.search || ''}
                onChange={(e) => handleSearchChange(e.target.value)}
                className="w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
            </div>
          </div>

          <div className="flex items-center gap-2">
            <label className="text-sm text-gray-600">Estado</label>
            <select
              value={filters.estado || ''}
              onChange={(e) => handleEstadoChange(e.target.value as 'activo' | 'inactivo' | '')}
              className="flex-1 px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
              <option value="">Todos</option>
              <option value="activo">Activos</option>
              <option value="inactivo">Inactivos</option>
            </select>
          </div>

          <div className="flex items-center gap-2">
            <label className="text-sm text-gray-600">Bajo stock</label>
            <button
              onClick={handleToggleBajoStock}
              className={`px-3 py-2 rounded-lg border ${
                filters.bajo_stock ? 'bg-amber-100 border-amber-300 text-amber-800' : 'bg-white border-gray-200'
              }`}
            >
              {filters.bajo_stock ? 'Solo bajo stock' : 'Incluir todos'}
            </button>
          </div>
        </div>
      </div>

      <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Código</th>
                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nombre</th>
                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Categoría</th>
                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Stock</th>
                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Precio venta</th>
                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado</th>
                <th className="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {productos.map((producto) => (
                <tr key={producto.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 text-sm text-gray-900 font-medium">{producto.codigo}</td>
                  <td className="px-4 py-3 text-sm text-gray-700">
                    <div className="flex flex-col">
                      <span className="font-semibold text-gray-900">{producto.nombre}</span>
                      {producto.descripcion && <span className="text-xs text-gray-500">{producto.descripcion}</span>}
                    </div>
                  </td>
                  <td className="px-4 py-3 text-sm text-gray-700">{producto.categoria?.nombre || 'Sin categoría'}</td>
                  <td className="px-4 py-3 text-sm text-gray-700">
                    <div className="flex items-center gap-2">
                      <span className="font-semibold">{producto.stock_actual ?? 0}</span>
                      {producto.es_bajo_stock && (
                        <span className="inline-flex items-center gap-1 text-xs text-amber-700 bg-amber-100 px-2 py-1 rounded-full">
                          <AlertTriangle className="w-3 h-3" /> Bajo stock
                        </span>
                      )}
                    </div>
                  </td>
                  <td className="px-4 py-3 text-sm text-gray-700">S/. {producto.precio_venta}</td>
                  <td className="px-4 py-3 text-sm text-gray-700">{estadoBadge(producto.estado)}</td>
                  <td className="px-4 py-3 text-sm text-right">
                    <button
                      onClick={() => handleToggleEstado(producto)}
                      className="px-3 py-2 text-xs font-semibold border border-gray-200 rounded-lg hover:bg-gray-50"
                    >
                      {producto.estado === 'activo' ? 'Desactivar' : 'Activar'}
                    </button>
                  </td>
                </tr>
              ))}

              {!loading && productos.length === 0 && (
                <tr>
                  <td colSpan={7} className="px-4 py-6 text-center text-sm text-gray-500">
                    No se encontraron productos con los filtros seleccionados.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>

        {loading && (
          <div className="p-4 text-center text-sm text-gray-500">Cargando productos...</div>
        )}

        <div className="flex items-center justify-between px-4 py-3 bg-gray-50 border-t border-gray-200">
          <div className="flex items-center gap-2 text-sm text-gray-600">
            <span>Mostrando página {currentPage} de {lastPage}</span>
            <select
              value={perPage}
              onChange={(e) => {
                setPerPage(Number(e.target.value));
                setCurrentPage(1);
              }}
              className="ml-2 px-3 py-1 border border-gray-200 rounded-lg"
            >
              {[10, 15, 25, 50].map((option) => (
                <option key={option} value={option}>{option} por página</option>
              ))}
            </select>
          </div>

          <div className="flex items-center gap-2">
            <button
              onClick={() => setCurrentPage((prev) => Math.max(1, prev - 1))}
              disabled={currentPage === 1}
              className="px-3 py-2 text-sm border border-gray-200 rounded-lg disabled:opacity-50"
            >
              Anterior
            </button>
            <button
              onClick={() => setCurrentPage((prev) => Math.min(lastPage, prev + 1))}
              disabled={currentPage === lastPage}
              className="px-3 py-2 text-sm border border-gray-200 rounded-lg disabled:opacity-50"
            >
              Siguiente
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
