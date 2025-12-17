// components/serie/SerieTable.tsx
'use client';

import { Edit2, Trash2, ToggleLeft, ToggleRight, Hash, FileText, AlertCircle } from 'lucide-react';
import type { Serie, TipoComprobante } from '@/types/Serie';

interface SerieTableProps {
  series: Serie[];
  loading: boolean;
  onEdit: (serie: Serie) => void;
  onDelete: (id: number) => void;
  onToggleEstado: (id: number) => void;
  onGenerarNumero: (id: number) => void;
  getTipoComprobanteLabel: (tipo: TipoComprobante) => string;
  getTipoComprobanteColor: (tipo: TipoComprobante) => string;
}

export default function SerieTable({
  series,
  loading,
  onEdit,
  onDelete,
  onToggleEstado,
  onGenerarNumero,
  getTipoComprobanteLabel,
  getTipoComprobanteColor,
}: SerieTableProps) {
  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (series.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center h-64 text-gray-500">
        <FileText className="w-12 h-12 mb-2" />
        <p className="text-lg font-medium">No se encontraron series</p>
        <p className="text-sm">Crea una nueva serie para comenzar</p>
      </div>
    );
  }

  return (
    <div className="overflow-x-auto">
      <table className="w-full">
        <thead>
          <tr className="border-b border-gray-200">
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Tipo
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Serie
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Correlativo Actual
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Siguiente Número
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Comprobantes Emitidos
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Estado
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Acciones
            </th>
          </tr>
        </thead>
        <tbody className="bg-white divide-y divide-gray-200">
          {series.map((serie) => {
            const color = getTipoComprobanteColor(serie.tipo_comprobante);
            const siguienteNumero = `${serie.serie}-${String(serie.correlativo_actual + 1).padStart(8, '0')}`;
            
            return (
              <tr key={serie.id} className="hover:bg-gray-50">
                <td className="px-6 py-4 whitespace-nowrap">
                  <span className={`inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-${color}-100 text-${color}-800`}>
                    <FileText className="w-3 h-3" />
                    {getTipoComprobanteLabel(serie.tipo_comprobante)}
                  </span>
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  <div className="flex items-center gap-2">
                    <div className={`p-1.5 bg-${color}-100 rounded`}>
                      <Hash className={`w-4 h-4 text-${color}-600`} />
                    </div>
                    <span className="font-mono font-bold text-gray-900">
                      {serie.serie}
                    </span>
                  </div>
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  <span className="font-mono text-gray-700">
                    {String(serie.correlativo_actual).padStart(8, '0')}
                  </span>
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  <div className="flex items-center gap-2">
                    <span className="font-mono text-sm text-gray-600">
                      {siguienteNumero}
                    </span>
                    <button
                      onClick={() => onGenerarNumero(serie.id)}
                      className="p-1 text-blue-600 hover:bg-blue-50 rounded transition-colors"
                      title="Generar número"
                    >
                      <Hash className="w-4 h-4" />
                    </button>
                  </div>
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  <div className="flex items-center gap-1">
                    <FileText className="w-4 h-4 text-gray-400" />
                    <span className="text-gray-700">
                      {serie.comprobantes_count || 0}
                    </span>
                  </div>
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  {serie.activo ? (
                    <span className="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                      <span className="w-2 h-2 bg-green-400 rounded-full"></span>
                      Activo
                    </span>
                  ) : (
                    <span className="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                      <span className="w-2 h-2 bg-red-400 rounded-full"></span>
                      Inactivo
                    </span>
                  )}
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  <div className="flex items-center gap-2">
                    <button
                      onClick={() => onEdit(serie)}
                      className="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                      title="Editar"
                    >
                      <Edit2 className="w-4 h-4" />
                    </button>
                    <button
                      onClick={() => onToggleEstado(serie.id)}
                      className={`p-1.5 ${
                        serie.activo
                          ? 'text-orange-600 hover:bg-orange-50'
                          : 'text-green-600 hover:bg-green-50'
                      } rounded-lg transition-colors`}
                      title={serie.activo ? 'Desactivar' : 'Activar'}
                    >
                      {serie.activo ? (
                        <ToggleRight className="w-4 h-4" />
                      ) : (
                        <ToggleLeft className="w-4 h-4" />
                      )}
                    </button>
                    <button
                      onClick={() => onDelete(serie.id)}
                      className="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                      title="Eliminar"
                      disabled={(serie.comprobantes_count || 0) > 0}
                    >
                      {(serie.comprobantes_count || 0) > 0 ? (
                        <AlertCircle className="w-4 h-4 text-gray-400" />
                      ) : (
                        <Trash2 className="w-4 h-4" />
                      )}
                    </button>
                  </div>
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
}