// services/usuarioService.ts

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

export const usuarioService = new UsuarioService();