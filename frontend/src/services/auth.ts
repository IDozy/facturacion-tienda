import api from './api';

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
  async login(credentials: LoginCredentials) {
    const response = await api.post<LoginResponse>('/auth/login', credentials);
    
    // Guardar token en localStorage
    if (response.data.success && response.data.data.token) {
      localStorage.setItem('token', response.data.data.token);
      localStorage.setItem('user', JSON.stringify(response.data.data.user));
      
      // Configurar el token en el header de axios para futuras peticiones
      api.defaults.headers.common['Authorization'] = `Bearer ${response.data.data.token}`;
    }
    
    return response.data;
  },

  async logout() {
    const token = localStorage.getItem('token');
    
    if (token) {
      try {
        api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        await api.post('/auth/logout');
      } catch (error) {
        console.error('Error al cerrar sesión:', error);
      } finally {
        // Limpiar localStorage siempre
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        delete api.defaults.headers.common['Authorization'];
      }
    }
  },

  async me() {
    const token = localStorage.getItem('token');
    
    if (token) {
      try {
        api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        const response = await api.get<MeResponse>('/auth/me');
        
        if (response.data.success) {
          // Actualizar usuario en localStorage
          localStorage.setItem('user', JSON.stringify(response.data.data));
          return response.data.data;
        }
      } catch (error) {
        console.error('Error al obtener usuario:', error);
        // Si falla, limpiar localStorage
        this.clearAuth();
      }
    }
    
    return null;
  },

  getToken() {
    return localStorage.getItem('token');
  },

  getUser(): User | null {
    const user = localStorage.getItem('user');
    return user ? JSON.parse(user) : null;
  },

  isAuthenticated() {
    return !!this.getToken();
  },

  clearAuth() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    delete api.defaults.headers.common['Authorization'];
  },

  // Inicializar el token al cargar la aplicación
  initAuth() {
    const token = this.getToken();
    if (token) {
      api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    }
  },
};