// components/ModalUsuario.tsx

import React, { useState, useEffect } from 'react';
import { Eye, EyeOff, Loader, X } from 'lucide-react';
import type { CreateUsuarioDTO, Rol, UpdateUsuarioDTO, Usuario } from '@/types/User';


interface ModalUsuarioProps {
  usuario: Usuario | null;
  roles: Rol[];
  onGuardar: (data: CreateUsuarioDTO | UpdateUsuarioDTO) => Promise<void>;
  onCerrar: () => void;
  loading: boolean;
}

const TIPOS_DOCUMENTO = ['DNI', 'RUC', 'PASAPORTE'] as const;

export const ModalUsuario: React.FC<ModalUsuarioProps> = ({
  usuario,
  roles,
  onGuardar,
  onCerrar,
  loading,
}) => {
  const [showPassword, setShowPassword] = useState(false);
  const [formData, setFormData] = useState<CreateUsuarioDTO | UpdateUsuarioDTO>({
    nombre: '',
    email: '',
    password: '',
    rol_id: 0,
    numero_documento: '',
    tipo_documento: 'DNI',
    telefono: '',
    activo: true,
  });

  useEffect(() => {
    if (usuario) {
      setFormData({
        nombre: usuario.nombre,
        email: usuario.email,
        password: '',
        rol_id: usuario.rol_id,
        numero_documento: usuario.numero_documento,
        tipo_documento: usuario.tipo_documento,
        telefono: usuario.telefono,
        activo: usuario.activo,
      });
    }
  }, [usuario]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    await onGuardar(formData);
  };

  const handleChange = (
    e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>
  ) => {
    const { name, value, type } = e.target as any;
    setFormData({
      ...formData,
      [name]: type === 'checkbox' ? (e.target as HTMLInputElement).checked : value,
    });
  };

  const isFormValid = () => {
    if (!usuario && !formData.password) return false;
    if (!formData.nombre || !formData.email || !formData.rol_id) return false;
    return true;
  };

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
      <div className="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="p-6 border-b sticky top-0 bg-white flex items-center justify-between">
          <h2 className="text-xl font-bold text-gray-900">
            {usuario ? 'Editar Usuario' : 'Nuevo Usuario'}
          </h2>
          <button
            onClick={onCerrar}
            className="text-gray-500 hover:text-gray-700 transition"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Contenido */}
        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          {/* Nombre */}
          <div>
            <label htmlFor="nombre" className="block text-sm font-medium text-gray-700 mb-1">
              Nombre *
            </label>
            <input
              id="nombre"
              type="text"
              name="nombre"
              required
              value={formData.nombre}
              onChange={handleChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Juan Pérez"
            />
          </div>

          {/* Email */}
          <div>
            <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">
              Email *
            </label>
            <input
              id="email"
              type="email"
              name="email"
              required
              value={formData.email}
              onChange={handleChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="juan@example.com"
            />
          </div>

          {/* Contraseña */}
          <div>
            <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-1">
              Contraseña {usuario ? '(opcional)' : '*'}
            </label>
            <div className="relative">
              <input
                id="password"
                type={showPassword ? 'text' : 'password'}
                name="password"
                required={!usuario}
                value={formData.password}
                onChange={handleChange}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder={usuario ? 'Dejar vacío para mantener la actual' : '••••••'}
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 transition"
              >
                {showPassword ? (
                  <EyeOff className="w-5 h-5" />
                ) : (
                  <Eye className="w-5 h-5" />
                )}
              </button>
            </div>
          </div>

          {/* Tipo de Documento */}
          <div>
            <label htmlFor="tipo_documento" className="block text-sm font-medium text-gray-700 mb-1">
              Tipo de Documento
            </label>
            <select
              id="tipo_documento"
              name="tipo_documento"
              value={formData.tipo_documento}
              onChange={handleChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              {TIPOS_DOCUMENTO.map((tipo) => (
                <option key={tipo} value={tipo}>
                  {tipo}
                </option>
              ))}
            </select>
          </div>

          {/* Número de Documento */}
          <div>
            <label htmlFor="numero_documento" className="block text-sm font-medium text-gray-700 mb-1">
              Número de Documento
            </label>
            <input
              id="numero_documento"
              type="text"
              name="numero_documento"
              value={formData.numero_documento}
              onChange={handleChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="12345678"
            />
          </div>

          {/* Teléfono */}
          <div>
            <label htmlFor="telefono" className="block text-sm font-medium text-gray-700 mb-1">
              Teléfono
            </label>
            <input
              id="telefono"
              type="tel"
              name="telefono"
              value={formData.telefono}
              onChange={handleChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="999888777"
            />
          </div>

          {/* Rol */}
          <div>
            <label htmlFor="rol_id" className="block text-sm font-medium text-gray-700 mb-1">
              Rol *
            </label>
            <select
              id="rol_id"
              name="rol_id"
              required
              value={formData.rol_id}
              onChange={handleChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">Selecciona un rol</option>
              {roles.map((rol) => (
                <option key={rol.id} value={rol.id}>
                  {rol.nombre}
                </option>
              ))}
            </select>
          </div>

          {/* Estado */}
          <div className="flex items-center p-3 bg-gray-50 rounded-lg">
            <input
              id="activo"
              type="checkbox"
              name="activo"
              checked={formData.activo}
              onChange={handleChange}
              className="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
            />
            <label htmlFor="activo" className="ml-2 text-sm font-medium text-gray-700">
              Usuario Activo
            </label>
          </div>

          {/* Botones */}
          <div className="flex gap-3 pt-4 border-t">
            <button
              type="button"
              onClick={onCerrar}
              className="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition font-medium"
            >
              Cancelar
            </button>
            <button
              type="submit"
              disabled={loading || !isFormValid()}
              className="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed font-medium flex items-center justify-center gap-2"
            >
              {loading && <Loader className="w-4 h-4 animate-spin" />}
              {loading ? 'Guardando...' : usuario ? 'Actualizar' : 'Crear'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};