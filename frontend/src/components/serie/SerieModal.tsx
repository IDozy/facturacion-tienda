// components/serie/SerieModal.tsx
'use client';

import { useState, useEffect } from 'react';
import { X, Save, AlertCircle, FileText } from 'lucide-react';
import serieService from '@/services/serieService';
import type { Serie, TipoComprobante, TipoComprobanteOption } from '@/types/Serie';
import { toast } from 'react-hot-toast';

interface SerieModalProps {
  serie: Serie | null;
  onClose: () => void;
  onSuccess: () => void;
}

export default function SerieModal({ serie, onClose, onSuccess }: SerieModalProps) {
  const [loading, setLoading] = useState(false);
  const [tiposComprobante, setTiposComprobante] = useState<TipoComprobanteOption[]>([]);
  const [validacionFormato, setValidacionFormato] = useState<{
    valido: boolean;
    mensaje: string;
  } | null>(null);
  
  const [formData, setFormData] = useState({
    tipo_comprobante: 'factura' as TipoComprobante,
    serie: '',
    correlativo_actual: 0,
    activo: true,
    empresa_id: 1, // Deberías obtener esto del contexto del usuario
  });

  const [errors, setErrors] = useState<Record<string, string>>({});

  useEffect(() => {
    if (serie) {
      setFormData({
        tipo_comprobante: serie.tipo_comprobante,
        serie: serie.serie,
        correlativo_actual: serie.correlativo_actual,
        activo: serie.activo,
        empresa_id: serie.empresa_id,
      });
    }
    loadTiposComprobante();
  }, [serie]);

  useEffect(() => {
    // Auto-generar serie según el tipo de comprobante
    if (!serie && formData.tipo_comprobante) {
      const prefijos: Record<TipoComprobante, string> = {
        factura: 'F',
        boleta: 'B',
        nota_credito: 'F',
        nota_debito: 'F',
        guia_remision: 'T',
      };
      
      // Solo auto-generar si el campo está vacío o tiene el prefijo anterior
      if (!formData.serie || formData.serie.length <= 1) {
        setFormData(prev => ({
          ...prev,
          serie: prefijos[formData.tipo_comprobante] + '001',
        }));
      }
    }
  }, [formData.tipo_comprobante, serie]);

  useEffect(() => {
    // Validar formato cuando cambia la serie o el tipo
    if (formData.serie && formData.tipo_comprobante) {
      validarFormatoSerie();
    }
  }, [formData.serie, formData.tipo_comprobante]);

  const loadTiposComprobante = async () => {
    try {
      const response = await serieService.getTiposComprobante();
      setTiposComprobante(response.data);
    } catch (error) {
      console.error('Error loading tipos comprobante:', error);
    }
  };

  const validarFormatoSerie = () => {
    const formatos: Record<TipoComprobante, RegExp> = {
      factura: /^F\d{3}$/,
      boleta: /^B\d{3}$/,
      nota_credito: /^(F|B)\d{3}$/,
      nota_debito: /^(F|B)\d{3}$/,
      guia_remision: /^T\d{3}$/,
    };

    const regex = formatos[formData.tipo_comprobante];
    const esValido = regex.test(formData.serie.toUpperCase());

    const mensajes: Record<TipoComprobante, string> = {
      factura: 'Formato: F### (Ej: F001)',
      boleta: 'Formato: B### (Ej: B001)',
      nota_credito: 'Formato: F### o B### (Ej: F001, B001)',
      nota_debito: 'Formato: F### o B### (Ej: F001, B001)',
      guia_remision: 'Formato: T### (Ej: T001)',
    };

    setValidacionFormato({
      valido: esValido,
      mensaje: mensajes[formData.tipo_comprobante],
    });

    return esValido;
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value, type } = e.target;
    
    if (name === 'serie') {
      // Convertir a mayúsculas y limitar a 4 caracteres
      const upperValue = value.toUpperCase().slice(0, 4);
      setFormData(prev => ({ ...prev, [name]: upperValue }));
    } else if (name === 'correlativo_actual') {
      setFormData(prev => ({ ...prev, [name]: parseInt(value) || 0 }));
    } else if (type === 'checkbox') {
      const target = e.target as HTMLInputElement;
      setFormData(prev => ({ ...prev, [name]: target.checked }));
    } else {
      setFormData(prev => ({ ...prev, [name]: value }));
    }
    
    // Limpiar error del campo
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: '' }));
    }
  };

  const validate = (): boolean => {
    const newErrors: Record<string, string> = {};

    if (!formData.tipo_comprobante) {
      newErrors.tipo_comprobante = 'El tipo de comprobante es requerido';
    }

    if (!formData.serie) {
      newErrors.serie = 'La serie es requerida';
    } else if (formData.serie.length !== 4) {
      newErrors.serie = 'La serie debe tener exactamente 4 caracteres';
    } else if (validacionFormato && !validacionFormato.valido) {
      newErrors.serie = validacionFormato.mensaje;
    }

    if (formData.correlativo_actual < 0) {
      newErrors.correlativo_actual = 'El correlativo no puede ser negativo';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!validate()) {
      toast.error('Por favor, corrija los errores del formulario');
      return;
    }

    setLoading(true);
    try {
      if (serie) {
        await serieService.updateSerie(serie.id, formData);
        toast.success('Serie actualizada exitosamente');
      } else {
        await serieService.createSerie(formData);
        toast.success('Serie creada exitosamente');
      }
      onSuccess();
    } catch (error: any) {
      const message = error.response?.data?.message || 'Error al guardar la serie';
      const validationErrors = error.response?.data?.errors;
      
      if (validationErrors) {
        setErrors(validationErrors);
      }
      
      toast.error(message);
    } finally {
      setLoading(false);
    }
  };

  const getTipoComprobanteInfo = (tipo: TipoComprobante) => {
    return tiposComprobante.find(t => t.value === tipo);
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-200">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-blue-100 rounded-lg">
              <FileText className="w-5 h-5 text-blue-600" />
            </div>
            <h2 className="text-xl font-semibold text-gray-900">
              {serie ? 'Editar Serie' : 'Nueva Serie'}
            </h2>
          </div>
          <button
            onClick={onClose}
            className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <X className="w-5 h-5 text-gray-500" />
          </button>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit} className="p-6">
          <div className="space-y-4">
            {/* Tipo de Comprobante */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Tipo de Comprobante <span className="text-red-500">*</span>
              </label>
              <select
                name="tipo_comprobante"
                value={formData.tipo_comprobante}
                onChange={handleChange}
                disabled={!!serie} // No permitir cambiar el tipo al editar
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100"
              >
                {tiposComprobante.map((tipo) => (
                  <option key={tipo.value} value={tipo.value}>
                    {tipo.label} - {tipo.formato}
                  </option>
                ))}
              </select>
              {errors.tipo_comprobante && (
                <p className="mt-1 text-sm text-red-600">{errors.tipo_comprobante}</p>
              )}
            </div>

            {/* Serie */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Serie <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                name="serie"
                value={formData.serie}
                onChange={handleChange}
                maxLength={4}
                placeholder="Ej: F001"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono uppercase"
              />
              {validacionFormato && (
                <div className={`mt-1 flex items-center gap-1 text-sm ${
                  validacionFormato.valido ? 'text-green-600' : 'text-amber-600'
                }`}>
                  <AlertCircle className="w-4 h-4" />
                  <span>{validacionFormato.mensaje}</span>
                </div>
              )}
              {errors.serie && (
                <p className="mt-1 text-sm text-red-600">{errors.serie}</p>
              )}
            </div>

            {/* Correlativo Actual */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Correlativo Actual
              </label>
              <input
                type="number"
                name="correlativo_actual"
                value={formData.correlativo_actual}
                onChange={handleChange}
                min="0"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
              <p className="mt-1 text-xs text-gray-500">
                Próximo número: {formData.serie}-{String(formData.correlativo_actual + 1).padStart(8, '0')}
              </p>
              {errors.correlativo_actual && (
                <p className="mt-1 text-sm text-red-600">{errors.correlativo_actual}</p>
              )}
            </div>

            {/* Estado */}
            <div className="flex items-center">
              <input
                type="checkbox"
                id="activo"
                name="activo"
                checked={formData.activo}
                onChange={handleChange}
                className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label htmlFor="activo" className="ml-2 block text-sm text-gray-900">
                Serie activa
              </label>
            </div>
          </div>

          {/* Actions */}
          <div className="flex justify-end gap-3 mt-6 pt-6 border-t border-gray-200">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
              disabled={loading}
            >
              Cancelar
            </button>
            <button
              type="submit"
              disabled={loading}
              className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              {loading ? (
                <>
                  <div className="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                  Guardando...
                </>
              ) : (
                <>
                  <Save className="w-4 h-4" />
                  {serie ? 'Actualizar' : 'Guardar'}
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}