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
  web?: string;
  usuario_sol?: string;
  clave_sol?: string;
  modo_prueba?: boolean;
  created_at?: string;
  updated_at?: string;
}
