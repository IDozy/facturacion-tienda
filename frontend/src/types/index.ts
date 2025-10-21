export interface Producto {
  id: number;
  codigo: string;
  codigo_barras?: string;
  descripcion: string;
  descripcion_larga?: string;
  unidad_medida: string;
  precio_unitario: number;
  precio_venta: number;
  tipo_igv: string;
  porcentaje_igv: number;
  stock: number;
  stock_minimo: number;
  ubicacion?: string;
  categoria?: string;
  imagen?: string;
  activo: boolean;
}

export interface Cliente {
  id: number;
  tipo_documento: string;
  numero_documento: string;
  nombre_razon_social: string;
  nombre_comercial?: string;
  direccion?: string;
  distrito?: string;
  provincia?: string;
  departamento?: string;
  ubigeo?: string;
  telefono?: string;
  email?: string;
  activo: boolean;
}

export interface ComprobanteDetalle {
  producto_id: number;
  cantidad: number;
  precio_unitario: number;
  descuento?: number;
}

export interface Comprobante {
  id?: number;
  cliente_id: number;
  tipo_comprobante: string;
  serie?: string;
  correlativo?: number;
  fecha_emision: string;
  moneda: string;
  total_gravada?: number;
  total_igv?: number;
  total?: number;
  estado_sunat?: string;
  detalles: ComprobanteDetalle[];
}
