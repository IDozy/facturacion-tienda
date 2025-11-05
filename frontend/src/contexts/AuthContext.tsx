// contexts/AuthContext.tsx

import type { Rol, Usuario } from '@/types/User';
import React, { createContext, useContext, useState, useEffect } from 'react';


interface AuthContextType {
  user: Usuario | null;
  roles: string[];
  permissions: string[];
  loading: boolean;
  hasRole: (role: string | string[]) => boolean;
  hasPermission: (permission: string | string[]) => boolean;
  hasAnyRole: (roles: string[]) => boolean;
  hasAllRoles: (roles: string[]) => boolean;
  login: (token: string, userData: Usuario) => void;
  logout: () => void;
  refreshUser: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<Usuario | null>(null);
  const [roles, setRoles] = useState<string[]>([]);
  const [permissions, setPermissions] = useState<string[]>([]);
  const [loading, setLoading] = useState(true);

  // Cargar usuario desde localStorage al iniciar
  useEffect(() => {
    const loadUser = () => {
      try {
        const token = localStorage.getItem('token');
        const userData = localStorage.getItem('user');
        
        if (token && userData) {
          const parsedUser = JSON.parse(userData);
          setUser(parsedUser);
          
          // Extraer roles del usuario
          const userRoles = parsedUser.roles?.map((r: Rol) => r.name || r.nombre) || [];
          setRoles(userRoles);
          
          // Extraer permisos si existen
          const userPermissions = parsedUser.permissions?.map((p: any) => p.name) || [];
          setPermissions(userPermissions);
          console.log(userPermissions ," permisos de usuario");
        }
      } catch (error) {
        console.error('Error loading user:', error);
      } finally {
        setLoading(false);
      }
    };

    loadUser();
  }, []);

  // Verificar si tiene un rol específico
  const hasRole = (role: string | string[]): boolean => {
    if (Array.isArray(role)) {
      return role.some(r => roles.includes(r));
    }
    return roles.includes(role);
  };

  // Verificar si tiene alguno de los roles
  const hasAnyRole = (rolesToCheck: string[]): boolean => {
    return rolesToCheck.some(role => roles.includes(role));
  };

  // Verificar si tiene todos los roles
  const hasAllRoles = (rolesToCheck: string[]): boolean => {
    return rolesToCheck.every(role => roles.includes(role));
  };

  // Verificar si tiene un permiso específico
  const hasPermission = (permission: string | string[]): boolean => {
    if (Array.isArray(permission)) {
      return permission.some(p => permissions.includes(p));
    }
    return permissions.includes(permission);
  };

  // Login
  const login = (token: string, userData: Usuario) => {
  localStorage.setItem('token', token);
  localStorage.setItem('user', JSON.stringify(userData));
  setUser(userData);

  // 👇 roles
  const userRoles = (userData.roles?.map((r: Rol) => r.name || r.nombre) || [])
    .filter((r): r is string => r !== undefined);
  setRoles(userRoles);

  // 👇 permisos
  const userPermissions = (userData.permissions?.map((p: any) => p.name) || [])
    .filter((p): p is string => p !== undefined);
  setPermissions(userPermissions);
};


  // Logout
  const logout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    setUser(null);
    setRoles([]);
    setPermissions([]);
  };

  // Refrescar datos del usuario
  const refreshUser = async () => {
    try {
      // Aquí deberías llamar a tu endpoint de perfil
      // const response = await api.get('/user/profile');
      // const userData = response.data.data;
      // setUser(userData);
      // ... actualizar roles y permisos
    } catch (error) {
      console.error('Error refreshing user:', error);
    }
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        roles,
        permissions,
        loading,
        hasRole,
        hasPermission,
        hasAnyRole,
        hasAllRoles,
        login,
        logout,
        refreshUser,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};