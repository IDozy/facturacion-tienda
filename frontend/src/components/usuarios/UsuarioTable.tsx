// components/TablaUsuarios.tsx

import React from 'react';
import { Edit2, Trash2, Loader } from 'lucide-react';

import { getRolNombre, getRolColor } from '@/utils/usuarioHelpers';
import type { Rol, Usuario } from '@/types/User';

interface TablaUsuariosProps {
  usuarios: Usuario[];
  roles: Rol[];
  loading: boolean;
  onEdit: (usuario: Usuario) => void;
  onDelete: (id: number) => void;
}

export const TablaUsuarios: React.FC<TablaUsuariosProps> = ({
  usuarios,
  roles,
  loading,
  onEdit,
  onDelete,
}) => {
  if (loading && usuarios.length === 0) {
    return (
      <div className="p-8 text-center text-gray-500 flex items-center justify-center gap-2">
        <Loader className="w-5 h-5 animate-spin" />
        Cargando usuarios...
      </div>
    );
  }

  if (usuarios.length === 0) {
    return (
      <div className="p-8 text-center text-gray-500">
        No hay usuarios para mostrar
      </div>
    );
  }

  return (
    <div className="overflow-x-auto">
      <table className="w-full">
        <thead className="bg-gray-50 border-b">
          <tr>
            <th className="px-6 py-3 text-left text-sm font-semibold text-gray-900">Nombre</th>
            <th className="px-6 py-3 text-left text-sm font-semibold text-gray-900">Email</th>
            <th className="px-6 py-3 text-left text-sm font-semibold text-gray-900">Documento</th>
            <th className="px-6 py-3 text-left text-sm font-semibold text-gray-900">Teléfono</th>
            <th className="px-6 py-3 text-left text-sm font-semibold text-gray-900">Rol</th>
            <th className="px-6 py-3 text-left text-sm font-semibold text-gray-900">Estado</th>
            <th className="px-6 py-3 text-left text-sm font-semibold text-gray-900">Acciones</th>
          </tr>
        </thead>
        <tbody className="divide-y">
          {usuarios.map((usuario) => (
            <tr key={usuario.id} className="hover:bg-gray-50 transition">
              <td className="px-6 py-4 text-sm text-gray-900 font-medium">
                {usuario.nombre}
              </td>
              <td className="px-6 py-4 text-sm text-gray-600">
                {usuario.email}
              </td>
              <td className="px-6 py-4 text-sm text-gray-600">
                <div className="flex items-center gap-2">
                  <span className="text-xs bg-gray-100 px-2 py-1 rounded font-medium">
                    {usuario.tipo_documento}
                  </span>
                  <span>{usuario.numero_documento}</span>
                </div>
              </td>
              <td className="px-6 py-4 text-sm text-gray-600">
                {usuario.telefono || '-'}
              </td>
              <td className="px-6 py-4 text-sm">
                <span className={`inline-block px-3 py-1 rounded-full text-xs font-medium ${getRolColor(usuario, roles)}`}>
                  {getRolNombre(usuario, roles)}
                </span>
              </td>
              <td className="px-6 py-4 text-sm">
                <span
                  className={`inline-block px-3 py-1 rounded-full text-xs font-medium ${
                    usuario.activo
                      ? 'bg-green-100 text-green-800'
                      : 'bg-red-100 text-red-800'
                  }`}
                >
                  {usuario.activo ? 'Activo' : 'Inactivo'}
                </span>
              </td>
              <td className="px-6 py-4 text-sm">
                <div className="flex gap-2">
                  <button
                    onClick={() => onEdit(usuario)}
                    className="p-2 text-blue-600 hover:bg-blue-50 rounded transition"
                    title="Editar"
                    aria-label={`Editar ${usuario.nombre}`}
                  >
                    <Edit2 className="w-4 h-4" />
                  </button>
                  <button
                    onClick={() => onDelete(usuario.id)}
                    className="p-2 text-red-600 hover:bg-red-50 rounded transition"
                    title="Eliminar"
                    aria-label={`Eliminar ${usuario.nombre}`}
                  >
                    <Trash2 className="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};