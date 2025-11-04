import api from './api';

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface User {
  id: number;
  nombre: string;
  email: string;
  empresa_id: number;
  activo: boolean;
  
}

interface LoginResponse {
  success: boolean;
  user: User;
  token: string;
  token_type: string;
  message?: string;
}

interface UserResponse {
  success: boolean;
  user: User;
}

export const authService = {
  async login(credentials: LoginCredentials) {
    const { data } = await api.post<LoginResponse>('/login', credentials);
    
    if (data.success && data.token) {
      localStorage.setItem('token', data.token);
      localStorage.setItem('user', JSON.stringify(data.user));
    }
    
    return data;
  },

  async logout() {
    try {
      await api.post('/logout');
    } finally {
      this.clearAuth();
    }
  },

  async me() {
    try {
      const { data } = await api.get<UserResponse>('/user');
      if (data.success) {
        localStorage.setItem('user', JSON.stringify(data.user));
        return data.user;
      }
    } catch (error) {
      this.clearAuth();
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
  },

  initAuth() {
    const token = this.getToken();
    if (token) {
      api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    }
  },
};