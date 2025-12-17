// services/serieService.ts

import type { Serie, SerieFilters, PaginatedResponse, TipoComprobanteOption } from '@/types/Serie';
import api from './api';

const serieService = {
  // Obtener series con filtros
  getSeries: async (params?: SerieFilters & { page?: number; per_page?: number; all?: string }) => {
    const response = await api.get('/facturacion/series', { params });
    return response.data;
  },

  // Obtener series por empresa
  getSeriesPorEmpresa: async (
    empresaId?: number,
    params?: SerieFilters & { page?: number; per_page?: number }
  ) => {
    const filteredParams = {
      ...params,
      empresa_id: empresaId,
    };
    
    const response = await api.get('/facturacion/series', { params: filteredParams });
    return response.data;
  },

  // Obtener una serie específica
  getSerie: async (id: number) => {
    const response = await api.get(`/facturacion/series/${id}`);
    return response.data.data;
  },

  // Crear serie
  createSerie: async (data: Partial<Serie>) => {
    const response = await api.post('/facturacion/series', data);
    return response.data;
  },

  // Actualizar serie
  updateSerie: async (id: number, data: Partial<Serie>) => {
    const response = await api.put(`/facturacion/series/${id}`, data);
    return response.data;
  },

  // Eliminar serie
  deleteSerie: async (id: number) => {
    const response = await api.delete(`/facturacion/series/${id}`);
    return response.data;
  },

  // Toggle estado
  toggleEstado: async (id: number) => {
    const response = await api.post(`/facturacion/series/${id}/toggle-estado`);
    return response.data;
  },

  // Generar número (incrementa correlativo)
  generarNumero: async (id: number) => {
    const response = await api.post(`/facturacion/series/${id}/generar-numero`);
    return response.data;
  },

  // Obtener siguiente número sin incrementar
  getSiguienteNumero: async (id: number) => {
    const response = await api.get(`/facturacion/series/${id}/siguiente-numero`);
    return response.data;
  },

  // Validar formato
  validarFormato: async (id: number) => {
    const response = await api.get(`/facturacion/series/${id}/validar-formato`);
    return response.data;
  },

  // Obtener series por tipo
  getSeriesPorTipo: async (tipoComprobante: string) => {
    const response = await api.get(`/facturacion/series/por-tipo/${tipoComprobante}`);
    return response.data.data;
  },

  // Restablecer correlativo
  restablecerCorrelativo: async (id: number, correlativo: number) => {
    const response = await api.post(`/facturacion/series/${id}/restablecer-correlativo`, {
      correlativo,
      confirmacion: true,
    });
    return response.data;
  },

  // Obtener estadísticas
  getEstadisticas: async (empresaId?: number) => {
    const params = empresaId ? { empresa_id: empresaId } : {};
    const response = await api.get('/series/estadisticas', { params });
    return response.data;
  },

  // Obtener tipos de comprobante
  getTiposComprobante: async (): Promise<{ data: TipoComprobanteOption[] }> => {
    const response = await api.get('/series/tipos');
    return response.data;
  },
};

export default serieService;