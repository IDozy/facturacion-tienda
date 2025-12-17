// pages/UsuariosPage.tsx

import React, { useState, useEffect } from 'react';
import { Plus, Search, AlertCircle, CheckCircle, Loader, Shield } from 'lucide-react';
import type { CreateUsuarioDTO, Rol, UpdateUsuarioDTO, Usuario } from '@/types/User';
import { usuarioService } from '@/services/userService';
import { TablaUsuarios } from '@/components/usuarios/UsuarioTable';
import { ModalUsuario } from '@/components/usuarios/UsuarioModal';

export const UsuariosPage: React.FC = () => {
  const [usuarios, setUsuarios] = useState<Usuario[]>([]);
  const [roles, setRoles] = useState<Rol[]>([]);
  const [loading, setLoading] = useState(false);
  const [initialLoading, setInitialLoading] = useState(true);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [search, setSearch] = useState('');
  const [showModal, setShowModal] = useState(false);
  const [editingUsuario, setEditingUsuario] = useState<Usuario | null>(null);

  useEffect(() => {
    cargarDatos();
  }, []);

  useEffect(() => {
    if (error) {
      const timer = setTimeout(() => setError(''), 5000);
      return () => clearTimeout(timer);
    }
  }, [error]);

  useEffect(() => {
    if (success) {
      const timer = setTimeout(() => setSuccess(''), 4000);
      return () => clearTimeout(timer);
    }
  }, [success]);

  const cargarDatos = async () => {
    try {
      setLoading(true);
      const [usuariosResponse, rolesData] = await Promise.all([
        usuarioService.obtenerUsuarios(),
        usuarioService.obtenerRoles(),
      ]);

      setUsuarios(usuariosResponse.data);
      setRoles(rolesData);
      setError('');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Error al cargar datos');
    } finally {
      setLoading(false);
      setInitialLoading(false);
    }
  };

  const handleGuardar = async (formData: CreateUsuarioDTO | UpdateUsuarioDTO) => {
    try {
      setLoading(true);
      setError('');

      if (editingUsuario) {
        await usuarioService.actualizarUsuario(editingUsuario.id, formData as UpdateUsuarioDTO);
        setSuccess('Usuario actualizado correctamente');
      } else {
        await usuarioService.crearUsuario(formData as CreateUsuarioDTO);
        setSuccess('Usuario creado exitosamente');
      }

      setShowModal(false);
      setEditingUsuario(null);
      await cargarDatos();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Error al guardar usuario');
      throw err;
    } finally {
      setLoading(false);
    }
  };

  const handleEliminar = async (id: number) => {
    const usuario = usuarios.find((u) => u.id === id);
    const mensaje = usuario
      ? `¿Está seguro de que desea eliminar al usuario "${usuario.nombre}"?`
      : '¿Está seguro de que desea eliminar este usuario?';

    if (!window.confirm(mensaje)) return;

    try {
      setLoading(true);
      setError('');
      await usuarioService.eliminarUsuario(id);
      setSuccess('Usuario eliminado correctamente');
      await cargarDatos();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Error al eliminar usuario');
    } finally {
      setLoading(false);
    }
  };

  const handleNuevo = () => {
    setEditingUsuario(null);
    setShowModal(true);
    setError('');
  };

  const handleEditar = (usuario: Usuario) => {
    setEditingUsuario(usuario);
    setShowModal(true);
    setError('');
  };

  const handleCerrarModal = () => {
    setShowModal(false);
    setEditingUsuario(null);
  };

  const filteredUsuarios = usuarios.filter(
    (u) =>
      u.nombre.toLowerCase().includes(search.toLowerCase()) ||
      u.email.toLowerCase().includes(search.toLowerCase()) ||
      u.numero_documento?.includes(search)
  );

  if (initialLoading) {
    return (
      <div className="p-8 max-w-7xl mx-auto space-y-6">
        <div className="flex items-center gap-3">
          <Loader className="w-6 h-6 animate-spin text-blue-600" />
          <div>
            <p className="text-lg font-semibold text-gray-900">Cargando usuarios</p>
            <p className="text-sm text-gray-500">Preparando el panel de usuarios...</p>
          </div>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {[1, 2, 3, 4].map((skeleton) => (
            <div
              key={skeleton}
              className="animate-pulse rounded-xl border border-gray-100 bg-white p-4 shadow-sm"
            >
              <div className="h-4 w-1/3 rounded bg-gray-200" />
              <div className="mt-3 h-3 w-2/3 rounded bg-gray-100" />
              <div className="mt-4 h-10 rounded bg-gray-100" />
            </div>
          ))}
        </div>
      </div>
    );
  }

  return (
    <div className="p-8 max-w-7xl mx-auto space-y-6">
      <div className="flex flex-col gap-2">
        <div className="flex items-center gap-2 text-sm text-blue-600">
          <Shield className="w-4 h-4" />
          <span>Configuración · Seguridad y accesos</span>
        </div>
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Gestión de Usuarios</h1>
            <p className="text-gray-600">Administra cuentas, roles y accesos de tu equipo.</p>
          </div>
          <div className="flex gap-2">
            <button
              onClick={handleNuevo}
              disabled={loading}
              className="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
            >
              <Plus className="w-4 h-4" />
              Nuevo usuario
            </button>
          </div>
        </div>
      </div>

      {error && (
        <div className="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
          <AlertCircle className="mt-0.5 h-5 w-5 flex-shrink-0" />
          <div className="flex-1">
            <p className="font-semibold">No se pudo completar la operación</p>
            <p className="text-red-700">{error}</p>
          </div>
          <button
            onClick={() => setError('')}
            className="text-red-600 transition hover:text-red-800"
            aria-label="Cerrar alerta de error"
          >
            ✕
          </button>
        </div>
      )}

      {success && (
        <div className="flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">
          <CheckCircle className="mt-0.5 h-5 w-5 flex-shrink-0" />
          <div className="flex-1">
            <p className="font-semibold">Acción completada</p>
            <p className="text-green-700">{success}</p>
          </div>
          <button
            onClick={() => setSuccess('')}
            className="text-green-700 transition hover:text-green-900"
            aria-label="Cerrar alerta de éxito"
          >
            ✕
          </button>
        </div>
      )}

      <div className="flex flex-col gap-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div className="relative flex-1 min-w-0">
          <Search className="absolute left-3 top-3 h-5 w-5 text-gray-400" />
          <input
            type="text"
            placeholder="Buscar por nombre, email o documento"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 py-2 text-sm focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100"
          />
        </div>
        <div className="text-xs text-gray-500">
          {filteredUsuarios.length} resultado{filteredUsuarios.length === 1 ? '' : 's'}
        </div>
      </div>

      <div className="rounded-2xl border border-gray-100 bg-white shadow-sm">
        <TablaUsuarios
          usuarios={filteredUsuarios}
          roles={roles}
          loading={loading}
          onEdit={handleEditar}
          onDelete={handleEliminar}
        />
      </div>

      {filteredUsuarios.length === 0 && !loading && (
        <div className="rounded-2xl border border-dashed border-gray-200 bg-white p-10 text-center shadow-sm">
          <p className="text-lg font-semibold text-gray-900">Aún no hay usuarios registrados</p>
          <p className="mt-2 text-gray-600">
            Crea el primer usuario para empezar a invitar a tu equipo y controlar sus accesos.
          </p>
          <div className="mt-4 flex justify-center">
            <button
              onClick={handleNuevo}
              className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
            >
              <Plus className="h-4 w-4" />
              Crear usuario
            </button>
          </div>
        </div>
      )}

      {showModal && (
        <ModalUsuario
          usuario={editingUsuario}
          roles={roles}
          onGuardar={handleGuardar}
          onCerrar={handleCerrarModal}
          loading={loading}
        />
      )}
    </div>
  );
};
