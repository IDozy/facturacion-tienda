// services/usuarioService.ts

import api from './api';
import type { CreateUsuarioDTO, Rol, UpdateUsuarioDTO, Usuario } from "@/types/User";

class UsuarioService {

  // USUARIOS
  async obtenerUsuarios(): Promise<Usuario[]> {
    try {

      const response = await api.get('/users');

      // Maneja tanto respuestas paginadas como arrays directos
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
      const response = await api.post('/users', usuario);
      return response.data.data || response.data;
    } catch (error: any) {
      throw new Error(error.response?.data?.message || 'Error al crear usuario');
    }
  }

  async actualizarUsuario(id: number, usuario: UpdateUsuarioDTO): Promise<Usuario> {
    try {

      // Limpia el objeto - no envía password si está vacío
      const dataToSend = { ...usuario };
      if (!dataToSend.password) {
        delete dataToSend.password;
      }

      const response = await api.put(`/users/${id}`, dataToSend);
      return response.data.data || response.data;
    } catch (error: any) {
      throw new Error(error.response?.data?.message || 'Error al actualizar usuario');
    }
  }

  async eliminarUsuario(id: number): Promise<void> {
    try {
      await api.delete(`/usuarios/${id}`);
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