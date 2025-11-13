// app/inventario/almacenes/page.tsx
'use client';

import { useState, useEffect } from 'react';
import { Plus, Search, Filter, Warehouse, MapPin, Package, AlertCircle } from 'lucide-react';
import almacenService from '@/services/almacenService';
import  type { Almacen, AlmacenFilters, PaginatedResponse } from '@/types/Almacen';
import AlmacenModal from '@/components/almacen/AlmacenModal';
import AlmacenTable from '@/components/almacen/AlmacenTable';
import { toast } from 'react-hot-toast';

export default function AlmacenesPage() {
  const [almacenes, setAlmacenes] = useState<Almacen[]>([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [selectedAlmacen, setSelectedAlmacen] = useState<Almacen | null>(null);
  
  // Paginación
  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [perPage, setPerPage] = useState(15);

  // Filtros
  const [filters, setFilters] = useState<AlmacenFilters>({
    search: '',
    activo: undefined,
    con_stock: undefined,
    sort_by: 'nombre',
    sort_order: 'asc',
  });

  const [searchInput, setSearchInput] = useState('');

  useEffect(() => {
    loadAlmacenes();
  }, [currentPage, perPage, filters]);

  const loadAlmacenes = async () => {
    try {
      setLoading(true);
      // ✅ Usar el método que filtra por empresa automáticamente
      const response = await almacenService.getAlmacenesPorEmpresa(
        undefined, // undefined = usa empresa_id del usuario autenticado
        {
          ...filters,
          page: currentPage,
          per_page: perPage,
        }
      ) as PaginatedResponse<Almacen>;

      setAlmacenes(response.data);
      setCurrentPage(response.current_page);
      setLastPage(response.last_page);
      setTotal(response.total);
    } catch (error: any) {
      console.error('Error loading almacenes:', error);
      toast.error('Error al cargar los almacenes');
    } finally {
      setLoading(false);
    }
  };

  const handleSearch = () => {
    setFilters({ ...filters, search: searchInput });
    setCurrentPage(1);
  };

  const handleFilterChange = (key: keyof AlmacenFilters, value: any) => {
    setFilters({ ...filters, [key]: value });
    setCurrentPage(1);
  };

  const handleCreate = () => {
    setSelectedAlmacen(null);
    setShowModal(true);
  };

  const handleEdit = (almacen: Almacen) => {
    setSelectedAlmacen(almacen);
    setShowModal(true);
  };

  const handleDelete = async (id: number) => {
    if (!confirm('¿Está seguro de eliminar este almacén?')) return;

    try {
      await almacenService.deleteAlmacen(id);
      toast.success('Almacén eliminado exitosamente');
      loadAlmacenes();
    } catch (error: any) {
      const message = error.response?.data?.message || 'Error al eliminar el almacén';
      toast.error(message);
    }
  };

  const handleToggleEstado = async (id: number) => {
    try {
      const response = await almacenService.toggleEstado(id);
      toast.success(response.message || 'Estado actualizado');
      loadAlmacenes();
    } catch (error: any) {
      toast.error('Error al cambiar el estado');
    }
  };

  const handleModalClose = () => {
    setShowModal(false);
    setSelectedAlmacen(null);
  };

  const handleModalSuccess = () => {
    loadAlmacenes();
    handleModalClose();
  };

  return (
    <div className="p-6 max-w-7xl mx-auto">
      {/* Header */}
      <div className="mb-6">
        <div className="flex items-center justify-between mb-2">
          <div className="flex items-center gap-3">
            <div className="p-3 bg-blue-100 rounded-lg">
              <Warehouse className="w-6 h-6 text-blue-600" />
            </div>
            <div>
              <h1 className="text-2xl font-bold text-gray-900">Almacenes</h1>
              <p className="text-sm text-gray-500">
                Gestiona los almacenes de tu empresa
              </p>
            </div>
          </div>
          <button
            onClick={handleCreate}
            className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            <Plus className="w-4 h-4" />
            Nuevo Almacén
          </button>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">Total Almacenes</p>
              <p className="text-2xl font-bold text-gray-900">{total}</p>
            </div>
            <Warehouse className="w-8 h-8 text-blue-500" />
          </div>
        </div>

        <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">Almacenes Activos</p>
              <p className="text-2xl font-bold text-green-600">
                {almacenes.filter(a => a.activo).length}
              </p>
            </div>
            <Package className="w-8 h-8 text-green-500" />
          </div>
        </div>

        <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">Inactivos</p>
              <p className="text-2xl font-bold text-red-600">
                {almacenes.filter(a => !a.activo).length}
              </p>
            </div>
            <AlertCircle className="w-8 h-8 text-red-500" />
          </div>
        </div>

        <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">Con Stock</p>
              <p className="text-2xl font-bold text-purple-600">
                {almacenes.filter(a => (a.productos_count || 0) > 0).length}
              </p>
            </div>
            <MapPin className="w-8 h-8 text-purple-500" />
          </div>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
        <div className="flex items-center gap-2 mb-3">
          <Filter className="w-4 h-4 text-gray-500" />
          <h3 className="font-semibold text-gray-700">Filtros</h3>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          {/* Búsqueda */}
          <div className="md:col-span-2">
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Buscar
            </label>
            <div className="flex gap-2">
              <div className="relative flex-1">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input
                  type="text"
                  value={searchInput}
                  onChange={(e) => setSearchInput(e.target.value)}
                  onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                  placeholder="Nombre o ubicación..."
                  className="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              <button
                onClick={handleSearch}
                className="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
              >
                Buscar
              </button>
            </div>
          </div>

          {/* Estado */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Estado
            </label>
            <select
              value={filters.activo === undefined ? '' : filters.activo ? 'true' : 'false'}
              onChange={(e) =>
                handleFilterChange(
                  'activo',
                  e.target.value === '' ? undefined : e.target.value === 'true'
                )
              }
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">Todos</option>
              <option value="true">Activos</option>
              <option value="false">Inactivos</option>
            </select>
          </div>

          {/* Con Stock */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Stock
            </label>
            <select
              value={filters.con_stock === undefined ? '' : filters.con_stock ? 'true' : 'false'}
              onChange={(e) =>
                handleFilterChange(
                  'con_stock',
                  e.target.value === '' ? undefined : e.target.value === 'true'
                )
              }
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">Todos</option>
              <option value="true">Con Stock</option>
              <option value="false">Sin Stock</option>
            </select>
          </div>
        </div>
      </div>

      {/* Table */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200">
        <AlmacenTable
          almacenes={almacenes}
          loading={loading}
          onEdit={handleEdit}
          onDelete={handleDelete}
          onToggleEstado={handleToggleEstado}
        />

        {/* Pagination */}
        {lastPage > 1 && (
          <div className="flex items-center justify-between px-6 py-4 border-t border-gray-200">
            <div className="flex items-center gap-2">
              <span className="text-sm text-gray-700">
                Mostrando {(currentPage - 1) * perPage + 1} a{' '}
                {Math.min(currentPage * perPage, total)} de {total} registros
              </span>
            </div>

            <div className="flex items-center gap-2">
              <button
                onClick={() => setCurrentPage(currentPage - 1)}
                disabled={currentPage === 1}
                className="px-3 py-1 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
              >
                Anterior
              </button>
              
              {[...Array(lastPage)].map((_, i) => {
                const page = i + 1;
                if (
                  page === 1 ||
                  page === lastPage ||
                  (page >= currentPage - 1 && page <= currentPage + 1)
                ) {
                  return (
                    <button
                      key={page}
                      onClick={() => setCurrentPage(page)}
                      className={`px-3 py-1 rounded-lg ${
                        currentPage === page
                          ? 'bg-blue-600 text-white'
                          : 'border border-gray-300 hover:bg-gray-50'
                      }`}
                    >
                      {page}
                    </button>
                  );
                } else if (page === currentPage - 2 || page === currentPage + 2) {
                  return <span key={page}>...</span>;
                }
                return null;
              })}

              <button
                onClick={() => setCurrentPage(currentPage + 1)}
                disabled={currentPage === lastPage}
                className="px-3 py-1 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
              >
                Siguiente
              </button>
            </div>
          </div>
        )}
      </div>

      {/* Modal */}
      {showModal && (
        <AlmacenModal
          almacen={selectedAlmacen}
          onClose={handleModalClose}
          onSuccess={handleModalSuccess}
        />
      )}
    </div>
  );
}