// components/inventario/AlmacenModal.tsx
'use client';

import { useState, useEffect } from 'react';
import { X } from 'lucide-react';
import almacenService from '@/services/almacenService';
import type { Almacen, AlmacenFormData } from '@/types/Almacen';
import { toast } from 'react-hot-toast';

interface AlmacenModalProps {
  almacen: Almacen | null;
  onClose: () => void;
  onSuccess: () => void;
}

export default function AlmacenModal({
  almacen,
  onClose,
  onSuccess,
}: AlmacenModalProps) {
  const [formData, setFormData] = useState<AlmacenFormData>({
    nombre: '',
    ubicacion: '',
    activo: true,
  });

  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (almacen) {
      setFormData({
        nombre: almacen.nombre,
        ubicacion: almacen.ubicacion || '',
        activo: almacen.activo,
      });
    }
  }, [almacen]);

  const handleChange = (
    e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>
  ) => {
    const { name, value, type } = e.target;
    setFormData({
      ...formData,
      [name]: type === 'checkbox' ? (e.target as HTMLInputElement).checked : value,
    });
    // Limpiar error del campo
    if (errors[name]) {
      setErrors({ ...errors, [name]: [] });
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setErrors({});

    // 🔍 DEBUG: Ver qué se está enviando
    console.log('=== DEBUG UPDATE ===');
    console.log('Almacén ID:', almacen?.id);
    console.log('Almacén empresa_id original:', almacen?.empresa_id);
    console.log('User data:', localStorage.getItem('user'));
    console.log('Form data a enviar:', formData);
    console.log('Token:', localStorage.getItem('token'));

    try {
      if (almacen) {
        await almacenService.updateAlmacen(almacen.id, formData);
        toast.success('Almacén actualizado exitosamente');
      } else {
        await almacenService.createAlmacen(formData);
        toast.success('Almacén creado exitosamente');
      }
      onSuccess();
    } catch (error: any) {
      console.error('Error completo:', error);
      console.error('Response data:', error.response?.data);
      
      if (error.response?.data?.errors) {
        setErrors(error.response.data.errors);
      } else {
        const message = error.response?.data?.message || 'Error al guardar el almacén';
        toast.error(message);
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="p-6 border-b sticky top-0 bg-white flex items-center justify-between">
          <h2 className="text-xl font-semibold text-gray-900">
            {almacen ? 'Editar Almacén' : 'Nuevo Almacén'}
          </h2>
          <button
            onClick={onClose}
            disabled={loading}
            className="text-gray-400 hover:text-gray-600 transition-colors disabled:opacity-50"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          {/* Nombre */}
          <div>
            <label
              htmlFor="nombre"
              className="block text-sm font-medium text-gray-700 mb-1"
            >
              Nombre <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              id="nombre"
              name="nombre"
              value={formData.nombre}
              onChange={handleChange}
              disabled={loading}
              className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed ${
                errors.nombre ? 'border-red-500' : 'border-gray-300'
              }`}
              placeholder="Ej: Almacén Principal"
              required
            />
            {errors.nombre && (
              <p className="mt-1 text-sm text-red-600">{errors.nombre[0]}</p>
            )}
          </div>

          {/* Ubicación */}
          <div>
            <label
              htmlFor="ubicacion"
              className="block text-sm font-medium text-gray-700 mb-1"
            >
              Ubicación
            </label>
            <textarea
              id="ubicacion"
              name="ubicacion"
              value={formData.ubicacion}
              onChange={handleChange}
              disabled={loading}
              rows={3}
              className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed ${
                errors.ubicacion ? 'border-red-500' : 'border-gray-300'
              }`}
              placeholder="Ej: Av. Industrial 123, Lima"
            />
            {errors.ubicacion && (
              <p className="mt-1 text-sm text-red-600">{errors.ubicacion[0]}</p>
            )}
          </div>

          {/* Estado Activo */}
          <div className="flex items-center p-3 bg-gray-50 rounded-lg">
            <input
              type="checkbox"
              id="activo"
              name="activo"
              checked={formData.activo}
              onChange={handleChange}
              disabled={loading}
              className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 disabled:opacity-50"
            />
            <label htmlFor="activo" className="ml-2 text-sm font-medium text-gray-700">
              Almacén activo
            </label>
          </div>

          {/* Buttons */}
          <div className="flex items-center justify-end gap-3 pt-4 border-t">
            <button
              type="button"
              onClick={onClose}
              disabled={loading}
              className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Cancelar
            </button>
            <button
              type="submit"
              disabled={loading}
              className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed font-medium flex items-center gap-2"
            >
              {loading && (
                <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
              )}
              {loading ? 'Guardando...' : almacen ? 'Actualizar' : 'Crear'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}