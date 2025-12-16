export interface Producto {
  id: number;
  codigo: string;
  nombre: string;
  descripcion?: string | null;
  categoria_id: number;
  unidad_medida: string;
  precio_compra: number;
  precio_venta: number;
  stock_minimo: number;
  empresa_id: number;
  estado: 'activo' | 'inactivo';
  categoria?: {
    id: number;
    nombre: string;
  };
  stock_actual?: number;
  precio_promedio?: number;
  es_bajo_stock?: boolean;
}

export interface ProductoFilters {
  search?: string;
  estado?: 'activo' | 'inactivo';
  categoria_id?: number;
  bajo_stock?: boolean;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
}

export interface ProductoResumen {
  total: number;
  activos: number;
  inactivos: number;
  bajo_stock: number;
  stock_total: number;
  valor_inventario: number;
}
