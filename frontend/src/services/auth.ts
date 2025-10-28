import axios from 'axios';
import api from './api';

// 🔥 Instancia separada SOLO para obtener el CSRF cookie (sin /api)
const csrfApi = axios.create({
  baseURL: 'https://solid-space-happiness-v7vwxp5r44wf69qj-8000.app.github.dev',
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
  },
});

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface User {
  id: number;
  name: string;
  email: string;
  rol_id?: number | null;
}

interface LoginResponse {
  success: boolean;
  data: {
    user: User;
    token: string;
  };
  message: string;
}

interface MeResponse {
  success: boolean;
  data: User;
}

export const authService = {
  /**
   * Inicia sesión en el sistema
   */
  async login(credentials: LoginCredentials) {
    try {
      // 🔹 Paso 1: Obtener cookie CSRF (usando csrfApi sin /api)
      await csrfApi.get('/sanctum/csrf-cookie');

      // 🔹 Paso 2: Realizar login (usando api con /api)
      const response = await api.post<LoginResponse>('/auth/login', credentials);

      // 🔹 Paso 3: Guardar token y usuario
      if (response.data.success && response.data.data.token) {
        localStorage.setItem('token', response.data.data.token);
        localStorage.setItem('user', JSON.stringify(response.data.data.user));
        api.defaults.headers.common['Authorization'] = `Bearer ${response.data.data.token}`;
      }

      return response.data;
    } catch (error: any) {
      console.error('Error en login:', error);
      throw error;
    }
  },

  /**
   * Cierra la sesión del usuario
   */
  async logout() {
    const token = localStorage.getItem('token');

    if (token) {
      try {
        api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        await api.post('/auth/logout');
      } catch (error) {
        console.error('Error al cerrar sesión:', error);
      } finally {
        // 🔹 Limpiar datos locales siempre
        this.clearAuth();
      }
    }
  },

  /**
   * Obtiene los datos del usuario autenticado
   */
  async me() {
    const token = localStorage.getItem('token');

    if (token) {
      try {
        api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        const response = await api.get<MeResponse>('/auth/me');

        if (response.data.success) {
          localStorage.setItem('user', JSON.stringify(response.data.data));
          return response.data.data;
        }
      } catch (error) {
        console.error('Error al obtener usuario actual:', error);
        this.clearAuth();
      }
    }

    return null;
  },

  /**
   * Obtiene el token guardado
   */
  getToken() {
    return localStorage.getItem('token');
  },

  /**
   * Obtiene los datos del usuario guardado
   */
  getUser(): User | null {
    const user = localStorage.getItem('user');
    return user ? JSON.parse(user) : null;
  },

  /**
   * Verifica si hay sesión activa
   */
  isAuthenticated() {
    return !!this.getToken();
  },

  /**
   * Limpia toda la información de autenticación
   */
  clearAuth() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    delete api.defaults.headers.common['Authorization'];
  },

  /**
   * Inicializa el token al cargar la aplicación
   */
  initAuth() {
    const token = this.getToken();
    if (token) {
      api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    }
  },
};