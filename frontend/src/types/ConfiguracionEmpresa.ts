// src/types/ConfiguracionEmpresa.ts
export interface ConfiguracionEmpresa {
  id?: number;
  empresa_id: number;
  igv_porcentaje: number;
  moneda_default: string;
  tolerancia_cuadratura: number;
  retencion_porcentaje_default: number;
  percepcion_porcentaje_default: number;
  created_at?: string;
  updated_at?: string;
}