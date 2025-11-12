// src/types/Empresa.ts
export interface Empresa {
  id?: number;
  ruc: string;
  razon_social: string;
  nombre_comercial?: string;
  direccion: string;
  ubigeo?: string;
  telefono?: string;
  email?: string;
  logo?: string;
  logo_url?: string; // URL del logo después de subirlo
  web?: string;
  
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