// components/auth/ProtectedRoute-debug.tsx

import React from 'react';
import { Navigate } from 'react-router-dom';
import { useAuth } from '@/contexts/AuthContext';

interface ProtectedRouteProps {
  children: React.ReactNode;
  roles?: string[];
  permissions?: string[];
  requireAll?: boolean;
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

  // 🐛 DEBUG: Agregar logs para ver qué está pasando
  console.log('=== ProtectedRoute Debug ===');
  console.log('Loading:', loading);
  console.log('User:', user);
  console.log('Required roles:', roles);
  console.log('Required permissions:', permissions);
  console.log('RequireAll:', requireAll);

  // Verificar si los métodos existen
  console.log('hasRole method exists:', typeof hasRole === 'function');
  console.log('hasAnyRole method exists:', typeof hasAnyRole === 'function');
  console.log('hasAllRoles method exists:', typeof hasAllRoles === 'function');
  console.log('hasPermission method exists:', typeof hasPermission === 'function');

  if (loading) {
    return <div>Cargando...</div>;
  }

  if (!user) {
    console.log('❌ No user found, redirecting to login');
    return <Navigate to="/login" replace />;
  }

  // Si no se requieren roles ni permisos, permitir acceso
  if (roles.length === 0 && permissions.length === 0) {
    console.log('✅ No roles or permissions required, allowing access');
    return <>{children}</>;
  }

  // Verificar roles
  if (roles.length > 0) {
    console.log('🔍 Checking roles...');
    
    if (typeof hasAnyRole !== 'function' || typeof hasAllRoles !== 'function') {
      console.error('❌ Role checking methods not available');
      return fallback ? <>{fallback}</> : <Navigate to="/unauthorized" replace />;
    }

    const hasRequiredRoles = requireAll ? hasAllRoles(roles) : hasAnyRole(roles);
    console.log('Has required roles:', hasRequiredRoles);
    
    if (!hasRequiredRoles) {
      console.log('❌ User does not have required roles');
      return fallback ? <>{fallback}</> : <Navigate to="/unauthorized" replace />;
    }
  }

  // Verificar permisos
  if (permissions.length > 0) {
    console.log('🔍 Checking permissions...');
    
    if (typeof hasPermission !== 'function') {
      console.error('❌ Permission checking method not available');
      return fallback ? <>{fallback}</> : <Navigate to="/unauthorized" replace />;
    }

    const hasRequiredPermissions = requireAll
      ? permissions.every(p => hasPermission(p))
      : hasPermission(permissions);
    
    console.log('Has required permissions:', hasRequiredPermissions);
    
    if (!hasRequiredPermissions) {
      console.log('❌ User does not have required permissions');
      return fallback ? <>{fallback}</> : <Navigate to="/unauthorized" replace />;
    }
  }

  console.log('✅ Access granted');
  return <>{children}</>;
};