// services/usuarioService.ts
/*
import api from './api';
import type { CreateUsuarioDTO, Rol, UpdateUsuarioDTO, Usuario } from "@/types/User";

class UsuarioService {

  // USUARIOS
  async obtenerUsuarios(): Promise<Usuario[]> {
    try {

      const response = await api.get('/users');
      return response.data.data || response.data;
    } catch (error: any) {
      throw new Error(error.response?.data?.message || 'Error al obtener usuarios');
    }
  }

  async obtenerUsuario(id: number): Promise<Usuario> {
    try {
      const response = await api.get(`/users/${id}`);
      return response.data.data || response.data;
    } catch (error: any) {
      throw new Error(error.response?.data?.message || 'Error al obtener usuario');
    }
  }

  async crearUsuario(usuario: CreateUsuarioDTO): Promise<Usuario> {
    try {
      // Obtener el nombre del rol desde el ID
      const rolesResponse = await api.get('/roles');
      const roles = rolesResponse.data.data || rolesResponse.data;
      const rolSeleccionado = roles.find((r: Rol) => r.id === usuario.rol_id);
      
      if (!rolSeleccionado) {
        throw new Error('Rol no encontrado');
      }

      // Transformar datos para el backend
      const dataToSend = {
        nombre: usuario.nombre,
        email: usuario.email,
        password: usuario.password,
        password_confirmation: usuario.password_confirmation,
        tipo_documento: usuario.tipo_documento,
        numero_documento: usuario.numero_documento,
        telefono: usuario.telefono || '',
        activo: usuario.activo,
        roles: [rolSeleccionado.name || rolSeleccionado.nombre], // ← Array de nombres de roles
      };
      
      console.log('📤 Enviando:', dataToSend);
      const response = await api.post('/users', dataToSend);
      console.log('✅ Creado:', response.data);
      return response.data.data || response.data;
    } catch (error: any) {
      console.error('❌ Error:', error.response?.data);
      
      if (error.response?.status === 422) {
        const errors = error.response.data.errors || {};
        const messages = Object.entries(errors)
          .map(([field, msgs]) => `• ${field}: ${(msgs as string[]).join(', ')}`)
          .join('\n');
        throw new Error(messages || 'Error de validación');
      }
      
      throw new Error(error.response?.data?.message || 'Error al crear usuario');
    }
  }

  async actualizarUsuario(id: number, usuario: UpdateUsuarioDTO): Promise<Usuario> {
    try {
      // Obtener el nombre del rol si se proporciona rol_id
      let dataToSend: any = {
        nombre: usuario.nombre,
        email: usuario.email,
        tipo_documento: usuario.tipo_documento,
        numero_documento: usuario.numero_documento,
        telefono: usuario.telefono || '',
        activo: usuario.activo,
      };

      // Solo incluir password si se proporciona
      if (usuario.password && usuario.password.trim() !== '') {
        dataToSend.password = usuario.password;
        dataToSend.password_confirmation = usuario.password_confirmation;
      }

      // Si se proporciona rol_id, convertirlo a array de nombres
      if (usuario.rol_id) {
        const rolesResponse = await api.get('/roles');
        const roles = rolesResponse.data.data || rolesResponse.data;
        const rolSeleccionado = roles.find((r: Rol) => r.id === usuario.rol_id);
        
        if (rolSeleccionado) {
          dataToSend.roles = [rolSeleccionado.name || rolSeleccionado.nombre];
        }
      }

      const response = await api.put(`/users/${id}`, dataToSend);
      return response.data.data || response.data;
    } catch (error: any) {
      throw new Error(error.response?.data?.message || 'Error al actualizar usuario');
    }
  }

  async eliminarUsuario(id: number): Promise<void> {
    try {
      await api.delete(`/users/${id}`);
    } catch (error: any) {
      throw new Error(error.response?.data?.message || 'Error al eliminar usuario');
    }
  }

  // ROLES
  async obtenerRoles(): Promise<Rol[]> {
    try {
      const response = await api.get('/roles');
      return response.data.data || response.data;
    } catch (error: any) {
      throw new Error(error.response?.data?.message || 'Error al obtener roles');
    }
  }
}

export const usuarioService = new UsuarioService();*/

// services/usuarioService.ts

import api from './api';
import type { CreateUsuarioDTO, Rol, UpdateUsuarioDTO, Usuario } from '@/types/User';

export interface Empresa {
  id: number;
  ruc: string;
  razon_social: string;
  nombre_comercial?: string;
  direccion?: string;
  telefono?: string;
  email: string;
  activo?: boolean;
  created_at?: string;
  updated_at?: string;
}

export interface UsuarioAutenticado extends Usuario {
  empresa?: Empresa;
}

interface ApiResponse<T> {
  success?: boolean;
  message?: string;
  data: T;
  errors?: Record<string, string[]>;
  meta?: unknown;
}

const parseError = (error: any, fallback: string) => {
  if (error?.response?.data?.errors) {
    return error.response.data.errors;
  }
  const message = error?.response?.data?.message || fallback;
  throw new Error(message);
};

class UsuarioService {
  async obtenerPerfilUsuario(): Promise<UsuarioAutenticado> {
    try {
      const response = await api.get<ApiResponse<UsuarioAutenticado>>('/profile');
      return response.data.data ?? (response.data as any).user ?? (response.data as any);
    } catch (error: any) {
      throw parseError(error, 'Error al obtener perfil de usuario');
    }
  }

  async obtenerEmpresaUsuario(): Promise<Empresa | null> {
    const perfil = await this.obtenerPerfilUsuario();
    return perfil.empresa || null;
  }

  async obtenerUsuarios(): Promise<{ data: Usuario[]; meta?: unknown }> {
    try {
      const response = await api.get<ApiResponse<Usuario[]>>('/users');
      return {
        data: response.data.data ?? (response.data as any).data ?? [],
        meta: response.data.meta,
      };
    } catch (error: any) {
      throw parseError(error, 'Error al obtener usuarios');
    }
  }

  async obtenerUsuario(id: number): Promise<Usuario> {
    try {
      const response = await api.get<ApiResponse<Usuario>>(`/users/${id}`);
      return response.data.data ?? (response.data as any);
    } catch (error: any) {
      throw parseError(error, 'Error al obtener usuario');
    }
  }

  async crearUsuario(usuario: CreateUsuarioDTO): Promise<Usuario> {
    try {
      const rolNombre = await this.obtenerNombreRol(usuario.rol_id);
      const payload = {
        nombre: usuario.nombre,
        email: usuario.email,
        password: usuario.password,
        password_confirmation: usuario.password_confirmation,
        tipo_documento: usuario.tipo_documento,
        numero_documento: usuario.numero_documento,
        telefono: usuario.telefono || '',
        activo: usuario.activo,
        roles: rolNombre ? [rolNombre] : [],
      };

      const response = await api.post<ApiResponse<Usuario>>('/users', payload);
      return response.data.data ?? (response.data as any);
    } catch (error: any) {
      if (error?.response?.status === 422) {
        const validationErrors = parseError(error, 'Error de validación');
        throw validationErrors;
      }
      throw parseError(error, 'Error al crear usuario');
    }
  }

  async actualizarUsuario(id: number, usuario: UpdateUsuarioDTO): Promise<Usuario> {
    try {
      const payload: any = {
        nombre: usuario.nombre,
        email: usuario.email,
        tipo_documento: usuario.tipo_documento,
        numero_documento: usuario.numero_documento,
        telefono: usuario.telefono || '',
        activo: usuario.activo,
      };

      if (usuario.password) {
        payload.password = usuario.password;
        payload.password_confirmation = usuario.password_confirmation;
      }

      if (usuario.rol_id) {
        const rolNombre = await this.obtenerNombreRol(usuario.rol_id);
        payload.roles = rolNombre ? [rolNombre] : [];
      }

      const response = await api.put<ApiResponse<Usuario>>(`/users/${id}`, payload);
      return response.data.data ?? (response.data as any);
    } catch (error: any) {
      if (error?.response?.status === 422) {
        const validationErrors = parseError(error, 'Error de validación');
        throw validationErrors;
      }
      throw parseError(error, 'Error al actualizar usuario');
    }
  }

  async eliminarUsuario(id: number): Promise<void> {
    try {
      await api.delete(`/users/${id}`);
    } catch (error: any) {
      throw parseError(error, 'Error al eliminar usuario');
    }
  }

  async obtenerRoles(): Promise<Rol[]> {
    try {
      const response = await api.get<ApiResponse<Rol[]>>('/roles');
      return response.data.data ?? (response.data as any).data ?? [];
    } catch (error: any) {
      throw parseError(error, 'Error al obtener roles');
    }
  }

  private async obtenerNombreRol(rolId?: number): Promise<string | null> {
    if (!rolId) return null;
    const roles = await this.obtenerRoles();
    const rolSeleccionado = roles.find((r) => r.id === rolId);
    return rolSeleccionado ? rolSeleccionado.name || rolSeleccionado.nombre || null : null;
  }
}

export const usuarioService = new UsuarioService();