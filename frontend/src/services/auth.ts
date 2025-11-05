// services/auth.ts

import type { Usuario } from '@/types/User';
import api from './api';

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface Rol {
  id: number;
  name: string;
  nombre?: string;
  guard_name?: string;
}

export interface User {
  id: number;
  nombre: string;
  email: string;
  empresa_id: number;
  activo: boolean;
  roles?: Rol[];
  permissions?: Array<{ id: number; name: string }>;
}

interface LoginResponse {
  success: boolean;
  user: User;
  token: string;
  token_type: string;
  message?: string;
}



export const authService = {
  async login(credentials: LoginCredentials) {
    const { data } = await api.post<LoginResponse>('/login', credentials);

    if (data.success && data.token) {
      localStorage.setItem('token', data.token);

      // Obtener el perfil completo del usuario con roles
      try {
        const userProfile = await this.fetchUserProfile();
        if (userProfile) {
          localStorage.setItem('user', JSON.stringify(userProfile));
        } else {
          // Si falla, guardar lo que vino del login
          localStorage.setItem('user', JSON.stringify(data.user));
        }
      } catch (error) {
        console.error('Error obteniendo perfil:', error);
        localStorage.setItem('user', JSON.stringify(data.user));
      }
    }

    return data;
  },

  // Nueva función para obtener el perfil del usuario
  async fetchUserProfile(): Promise<User | null> {
    try {
      // Usa el endpoint de perfil o el endpoint de usuario actual
      const { data } = await api.get<any>('/profile');
      return data.data || data.user || data;
    } catch (error) {
      console.error('Error obteniendo perfil:', error);
      return null;
    }
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
      const userProfile = await this.fetchUserProfile();
      if (userProfile) {
        localStorage.setItem('user', JSON.stringify(userProfile));
        return userProfile;
      }
    } catch (error) {
      this.clearAuth();
    }
    return null;
  },

  getToken() {
    return localStorage.getItem('token');
  },

  getUser(): Usuario | null {
    const user = localStorage.getItem('user');
    if (!user) return null;
    const parsedUser = JSON.parse(user) as Usuario;
    return parsedUser;
  },

  // ========== FUNCIONES PARA ROLES ========== //

  getUserRoles(): string[] {
    const user = this.getUser();
    if (!user) {
      return [];
    }

    // Verificar si tiene roles
    if (user.roles && Array.isArray(user.roles)) {
      const roleNames = user.roles.map((r: any) => {
        const roleName = r.name || r.nombre || '';
        return roleName.toLowerCase();
      }).filter(Boolean);
      return roleNames;
    }
    return [];
  },

  hasRole(role: string | string[]): boolean {
    const userRoles = this.getUserRoles();
    if (Array.isArray(role)) {
      return role.some(r => userRoles.includes(r));
    }
    return userRoles.includes(role);
  },

  hasAnyRole(roles: string[]): boolean {
    const userRoles = this.getUserRoles();
    const hasAny = roles.some(role => userRoles.includes(role));
    return hasAny;
  },

  hasAllRoles(roles: string[]): boolean {
    const userRoles = this.getUserRoles();
    return roles.every(role => userRoles.includes(role));
  },

  getUserPermissions(): string[] {
    const user = this.getUser();
    if (!user || !user.permissions) return [];
    return user.permissions
      .map(p => p.name || p.nombre) // usa cualquiera disponible
      .filter((n): n is string => Boolean(n)); // filtra valores vacíos o undefined
  },

  hasPermission(permission: string | string[]): boolean {
    const userPermissions = this.getUserPermissions();
    if (Array.isArray(permission)) {
      return permission.some(p => userPermissions.includes(p));
    }
    return userPermissions.includes(permission);
  },

  // ================================================= //

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