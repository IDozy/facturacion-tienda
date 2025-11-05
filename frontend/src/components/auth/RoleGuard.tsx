// components/auth/RoleGuard.tsx

import React from 'react';
import { useAuth } from '@/contexts/AuthContext';

interface RoleGuardProps {
  children: React.ReactNode;
  roles?: string[];
  permissions?: string[];
  requireAll?: boolean;
  fallback?: React.ReactNode;
}

export const RoleGuard: React.FC<RoleGuardProps> = ({
  children,
  roles = [],
  permissions = [],
  requireAll = false,
  fallback = null,
}) => {
  const { hasRole, hasPermission, hasAnyRole, hasAllRoles } = useAuth();

  // Verificar roles
  if (roles.length > 0) {
    const hasRequiredRoles = requireAll ? hasAllRoles(roles) : hasAnyRole(roles);
    if (!hasRequiredRoles) return <>{fallback}</>;
  }

  // Verificar permisos
  if (permissions.length > 0) {
    const hasRequiredPermissions = requireAll
      ? permissions.every(p => hasPermission(p))
      : hasPermission(permissions);
    if (!hasRequiredPermissions) return <>{fallback}</>;
  }

  return <>{children}</>;
};