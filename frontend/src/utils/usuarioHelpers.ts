// utils/usuarioHelpers.ts

import type { Rol, Usuario } from "@/types/User";



/**
 * Obtiene el nombre del rol de un usuario (compatible con Spatie y legacy)
 */
export const getRolNombre = (usuario: Usuario, roles?: Rol[]): string => {
  // 1. Intenta obtener desde el array de roles de Spatie
  if (usuario.roles && usuario.roles.length > 0) {
    const role = usuario.roles[0];
    return role.nombre || role.name || 'Sin rol';
  }
  
  // 2. Intenta obtener desde el objeto rol único
  if (usuario.rol) {
    return usuario.rol.nombre || usuario.rol.name || 'Sin rol';
  }
  
  // 3. Busca en el array de roles por rol_id
  if (usuario.rol_id && roles) {
    const rol = roles.find((r) => r.id === usuario.rol_id);
    return rol ? (rol.nombre || rol.name || 'Sin rol') : 'Sin rol';
  }
  
  return 'Sin rol';
};

/**
 * Obtiene el ID del rol de un usuario
 */
export const getRolId = (usuario: Usuario): number | undefined => {
  // 1. Desde rol_id directo
  if (usuario.rol_id) {
    return usuario.rol_id;
  }
  
  // 2. Desde array de roles de Spatie
  if (usuario.roles && usuario.roles.length > 0) {
    return usuario.roles[0].id;
  }
  
  // 3. Desde objeto rol único
  if (usuario.rol) {
    return usuario.rol.id;
  }
  
  return undefined;
};

/**
 * Obtiene el color del badge del rol
 */
export const getRolColor = (usuario: Usuario, roles?: Rol[]): string => {
  const rolNombre = getRolNombre(usuario, roles).toLowerCase();
  
  const colorMap: Record<string, string> = {
    'admin': 'bg-purple-100 text-purple-800',
    'administrador': 'bg-purple-100 text-purple-800',
    'vendedor': 'bg-blue-100 text-blue-800',
    'cajero': 'bg-green-100 text-green-800',
    'contador': 'bg-yellow-100 text-yellow-800',
  };
  
  return colorMap[rolNombre] || 'bg-gray-100 text-gray-800';
};

/**
 * Normaliza el nombre del rol (de 'name' o 'nombre' a un solo campo)
 */
export const normalizeRolNombre = (rol: Rol): string => {
  return rol.nombre || rol.name || 'Sin nombre';
};