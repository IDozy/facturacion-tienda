import React, { useEffect, useMemo, useState } from 'react';
import { Eye, EyeOff, Loader, X } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import type { CreateUsuarioDTO, Rol, UpdateUsuarioDTO, Usuario } from '@/types/User';
import { getRolId, normalizeRolNombre } from '@/utils/usuarioHelpers';

interface ModalUsuarioProps {
  usuario: Usuario | null;
  roles: Rol[];
  onGuardar: (data: CreateUsuarioDTO | UpdateUsuarioDTO) => Promise<void>;
  onCerrar: () => void;
  loading: boolean;
}

const TIPOS_DOCUMENTO = ['DNI', 'RUC', 'PASAPORTE'] as const;

const buildSchema = (esEdicion: boolean) =>
  z
    .object({
      nombre: z.string().min(2, 'Ingresa un nombre'),
      email: z.string().email('Ingresa un email válido'),
      password: esEdicion
        ? z.string().min(8, 'Mínimo 8 caracteres').optional()
        : z.string().min(8, 'Mínimo 8 caracteres'),
      password_confirmation: z.string().optional(),
      rol_id: z.number().min(1, 'Selecciona un rol'),
      numero_documento: z.string().min(6, 'Documento requerido'),
      tipo_documento: z.enum(TIPOS_DOCUMENTO),
      telefono: z.string().optional(),
      activo: z.boolean().default(true),
    })
    .refine((data) => !data.password || data.password === data.password_confirmation, {
      message: 'Las contraseñas no coinciden',
      path: ['password_confirmation'],
    });

export const ModalUsuario: React.FC<ModalUsuarioProps> = ({
  usuario,
  roles,
  onGuardar,
  onCerrar,
  loading,
}) => {
  const esEdicion = Boolean(usuario);
  const schema = useMemo(() => buildSchema(esEdicion), [esEdicion]);
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);

  const {
    register,
    handleSubmit,
    setError,
    reset,
    formState: { errors },
  } = useForm<CreateUsuarioDTO | UpdateUsuarioDTO>({
    resolver: zodResolver(schema),
    defaultValues: {
      nombre: '',
      email: '',
      password: '',
      password_confirmation: '',
      rol_id: 0,
      numero_documento: '',
      tipo_documento: 'DNI',
      telefono: '',
      activo: true,
    },
  });

  useEffect(() => {
    if (usuario) {
      const rolId = getRolId(usuario) || 0;
      reset({
        nombre: usuario.nombre,
        email: usuario.email,
        password: '',
        password_confirmation: '',
        rol_id: rolId,
        numero_documento: usuario.numero_documento,
        tipo_documento: usuario.tipo_documento,
        telefono: usuario.telefono,
        activo: usuario.activo,
      });
    } else {
      reset({
        nombre: '',
        email: '',
        password: '',
        password_confirmation: '',
        rol_id: 0,
        numero_documento: '',
        tipo_documento: 'DNI',
        telefono: '',
        activo: true,
      });
    }
  }, [usuario, reset]);

  const onSubmit = async (data: any) => {
    try {
      await onGuardar(data);
    } catch (err: any) {
      if (err && typeof err === 'object') {
        Object.entries(err).forEach(([field, messages]) => {
          if (Array.isArray(messages)) {
            setError(field as any, { message: messages.join(', ') });
          }
        });
      }
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm">
      <div className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
        <div className="sticky top-0 flex items-center justify-between border-b border-gray-100 bg-white px-6 py-4">
          <div>
            <p className="text-xs uppercase tracking-wide text-gray-500">Usuarios</p>
            <h2 className="text-xl font-semibold text-gray-900">
              {usuario ? 'Editar usuario' : 'Nuevo usuario'}
            </h2>
          </div>
          <button
            onClick={onCerrar}
            className="text-gray-500 transition hover:text-gray-700"
            aria-label="Cerrar modal de usuario"
          >
            <X className="h-5 w-5" />
          </button>
        </div>

        <form onSubmit={handleSubmit(onSubmit)} className="px-6 py-6">
          <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div className="space-y-1">
              <label className="text-sm font-medium text-gray-700">Nombre completo *</label>
              <input
                type="text"
                {...register('nombre')}
                className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                placeholder="Ej. Ana Fernández"
                disabled={loading}
              />
              {errors.nombre && <p className="text-xs text-red-600">{errors.nombre.message as string}</p>}
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium text-gray-700">Correo electrónico *</label>
              <input
                type="email"
                {...register('email')}
                className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                placeholder="correo@empresa.com"
                disabled={loading}
              />
              {errors.email && <p className="text-xs text-red-600">{errors.email.message as string}</p>}
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium text-gray-700">
                Contraseña {usuario ? '(opcional)' : '*'}
              </label>
              <div className="relative">
                <input
                  type={showPassword ? 'text' : 'password'}
                  {...register('password')}
                  className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                  placeholder={usuario ? 'Dejar vacío para mantener la actual' : '••••••••'}
                  disabled={loading}
                />
                <button
                  type="button"
                  onClick={() => setShowPassword((prev) => !prev)}
                  className="absolute right-3 top-2 text-gray-500"
                  aria-label="Mostrar u ocultar contraseña"
                >
                  {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
              </div>
              {errors.password && <p className="text-xs text-red-600">{errors.password.message as string}</p>}
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium text-gray-700">Confirmar contraseña</label>
              <div className="relative">
                <input
                  type={showConfirmPassword ? 'text' : 'password'}
                  {...register('password_confirmation')}
                  className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                  placeholder="Repite la contraseña"
                  disabled={loading}
                />
                <button
                  type="button"
                  onClick={() => setShowConfirmPassword((prev) => !prev)}
                  className="absolute right-3 top-2 text-gray-500"
                  aria-label="Mostrar u ocultar confirmación"
                >
                  {showConfirmPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
              </div>
              {errors.password_confirmation && (
                <p className="text-xs text-red-600">{errors.password_confirmation.message as string}</p>
              )}
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium text-gray-700">Tipo de documento *</label>
              <select
                {...register('tipo_documento')}
                className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                disabled={loading}
              >
                {TIPOS_DOCUMENTO.map((tipo) => (
                  <option key={tipo} value={tipo}>
                    {tipo}
                  </option>
                ))}
              </select>
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium text-gray-700">Número de documento *</label>
              <input
                type="text"
                {...register('numero_documento')}
                className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                placeholder="00000000"
                disabled={loading}
              />
              {errors.numero_documento && (
                <p className="text-xs text-red-600">{errors.numero_documento.message as string}</p>
              )}
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium text-gray-700">Teléfono</label>
              <input
                type="text"
                {...register('telefono')}
                className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                placeholder="999 888 777"
                disabled={loading}
              />
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium text-gray-700">Rol *</label>
              <select
                {...register('rol_id', { valueAsNumber: true })}
                className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                disabled={loading}
              >
                <option value={0}>Selecciona un rol</option>
                {roles.map((rol) => (
                  <option key={rol.id} value={rol.id}>
                    {normalizeRolNombre(rol)}
                  </option>
                ))}
              </select>
              {errors.rol_id && <p className="text-xs text-red-600">{errors.rol_id.message as string}</p>}
            </div>

            <div className="flex items-center gap-3 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
              <input
                type="checkbox"
                {...register('activo')}
                className="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                disabled={loading}
              />
              <div>
                <p className="text-sm font-medium text-gray-800">Usuario activo</p>
                <p className="text-xs text-gray-500">Controla si puede iniciar sesión en el sistema.</p>
              </div>
            </div>
          </div>

          <div className="mt-6 flex flex-col gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:justify-end">
            <button
              type="button"
              onClick={onCerrar}
              className="inline-flex items-center justify-center rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
              disabled={loading}
            >
              Cancelar
            </button>
            <button
              type="submit"
              disabled={loading}
              className="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-60"
            >
              {loading && <Loader className="h-4 w-4 animate-spin" />}
              {loading ? 'Guardando...' : usuario ? 'Actualizar' : 'Crear usuario'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};
