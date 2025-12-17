// app/facturacion/series/page.tsx
'use client';

import { useState, useEffect } from 'react';
import { Plus, Search, Filter, FileText, Hash, AlertCircle, TrendingUp } from 'lucide-react';
import serieService from '@/services/serieService';
import type { Serie, SerieFilters, PaginatedResponse, TipoComprobante } from '@/types/Serie';
import SerieModal from '@/components/serie/SerieModal';
import SerieTable from '@/components/serie/SerieTable';
import { toast } from 'react-hot-toast';

export default function SeriesPage() {
  const [series, setSeries] = useState<Serie[]>([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [selectedSerie, setSelectedSerie] = useState<Serie | null>(null);
  
  // Paginación
  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [perPage, setPerPage] = useState(15);

  // Filtros
  const [filters, setFilters] = useState<SerieFilters>({
    search: '',
    activo: undefined,
    tipo_comprobante: undefined,
    sort_by: 'tipo_comprobante',
    sort_order: 'asc',
  });

  const [searchInput, setSearchInput] = useState('');

  // Estadísticas
  const [stats, setStats] = useState({
    total_series: 0,
    activas: 0,
    inactivas: 0,
  });

  useEffect(() => {
    loadSeries();
    loadEstadisticas();
  }, [currentPage, perPage, filters]);

  const loadSeries = async () => {
    try {
      setLoading(true);
      const response = await serieService.getSeries({
        ...filters,
        page: currentPage,
        per_page: perPage,
      }) as PaginatedResponse<Serie>;

      setSeries(response.data);
      setCurrentPage(response.current_page);
      setLastPage(response.last_page);
      setTotal(response.total);
    } catch (error: any) {
      console.error('Error loading series:', error);
      toast.error('Error al cargar las series');
    } finally {
      setLoading(false);
    }
  };

  const loadEstadisticas = async () => {
    try {
      const data = await serieService.getEstadisticas();
      setStats({
        total_series: data.total_series,
        activas: data.activas,
        inactivas: data.inactivas,
      });
    } catch (error) {
      console.error('Error loading estadísticas:', error);
    }
  };

  const handleSearch = () => {
    setFilters({ ...filters, search: searchInput });
    setCurrentPage(1);
  };

  const handleFilterChange = (key: keyof SerieFilters, value: any) => {
    setFilters({ ...filters, [key]: value });
    setCurrentPage(1);
  };

  const handleCreate = () => {
    setSelectedSerie(null);
    setShowModal(true);
  };

  const handleEdit = (serie: Serie) => {
    setSelectedSerie(serie);
    setShowModal(true);
  };

  const handleDelete = async (id: number) => {
    if (!confirm('¿Está seguro de eliminar esta serie?')) return;

    try {
      await serieService.deleteSerie(id);
      toast.success('Serie eliminada exitosamente');
      loadSeries();
      loadEstadisticas();
    } catch (error: any) {
      const message = error.response?.data?.message || 'Error al eliminar la serie';
      toast.error(message);
    }
  };

  const handleToggleEstado = async (id: number) => {
    try {
      const response = await serieService.toggleEstado(id);
      toast.success(response.message || 'Estado actualizado');
      loadSeries();
      loadEstadisticas();
    } catch (error: any) {
      toast.error('Error al cambiar el estado');
    }
  };

  const handleGenerarNumero = async (id: number) => {
    try {
      const response = await serieService.generarNumero(id);
      toast.success(`Número generado: ${response.numero}`);
      loadSeries();
    } catch (error: any) {
      toast.error('Error al generar el número');
    }
  };

  const handleModalClose = () => {
    setShowModal(false);
    setSelectedSerie(null);
  };

  const handleModalSuccess = () => {
    loadSeries();
    loadEstadisticas();
    handleModalClose();
  };

  const getTipoComprobanteLabel = (tipo: TipoComprobante): string => {
    const labels: Record<TipoComprobante, string> = {
      factura: 'Factura',
      boleta: 'Boleta',
      nota_credito: 'Nota de Crédito',
      nota_debito: 'Nota de Débito',
      guia_remision: 'Guía de Remisión',
    };
    return labels[tipo] || tipo;
  };

  const getTipoComprobanteColor = (tipo: TipoComprobante): string => {
    const colors: Record<TipoComprobante, string> = {
      factura: 'blue',
      boleta: 'green',
      nota_credito: 'yellow',
      nota_debito: 'orange',
      guia_remision: 'purple',
    };
    return colors[tipo] || 'gray';
  };

  return (
    <div className="p-6 max-w-7xl mx-auto">
      {/* Header */}
      <div className="mb-6">
        <div className="flex items-center justify-between mb-2">
          <div className="flex items-center gap-3">
            <div className="p-3 bg-blue-100 rounded-lg">
              <FileText className="w-6 h-6 text-blue-600" />
            </div>
            <div>
              <h1 className="text-2xl font-bold text-gray-900">Series de Comprobantes</h1>
              <p className="text-sm text-gray-500">
                Gestiona las series para emisión de comprobantes SUNAT
              </p>
            </div>
          </div>
          <button
            onClick={handleCreate}
            className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            <Plus className="w-4 h-4" />
            Nueva Serie
          </button>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">Total Series</p>
              <p className="text-2xl font-bold text-gray-900">{stats.total_series}</p>
            </div>
            <FileText className="w-8 h-8 text-blue-500" />
          </div>
        </div>

        <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">Series Activas</p>
              <p className="text-2xl font-bold text-green-600">{stats.activas}</p>
            </div>
            <Hash className="w-8 h-8 text-green-500" />
          </div>
        </div>

        <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">Inactivas</p>
              <p className="text-2xl font-bold text-red-600">{stats.inactivas}</p>
            </div>
            <AlertCircle className="w-8 h-8 text-red-500" />
          </div>
        </div>

        <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">Comprobantes Emitidos</p>
              <p className="text-2xl font-bold text-purple-600">
                {series.reduce((sum, s) => sum + (s.comprobantes_count || 0), 0)}
              </p>
            </div>
            <TrendingUp className="w-8 h-8 text-purple-500" />
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
                  placeholder="Serie..."
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

          {/* Tipo de Comprobante */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Tipo de Comprobante
            </label>
            <select
              value={filters.tipo_comprobante || ''}
              onChange={(e) =>
                handleFilterChange(
                  'tipo_comprobante',
                  e.target.value || undefined
                )
              }
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">Todos</option>
              <option value="factura">Factura</option>
              <option value="boleta">Boleta</option>
              <option value="nota_credito">Nota de Crédito</option>
              <option value="nota_debito">Nota de Débito</option>
              <option value="guia_remision">Guía de Remisión</option>
            </select>
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
        </div>
      </div>

      {/* Table */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200">
        <SerieTable
          series={series}
          loading={loading}
          onEdit={handleEdit}
          onDelete={handleDelete}
          onToggleEstado={handleToggleEstado}
          onGenerarNumero={handleGenerarNumero}
          getTipoComprobanteLabel={getTipoComprobanteLabel}
          getTipoComprobanteColor={getTipoComprobanteColor}
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
        <SerieModal
          serie={selectedSerie}
          onClose={handleModalClose}
          onSuccess={handleModalSuccess}
        />
      )}
    </div>
  );
}