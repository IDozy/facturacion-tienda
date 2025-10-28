// types/usuario.types.ts

// ============ PERMISOS ============
export interface Permiso {
  id: number;
  nombre: string;
  descripcion?: string;
  modulo: string;
}

// ============ ROLES ============
export interface Rol {
  id: number;
  nombre: string;
  descripcion?: string;
  activo?: boolean;
  created_at?: string;
  updated_at?: string;
  permisos?: Permiso[];
  usuarios_count?: number;
}

// ============ USUARIOS ============
export interface Usuario {
  id: number;
  nombre: string;
  email: string;
  rol_id: number;
  numero_documento: string;
  tipo_documento: 'DNI' | 'RUC' | 'PASAPORTE';
  telefono: string;
  activo: boolean;
  created_at?: string;
  updated_at?: string;
  rol?: Rol;
}

export interface CreateUsuarioDTO {
  nombre: string;
  email: string;
  password: string;
  rol_id: number;
  numero_documento: string;
  tipo_documento: 'DNI' | 'RUC' | 'PASAPORTE';
  telefono: string;
  activo: boolean;
}

export interface UpdateUsuarioDTO {
  nombre: string;
  email: string;
  password?: string;
  rol_id: number;
  numero_documento: string;
  tipo_documento: 'DNI' | 'RUC' | 'PASAPORTE';
  telefono: string;
  activo: boolean;
}

// ============ ROLES (CRUD) ============
export interface CreateRolDTO {
  nombre: string;
  descripcion?: string;
  permisos: number[];
}

export interface UpdateRolDTO {
  nombre?: string;
  descripcion?: string;
  permisos?: number[];
  activo?: boolean;
}

export interface AsignarPermisosDTO {
  permisos: number[];
}

// ============ RESPUESTAS ============
export interface UsuariosResponse {
  data: Usuario[];
  message?: string;
}

export interface RolesResponse {
  success: boolean;
  data: Rol[];
  message?: string;
}

export interface PermisosResponse {
  success: boolean;
  data: Permiso[];
  message?: string;
}

export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}