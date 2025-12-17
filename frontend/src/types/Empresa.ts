// src/types/Empresa.ts
export interface Empresa {
  id?: number;
  ruc: string;
  razon_social: string;
  nombre_comercial?: string;
  direccion?: string;
  direccion_fiscal?: string;
  departamento?: string;
  provincia?: string;
  distrito?: string;
  telefono?: string;
  email?: string;
  logo?: string;
  logo_url?: string; // URL del logo después de subirlo
  web?: string;
  moneda?: 'PEN' | 'USD';
  igv_porcentaje?: number;
  incluye_igv_por_defecto?: boolean;
  serie_factura?: string;
  serie_boleta?: string;
  numero_factura_actual?: number;
  numero_boleta_actual?: number;
  formato_fecha?: 'DD/MM/YYYY' | 'MM/DD/YYYY' | 'YYYY-MM-DD';
  decimales?: number;
  zona_horaria?: string;
  
  // Configuración SUNAT
  usuario_sol?: string;
  clave_sol?: string;
  certificado_digital?: string; // Ruta al archivo .pfx en el servidor
  clave_certificado?: string; // Contraseña del certificado
  modo?: 'prueba' | 'produccion'; // Modo de operación
  modo_prueba?: boolean; // Mantener por compatibilidad
  fecha_expiracion_certificado?: string; // Fecha de expiración del certificado
  pse_autorizado?: boolean; // Si es PSE autorizado por SUNAT
  
  // Campos adicionales SUNAT (opcionales, por si los necesitas después)
  certificado_activo?: boolean; // Si el certificado está activo
  ultima_actualizacion_sunat?: string; // Última vez que se actualizó config SUNAT
  codigo_domicilio_fiscal?: string; // Código de domicilio fiscal
  
  // Timestamps
  created_at?: string;
  updated_at?: string;
}