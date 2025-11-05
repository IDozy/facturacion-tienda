// types/usuario.types.ts

// ============ PERMISOS ============
export interface Permiso {
  id: number;
  name?: string;           // Spatie usa 'name'
  nombre?: string;         // Alias para compatibilidad
  descripcion?: string;
  modulo?: string;
  guard_name?: string;
  created_at?: string;
  updated_at?: string;
}

// ============ ROLES ============
export interface Rol {
  id: number;
  name?: string;           // Spatie usa 'name'
  nombre?: string;         // Alias para compatibilidad
  descripcion?: string;
  activo?: boolean;
  guard_name?: string;
  created_at?: string;
  updated_at?: string;
  permisos?: Permiso[];
  permissions?: Permiso[]; // Spatie usa 'permissions'
  usuarios_count?: number;
}

// ============ USUARIOS ============
export interface Usuario {
  id: number;
  nombre: string;
  email: string;
  rol_id?: number;                              // Ahora opcional
  numero_documento: string;
  tipo_documento: 'DNI' | 'RUC' | 'PASAPORTE';
  telefono: string;
  activo: boolean;
  created_at?: string;
  updated_at?: string;
  rol?: Rol;                                    // Rol único (legacy)
  roles?: Rol[];                                // Array de roles (Spatie)
  empresa_id?: number;
  empresa?: any;

  permissions?: Permiso[];
}

export interface CreateUsuarioDTO {
  nombre: string;
  email: string;
  password: string;
  password_confirmation?: string; 
  rol_id: number;
  numero_documento: string;
  tipo_documento: 'DNI' | 'RUC' | 'PASAPORTE';
  telefono: string;
  activo: boolean;
}

export interface UpdateUsuarioDTO {
  nombre?: string;
  email?: string;
  password?: string;
  password_confirmation?: string; 
  rol_id?: number;
  numero_documento?: string;
  tipo_documento?: 'DNI' | 'RUC' | 'PASAPORTE';
  telefono?: string;
  activo?: boolean;
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
  total?: number;
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