// pages/UsuariosPage.tsx

import React, { useState, useEffect } from 'react';
import { Plus, Search, AlertCircle, CheckCircle, Loader } from 'lucide-react';
import type { CreateUsuarioDTO, Rol, UpdateUsuarioDTO, Usuario } from '@/types/User';
import { usuarioService } from '@/services/userService';
import { TablaUsuarios } from '@/components/usuarios/UsuarioTable';
import { ModalUsuario } from '@/components/usuarios/UsuarioModal';

export const UsuariosPage: React.FC = () => {
  const [usuarios, setUsuarios] = useState<Usuario[]>([]);
  const [roles, setRoles] = useState<Rol[]>([]);
  const [loading, setLoading] = useState(false);
  const [initialLoading, setInitialLoading] = useState(true); // ← Nuevo: para carga inicial
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
      const timer = setTimeout(() => setSuccess(''), 5000);
      return () => clearTimeout(timer);
    }
  }, [success]);

  const cargarDatos = async () => {
    try {
      setLoading(true);
      const [usuariosData, rolesData] = await Promise.all([
        usuarioService.obtenerUsuarios(),
        usuarioService.obtenerRoles(),
      ]);
      setUsuarios(usuariosData);
      setRoles(rolesData);
      setError(''); // Limpia errores anteriores si la carga es exitosa
    } catch (err) {

      setError(err instanceof Error ? err.message : 'Error al cargar datos');
    } finally {
      setLoading(false);
      setInitialLoading(false); // ← Marca que la carga inicial terminó
    }
  };

  const handleGuardar = async (formData: CreateUsuarioDTO | UpdateUsuarioDTO) => {
    try {
      setLoading(true);
      setError(''); // Limpia errores anteriores

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
      throw err; // ← Para que el modal también pueda manejar el error
    } finally {
      setLoading(false);
    }
  };

  const handleEliminar = async (id: number) => {
    const usuario = usuarios.find(u => u.id === id);
    const mensaje = usuario
      ? `¿Está seguro de que desea eliminar al usuario "${usuario.nombre}"?`
      : '¿Está seguro de que desea eliminar este usuario?';

    if (!window.confirm(mensaje)) return;

    try {
      setLoading(true);
      setError(''); // Limpia errores anteriores
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
    setError(''); // Limpia errores al abrir modal
  };

  const handleEditar = (usuario: Usuario) => {
    setEditingUsuario(usuario);
    setShowModal(true);
    setError(''); // Limpia errores al abrir modal
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

  // ← Pantalla de carga inicial
  if (initialLoading) {
    return (
      <div className="p-8 max-w-7xl mx-auto">
        <div className="flex items-center justify-center h-64">
          <div className="flex flex-col items-center gap-3">
            <Loader className="w-8 h-8 animate-spin text-blue-600" />
            <p className="text-gray-600">Cargando usuarios...</p>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="p-8 max-w-7xl mx-auto">
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900 mb-2">Gestión de Usuarios</h1>
        <p className="text-gray-600">Administra los usuarios del sistema</p>
      </div>

      {/* Mensajes */}
      {error && (
        <div className="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center gap-3 animate-in fade-in slide-in-from-top-2 duration-300">
          <AlertCircle className="w-5 h-5 text-red-600 flex-shrink-0" />
          <span className="text-red-700">{error}</span>
          <button
            onClick={() => setError('')}
            className="ml-auto text-red-600 hover:text-red-800"
          >
            ✕
          </button>
        </div>
      )}

      {success && (
        <div className="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center gap-3 animate-in fade-in slide-in-from-top-2 duration-300">
          <CheckCircle className="w-5 h-5 text-green-600 flex-shrink-0" />
          <span className="text-green-700">{success}</span>
          <button
            onClick={() => setSuccess('')}
            className="ml-auto text-green-600 hover:text-green-800"
          >
            ✕
          </button>
        </div>
      )}

      {/* Barra de herramientas */}
      <div className="mb-6 flex flex-col md:flex-row gap-4 justify-between items-start md:items-center">
        <div className="relative flex-1 max-w-md">
          <Search className="absolute left-3 top-3 w-5 h-5 text-gray-400" />
          <input
            type="text"
            placeholder="Buscar por nombre, email o documento..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>

        <button
          onClick={handleNuevo}
          disabled={loading}
          className="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <Plus className="w-5 h-5" />
          Nuevo Usuario
        </button>
      </div>

      {/* Contador de resultados */}
      {search && (
        <div className="mb-4 text-sm text-gray-600">
          {filteredUsuarios.length === 0
            ? 'No se encontraron usuarios'
            : `${filteredUsuarios.length} usuario${filteredUsuarios.length !== 1 ? 's' : ''} encontrado${filteredUsuarios.length !== 1 ? 's' : ''}`
          }
        </div>
      )}

      {/* Tabla */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <TablaUsuarios
          usuarios={filteredUsuarios}
          roles={roles}
          loading={loading}
          onEdit={handleEditar}
          onDelete={handleEliminar}
        />
      </div>

      {/* Modal */}
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