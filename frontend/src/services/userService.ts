// services/usuarioService.ts

import type { ApiError, CreateUsuarioDTO, Rol, UpdateUsuarioDTO, Usuario } from "@/types/User";



const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

class UsuarioService {
  private getAuthHeader() {
    const token = localStorage.getItem('token');
    console.log('🔑 Token:', token ? '✅ Existe' : '❌ No existe');
    return {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    };
  }

  private handleError(error: unknown): ApiError {
    if (error instanceof Response) {
      const message = error.statusText || 'Error desconocido';
      console.error(`❌ Error Response: ${error.status} - ${message}`);
      return { message };
    }
    if (error instanceof Error) {
      console.error(`❌ Error: ${error.message}`);
      return { message: error.message };
    }
    console.error('❌ Error desconocido:', error);
    return { message: 'Error desconocido' };
  }

  // USUARIOS
  async obtenerUsuarios(): Promise<Usuario[]> {
    try {
      console.log('📡 Obteniendo usuarios...');
      const url = `${API_URL}/usuarios`;
      console.log('URL:', url);
      
      const response = await fetch(url, {
        headers: this.getAuthHeader(),
      });

      console.log('📊 Response status:', response.status);
      console.log('📊 Response ok:', response.ok);

      if (!response.ok) {
        const errorData = await response.json();
        console.error('❌ Error data:', errorData);
        throw new Error(errorData.message || `HTTP ${response.status}`);
      }

      const data = await response.json();
      console.log('✅ Usuarios obtenidos:', data);
      return data.data || data;
    } catch (error) {
      const apiError = this.handleError(error);
      throw new Error(apiError.message);
    }
  }

  async obtenerUsuario(id: number): Promise<Usuario> {
    try {
      const response = await fetch(`${API_URL}/usuarios/${id}`, {
        headers: this.getAuthHeader(),
      });

      if (!response.ok) {
        throw response;
      }

      const data = await response.json();
      return data.data || data;
    } catch (error) {
      const apiError = this.handleError(error);
      throw new Error(apiError.message);
    }
  }

  async crearUsuario(usuario: CreateUsuarioDTO): Promise<Usuario> {
    try {
      console.log('📡 Creando usuario...');
      const response = await fetch(`${API_URL}/usuarios`, {
        method: 'POST',
        headers: this.getAuthHeader(),
        body: JSON.stringify(usuario),
      });

      console.log('📊 Response status:', response.status);

      if (!response.ok) {
        const errorData = await response.json();
        console.error('❌ Error:', errorData);
        throw new Error(errorData.message || 'Error al crear usuario');
      }

      const data = await response.json();
      console.log('✅ Usuario creado:', data);
      return data.data || data;
    } catch (error) {
      const apiError = this.handleError(error);
      throw new Error(apiError.message);
    }
  }

  async actualizarUsuario(id: number, usuario: UpdateUsuarioDTO): Promise<Usuario> {
    try {
      const dataToSend = { ...usuario };
      if (!dataToSend.password) {
        delete dataToSend.password;
      }

      const response = await fetch(`${API_URL}/usuarios/${id}`, {
        method: 'PUT',
        headers: this.getAuthHeader(),
        body: JSON.stringify(dataToSend),
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'Error al actualizar usuario');
      }

      const data = await response.json();
      return data.data || data;
    } catch (error) {
      const apiError = this.handleError(error);
      throw new Error(apiError.message);
    }
  }

  async eliminarUsuario(id: number): Promise<void> {
    try {
      const response = await fetch(`${API_URL}/usuarios/${id}`, {
        method: 'DELETE',
        headers: this.getAuthHeader(),
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'Error al eliminar usuario');
      }
    } catch (error) {
      const apiError = this.handleError(error);
      throw new Error(apiError.message);
    }
  }

  // ROLES
  async obtenerRoles(): Promise<Rol[]> {
    try {
      console.log('📡 Obteniendo roles...');
      const url = `${API_URL}/roles`;
      console.log('URL:', url);
      
      const response = await fetch(url, {
        headers: this.getAuthHeader(),
      });

      console.log('📊 Response status:', response.status);

      if (!response.ok) {
        const errorData = await response.json();
        console.error('❌ Error:', errorData);
        throw new Error(errorData.message || `HTTP ${response.status}`);
      }

      const data = await response.json();
      console.log('✅ Roles obtenidos:', data);
      return data.data || data;
    } catch (error) {
      const apiError = this.handleError(error);
      throw new Error(apiError.message);
    }
  }
}

export const usuarioService = new UsuarioService();