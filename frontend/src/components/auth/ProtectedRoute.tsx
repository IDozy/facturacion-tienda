// components/auth/ProtectedRoute.tsx

import React from 'react';
import { Navigate } from 'react-router-dom';
import { useAuth } from '@/contexts/AuthContext';

interface ProtectedRouteProps {
  children: React.ReactNode;
  roles?: string[];
  permissions?: string[];
  requireAll?: boolean; // Si true, requiere TODOS los roles/permisos
  fallback?: React.ReactNode;
}

export const ProtectedRoute: React.FC<ProtectedRouteProps> = ({
  children,
  roles = [],
  permissions = [],
  requireAll = false,
  fallback,
}) => {
  const { user, hasRole, hasPermission, hasAnyRole, hasAllRoles, loading } = useAuth();

  if (loading) {
    return <div>Cargando...</div>;
  }

  if (!user) {
    return <Navigate to="/login" replace />;
  }

  // Verificar roles
  if (roles.length > 0) {
    const hasRequiredRoles = requireAll ? hasAllRoles(roles) : hasAnyRole(roles);
    
    if (!hasRequiredRoles) {
      return fallback ? <>{fallback}</> : <Navigate to="/unauthorized" replace />;
    }
  }

  // Verificar permisos
  if (permissions.length > 0) {
    const hasRequiredPermissions = requireAll
      ? permissions.every(p => hasPermission(p))
      : hasPermission(permissions);
    
    if (!hasRequiredPermissions) {
      return fallback ? <>{fallback}</> : <Navigate to="/unauthorized" replace />;
    }
  }

  return <>{children}</>;
};