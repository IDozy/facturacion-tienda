// types/Serie.ts
export interface Serie {
  id: number;
  empresa_id: number;
  tipo_comprobante: TipoComprobante;
  serie: string;
  correlativo_actual: number;
  activo: boolean;
  created_at?: string;
  updated_at?: string;
  
  // Relaciones
  empresa?: {
    id: number;
    razon_social: string;
    ruc: string;
  };
  comprobantes_count?: number;
}

export type TipoComprobante = 
  | 'factura' 
  | 'boleta' 
  | 'nota_credito' 
  | 'nota_debito' 
  | 'guia_remision';

export interface SerieFilters {
  search?: string;
  activo?: boolean;
  tipo_comprobante?: TipoComprobante;
  empresa_id?: number;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

export interface TipoComprobanteOption {
  value: TipoComprobante;
  label: string;
  formato: string;
}