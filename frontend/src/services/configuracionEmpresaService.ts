// src/services/configuracionEmpresaService.ts
import api from './api';
import type { ConfiguracionEmpresa } from '../types/ConfiguracionEmpresa';

// Obtener configuración por ID de configuración
export const getConfiguracionEmpresa = async (id: number): Promise<ConfiguracionEmpresa> => {
  const response = await api.get(`/configuraciones-empresa/${id}`);
  // El backend puede devolver { data: {...} } o directamente el objeto
  return (response.data as any)?.data || response.data;
};

// ✅ NUEVA: Obtener configuración por empresa_id
export const getConfiguracionPorEmpresa = async (empresaId: number): Promise<ConfiguracionEmpresa | null> => {
  try {
    const response = await api.get(`/configuraciones-empresa-por-empresa/${empresaId}`);
    return response.data?.data || response.data;
  } catch (error: any) {
    if (error.response?.status === 404) {
      return null;
    }
    throw error;
  }
};


export const updateConfiguracionEmpresa = async (
  id: number,
  data: Partial<ConfiguracionEmpresa>
): Promise<ConfiguracionEmpresa> => {
  const response = await api.put(`/configuraciones-empresa/${id}`, data);
  const result = response.data;
  if (result?.data) return result.data;
  return result;
};


export const createConfiguracionEmpresa = async (
  data: Partial<ConfiguracionEmpresa>
): Promise<ConfiguracionEmpresa> => {
  const response = await api.post('/configuraciones-empresa', data);
  // El backend puede devolver { data: {...} } o directamente el objeto
  return (response.data as any)?.data || response.data;
};

// ✅ NUEVA: Guardar o actualizar automáticamente
export const saveConfiguracionEmpresa = async (
  data: Partial<ConfiguracionEmpresa>
): Promise<ConfiguracionEmpresa> => {
  if (!data.empresa_id) {
    throw new Error('empresa_id es requerido');
  }

  try {
    
    const existing = await getConfiguracionPorEmpresa(data.empresa_id);

    if (existing?.id) {
      console.log('🔍 ID de configuración encontrado:', existing.id);

      // ⚠️ IMPORTANTE: Remover campos que no se deben enviar en UPDATE
      // Creamos un objeto limpio solo con los campos editables
      const updateData: Partial<ConfiguracionEmpresa> = {
        igv_porcentaje: data.igv_porcentaje,
        moneda_default: data.moneda_default,
        tolerancia_cuadratura: data.tolerancia_cuadratura,
        retencion_porcentaje_default: data.retencion_porcentaje_default,
        percepcion_porcentaje_default: data.percepcion_porcentaje_default,
      };

      console.log('📤 Datos a actualizar:', updateData);
console.log('🆔 Actualizando configuración con ID:', existing.id);
      const response = await updateConfiguracionEmpresa(existing.id, updateData);

      console.log('✅ Configuración actualizada exitosamente');

      // Recargar datos actualizados desde el servidor
      const reloaded = await getConfiguracionPorEmpresa(data.empresa_id);
      return reloaded || response;
    }
  } catch (error: any) {
    console.error('❌ Error al buscar/actualizar configuración:', error);
    // Si el error NO es 404, propagar el error
    if (error.response?.status !== 404) {
      throw error;
    }
  }

  // Si no existe (404) o no se encontró, crear nueva
  console.log('✨ Creando nueva configuración');

  // Para CREATE, solo enviar los campos necesarios
  const createData: Partial<ConfiguracionEmpresa> = {
    empresa_id: data.empresa_id,
    igv_porcentaje: data.igv_porcentaje,
    moneda_default: data.moneda_default,
    tolerancia_cuadratura: data.tolerancia_cuadratura,
    retencion_porcentaje_default: data.retencion_porcentaje_default,
    percepcion_porcentaje_default: data.percepcion_porcentaje_default,
  };

  console.log('📤 Datos a crear:', createData);

  const response = await createConfiguracionEmpresa(createData);

  // Manejar respuesta que puede tener la data envuelta o directa
  if (response && typeof response === 'object') {
    return (response as any).data || response;
  }

  return response;
};

// Funciones adicionales para usar las rutas especializadas
export const calcularIgv = async (configuracionId: number, monto: number) => {
  const response = await api.post(`/configuraciones-empresa/${configuracionId}/calcular-igv`, { monto });
  return response.data;
};

export const calcularSinIgv = async (configuracionId: number, montoConIgv: number) => {
  const response = await api.post(`/configuraciones-empresa/${configuracionId}/calcular-sin-igv`, { monto_con_igv: montoConIgv });
  return response.data;
};

export const calcularRetencion = async (configuracionId: number, monto: number) => {
  const response = await api.post(`/configuraciones-empresa/${configuracionId}/calcular-retencion`, { monto });
  return response.data;
};

export const calcularPercepcion = async (configuracionId: number, monto: number) => {
  const response = await api.post(`/configuraciones-empresa/${configuracionId}/calcular-percepcion`, { monto });
  return response.data;
};

export const actualizarIgv = async (configuracionId: number, igvPorcentaje: number) => {
  const response = await api.patch(`/configuraciones-empresa/${configuracionId}/actualizar-igv`, { igv_porcentaje: igvPorcentaje });
  return response.data;
};

export const actualizarMoneda = async (configuracionId: number, moneda: string) => {
  const response = await api.patch(`/configuraciones-empresa/${configuracionId}/actualizar-moneda`, { moneda_default: moneda });
  return response.data;
};

export const restablecerDefecto = async (configuracionId: number) => {
  const response = await api.post(`/configuraciones-empresa/${configuracionId}/restablecer-defecto`);
  return response.data;
};

export const getMonedasDisponibles = async () => {
  const response = await api.get('/monedas-disponibles');
  return response.data;
};