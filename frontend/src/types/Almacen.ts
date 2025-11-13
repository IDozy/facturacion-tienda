// types/almacen.types.ts

export interface Almacen {
  id: number;
  nombre: string;
  ubicacion: string | null;
  empresa_id: number;
  activo: boolean;
  created_at: string;
  updated_at: string;
  productos_count?: number;
  empresa?: {
    id: number;
    razon_social: string;
    ruc: string;
  };
}

export interface AlmacenDetalle extends Almacen {
  cantidad_productos: number;
  valor_inventario: number;
  productos_bajo_stock: number;
}

export interface AlmacenProducto {
  producto_id: number;
  codigo: string;
  nombre: string;
  stock_actual: number;
  stock_minimo: number;
  diferencia?: number;
  precio_promedio?: number;
  valor_total?: number;
}

export interface MovimientoStock {
  id: number;
  almacen_id: number;
  producto_id: number;
  tipo: 'entrada' | 'salida' | 'ajuste' | 'transferencia';
  cantidad: number;
  motivo: string | null;
  referencia: string | null;
  created_at: string;
  producto?: {
    id: number;
    codigo: string;
    nombre: string;
  };
}

export interface EstadisticasAlmacen {
  total_productos: number;
  productos_con_stock: number;
  productos_sin_stock: number;
  productos_bajo_stock: number;
  stock_total: number;
  valor_inventario: number;
  ultimos_movimientos: MovimientoStock[];
}

export interface ValorizacionAlmacen {
  almacen: string;
  productos: AlmacenProducto[];
  valor_total: number;
}

export interface ComparacionAlmacen {
  id: number;
  nombre: string;
  ubicacion: string | null;
  activo: boolean;
  total_productos: number;
  productos_con_stock: number;
  stock_total: number;
  valor_inventario: number;
  productos_bajo_stock: number;
}

export interface VerificarStockResponse {
  tiene_stock: boolean;
  stock_actual: number;
  cantidad_solicitada: number;
  faltante: number;
}

export interface AlmacenFormData {
  nombre: string;
  ubicacion?: string;
  activo?: boolean;
}

export interface AlmacenFilters {
  activo?: boolean;
  con_stock?: boolean;
  search?: string;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
  all?: boolean;
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

export interface ApiResponse<T> {
  message?: string;
  data?: T;
  errors?: Record<string, string[]>;
}