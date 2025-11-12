// src/components/empresa/ConfiguracionEmpresaForm.tsx
import { useState, useEffect } from "react";
import {
    Settings,
    Percent,
    DollarSign,
    Calculator,
    Save,
    Loader2,
    CheckCircle2,
    AlertCircle,
    Eye,
    TrendingUp,
    TrendingDown,
} from "lucide-react";
import {
    getConfiguracionPorEmpresa,  // ✅ Cambiado
    saveConfiguracionEmpresa,     // ✅ Nuevo
} from "../../services/configuracionEmpresaService";
import { getUserWithEmpresa } from "../../services/empresaService";
import type { ConfiguracionEmpresa } from "../../types/ConfiguracionEmpresa";

export default function ConfiguracionEmpresaForm() {
    const [configuracion, setConfiguracion] = useState<Partial<ConfiguracionEmpresa>>({});
    const [formData, setFormData] = useState<Partial<ConfiguracionEmpresa>>({
        igv_porcentaje: 18.00,
        moneda_default: "PEN",
        tolerancia_cuadratura: 1.00,
        retencion_porcentaje_default: 0.00,
        percepcion_porcentaje_default: 0.00,
    });
    const [empresaId, setEmpresaId] = useState<number | null>(null);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [showSuccess, setShowSuccess] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    // Ejemplos de cálculo
    const [montoEjemplo, setMontoEjemplo] = useState<number>(1000);
    const [calculosEjemplo, setCalculosEjemplo] = useState({
        igv: 0,
        total: 0,
        retencion: 0,
        percepcion: 0,
    });

    useEffect(() => {
        const fetchData = async () => {
            try {
                const userData = await getUserWithEmpresa();
                const empId = userData.user?.empresa_id || userData.empresa_id;

                if (!empId) {
                    setErrors({ fetch: "No se pudo obtener el ID de la empresa" });
                    setLoading(false);
                    return;
                }

                setEmpresaId(empId);

                try {
                    // ✅ Usar la función correcta que busca por empresa_id
                    const configData = await getConfiguracionPorEmpresa(empId);
                    if (configData) {
                        setConfiguracion({ ...configData });
                        setFormData({ ...configData });
                    }
                } catch (error: any) {
                    console.log("Error al cargar configuración:", error);
                    // Mantener valores por defecto
                }
            } catch (error) {
                console.error("Error al obtener la configuración:", error);
                setErrors({ fetch: "Error al cargar la configuración" });
            } finally {
                setLoading(false);
            }
        };
        fetchData();
    }, []);

    // Actualizar cálculos de ejemplo cuando cambian los valores
    useEffect(() => {
        const igvRate = (formData.igv_porcentaje || 18) / 100;
        const retencionRate = (formData.retencion_porcentaje_default || 3) / 100;
        const percepcionRate = (formData.percepcion_porcentaje_default || 2) / 100;

        setCalculosEjemplo({
            igv: Math.round(montoEjemplo * igvRate * 100) / 100,
            total: Math.round(montoEjemplo * (1 + igvRate) * 100) / 100,
            retencion: Math.round(montoEjemplo * retencionRate * 100) / 100,
            percepcion: Math.round(montoEjemplo * percepcionRate * 100) / 100,
        });
    }, [formData, montoEjemplo]);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        let processedValue: any = value;

        if (
            name === "igv_porcentaje" ||
            name === "tolerancia_cuadratura" ||
            name === "retencion_porcentaje_default" ||
            name === "percepcion_porcentaje_default"
        ) {
            processedValue = parseFloat(value) || 0;
        }

        setFormData({ ...formData, [name]: processedValue });
        if (errors[name]) setErrors({ ...errors, [name]: "" });
    };

    const validateForm = () => {
        const newErrors: Record<string, string> = {};

        // IGV
        if (formData.igv_porcentaje === null || formData.igv_porcentaje === undefined || formData.igv_porcentaje < 0 || formData.igv_porcentaje > 100) {
            newErrors.igv_porcentaje = "El IGV debe estar entre 0 y 100";
        }

        // Retención
        if (formData.retencion_porcentaje_default === null || formData.retencion_porcentaje_default === undefined || formData.retencion_porcentaje_default < 0 || formData.retencion_porcentaje_default > 100) {
            newErrors.retencion_porcentaje_default = "La retención debe estar entre 0 y 100";
        }

        // Percepción
        if (formData.percepcion_porcentaje_default === null || formData.percepcion_porcentaje_default === undefined || formData.percepcion_porcentaje_default < 0 || formData.percepcion_porcentaje_default > 100) {
            newErrors.percepcion_porcentaje_default = "La percepción debe estar entre 0 y 100";
        }

        // Tolerancia
        if (formData.tolerancia_cuadratura === null || formData.tolerancia_cuadratura === undefined || formData.tolerancia_cuadratura < 0) {
            newErrors.tolerancia_cuadratura = "La tolerancia no puede ser negativa";
        }

        // Moneda
        if (!formData.moneda_default) {
            newErrors.moneda_default = "Debe seleccionar una moneda por defecto";
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };


    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!validateForm() || !empresaId) return;

        setSaving(true);
        setShowSuccess(false);
        setErrors({});

        try {
            const dataToSend = {
                ...formData,
                empresa_id: empresaId,
            };

            // ✅ Usar la nueva función que maneja crear/actualizar automáticamente
            const configActualizada = await saveConfiguracionEmpresa(dataToSend);

            setConfiguracion(configActualizada);
            setFormData(configActualizada);

            setShowSuccess(true);
            setTimeout(() => setShowSuccess(false), 3000);
        } catch (error: any) {
            console.error("Error al guardar la configuración:", error);
            setErrors({
                submit: error.response?.data?.message || "Error al guardar los cambios",
            });
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return (
            <div className="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center p-4">
                <div className="flex flex-col items-center gap-4">
                    <Loader2 className="w-12 h-12 text-blue-600 animate-spin" />
                    <p className="text-slate-600 font-medium">Cargando configuración...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="relative flex min-h-screen ">
            {/* COLUMNA IZQUIERDA (scrollable) */}
            <div className="flex-1 overflow-y-auto px-1 lg:mr-[350px]">
                <div className="max-w-5xl mx-auto space-y-6">
                    {/* Título */}
                    <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2 mb-4">
                        <Settings className="w-7 h-7 text-blue-600" />
                        Configuración de Empresa
                    </h1>

                    {/* Alertas */}
                    {errors.fetch && (
                        <div className="p-4 bg-red-50 border border-red-200 rounded-sm flex items-center gap-3">
                            <AlertCircle className="w-5 h-5 text-red-600 flex-shrink-0" />
                            <p className="text-red-800 font-medium">{errors.fetch}</p>
                        </div>
                    )}
                    {showSuccess && (
                        <div className="p-4 bg-green-50 border border-green-200 rounded-sm flex items-center gap-3 animate-pulse">
                            <CheckCircle2 className="w-5 h-5 text-green-600 flex-shrink-0" />
                            <p className="text-green-800 font-medium">¡Configuración guardada exitosamente!</p>
                        </div>
                    )}
                    {errors.submit && (
                        <div className="p-4 bg-red-50 border border-red-200 rounded-sm flex items-center gap-3">
                            <AlertCircle className="w-5 h-5 text-red-600 flex-shrink-0" />
                            <p className="text-red-800 font-medium">{errors.submit}</p>
                        </div>
                    )}

                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Card: Configuración Tributaria */}
                        <div className="bg-white rounded-sm border border-slate-300 overflow-hidden">
                            <div className="bg-purple-300 px-6 py-4">
                                <h2 className="text-lg font-semibold text-black flex items-center gap-2">
                                    <Percent className="w-5 h-5" />
                                    Configuración Tributaria
                                </h2>
                            </div>

                            <div className="p-6">
                                <div className="grid md:grid-cols-2 gap-6">
                                    {/* IGV */}
                                    <div className="space-y-2">
                                        <label className="block text-sm font-semibold text-slate-700">
                                            IGV (%) <span className="text-red-500">*</span>
                                        </label>
                                        <div className="relative">
                                            <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <Percent className="w-5 h-5 text-slate-400" />
                                            </div>
                                            <input
                                                type="number"
                                                name="igv_porcentaje"
                                                value={formData.igv_porcentaje || ""}
                                                onChange={handleChange}
                                                step="0.01"
                                                min="0"
                                                max="100"
                                                placeholder="18.00"
                                                className={`w-full pl-12 pr-4 py-3 border-1 rounded-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder:text-slate-400 ${errors.igv_porcentaje
                                                    ? "border-red-300 bg-red-50"
                                                    : "border-slate-300 hover:border-slate-400"
                                                    }`}
                                            />
                                        </div>
                                        {errors.igv_porcentaje && (
                                            <p className="text-sm text-red-600 flex items-center gap-1">
                                                <AlertCircle className="w-4 h-4" />
                                                {errors.igv_porcentaje}
                                            </p>
                                        )}
                                    </div>

                                    {/* Moneda Default */}
                                    <div className="space-y-2">
                                        <label className="block text-sm font-semibold text-slate-700">
                                            Moneda por Defecto <span className="text-red-500">*</span>
                                        </label>
                                        <div className="relative">
                                            <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <DollarSign className="w-5 h-5 text-slate-400" />
                                            </div>
                                            <select
                                                name="moneda_default"
                                                value={formData.moneda_default || "PEN"}
                                                onChange={handleChange}
                                                className={`w-full pl-12 pr-4 py-3 border-1 rounded-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none bg-white ${errors.moneda_default
                                                    ? "border-red-300 bg-red-50"
                                                    : "border-slate-300 hover:border-slate-400"
                                                    }`}
                                            >
                                                <option value="PEN">PEN - Soles</option>
                                                <option value="USD">USD - Dólares</option>
                                                <option value="EUR">EUR - Euros</option>
                                            </select>
                                        </div>
                                        {errors.moneda_default && (
                                            <p className="text-sm text-red-600 flex items-center gap-1">
                                                <AlertCircle className="w-4 h-4" />
                                                {errors.moneda_default}
                                            </p>
                                        )}
                                    </div>

                                    {/* Retención */}
                                    <div className="space-y-2">
                                        <label className="block text-sm font-semibold text-slate-700">
                                            Retención por Defecto (%)
                                        </label>
                                        <div className="relative">
                                            <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <TrendingDown className="w-5 h-5 text-slate-400" />
                                            </div>
                                            <input
                                                type="number"
                                                name="retencion_porcentaje_default"
                                                value={formData.retencion_porcentaje_default ?? ""}
                                                onChange={handleChange}
                                                step="0.01"
                                                min="0"
                                                max="100"
                                                placeholder="0.00"
                                                className={`w-full pl-12 pr-4 py-3 border-1 rounded-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder:text-slate-400 ${errors.retencion_porcentaje_default
                                                    ? "border-red-300 bg-red-50"
                                                    : "border-slate-300 hover:border-slate-400"
                                                    }`}
                                            />
                                        </div>
                                        {errors.retencion_porcentaje_default && (
                                            <p className="text-sm text-red-600 flex items-center gap-1">
                                                <AlertCircle className="w-4 h-4" />
                                                {errors.retencion_porcentaje_default}
                                            </p>
                                        )}
                                    </div>

                                    {/* Percepción */}
                                    <div className="space-y-2">
                                        <label className="block text-sm font-semibold text-slate-700">
                                            Percepción por Defecto (%)
                                        </label>
                                        <div className="relative">
                                            <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <TrendingUp className="w-5 h-5 text-slate-400" />
                                            </div>
                                            <input
                                                type="number"
                                                name="percepcion_porcentaje_default"
                                                value={formData.percepcion_porcentaje_default ?? ""}
                                                onChange={handleChange}
                                                step="0.01"
                                                min="0"
                                                max="100"
                                                placeholder="2.00"
                                                className={`w-full pl-12 pr-4 py-3 border-1 rounded-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder:text-slate-400 ${errors.percepcion_porcentaje_default
                                                    ? "border-red-300 bg-red-50"
                                                    : "border-slate-300 hover:border-slate-400"
                                                    }`}
                                            />
                                        </div>
                                        {errors.percepcion_porcentaje_default && (
                                            <p className="text-sm text-red-600 flex items-center gap-1">
                                                <AlertCircle className="w-4 h-4" />
                                                {errors.percepcion_porcentaje_default}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Card: Configuración Contable */}
                        <div className="bg-white rounded-sm border border-slate-300 overflow-hidden">
                            <div className="bg-orange-300 px-6 py-4">
                                <h2 className="text-lg font-semibold text-black flex items-center gap-2">
                                    <Calculator className="w-5 h-5" />
                                    Configuración Contable
                                </h2>
                            </div>

                            <div className="p-6">
                                <div className="grid md:grid-cols-2 gap-6">
                                    {/* Tolerancia de Cuadratura */}
                                    <div className="space-y-2">
                                        <label className="block text-sm font-semibold text-slate-700">
                                            Tolerancia de Cuadratura ({formData.moneda_default || "PEN"})
                                        </label>
                                        <div className="relative">
                                            <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <Calculator className="w-5 h-5 text-slate-400" />
                                            </div>
                                            <input
                                                type="number"
                                                name="tolerancia_cuadratura"
                                                value={formData.tolerancia_cuadratura || ""}
                                                onChange={handleChange}
                                                step="0.01"
                                                min="0"
                                                placeholder="1.00"
                                                className={`w-full pl-12 pr-4 py-3 border-1 rounded-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder:text-slate-400 ${errors.tolerancia_cuadratura
                                                    ? "border-red-300 bg-red-50"
                                                    : "border-slate-300 hover:border-slate-400"
                                                    }`}
                                            />
                                        </div>
                                        {errors.tolerancia_cuadratura && (
                                            <p className="text-sm text-red-600 flex items-center gap-1">
                                                <AlertCircle className="w-4 h-4" />
                                                {errors.tolerancia_cuadratura}
                                            </p>
                                        )}
                                        <p className="text-xs text-slate-500">
                                            Diferencia máxima permitida en cuadraturas contables
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Botón de guardar */}
                        <div className="bg-slate-50 px-8 py-6 border border-slate-300 rounded-sm flex justify-between items-center">
                            <p className="text-sm text-slate-600">
                                <span className="text-red-500">*</span> Campos obligatorios
                            </p>
                            <button
                                type="submit"
                                disabled={saving}
                                className="group relative px-8 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-sm hover:from-blue-700 hover:to-blue-800 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl flex items-center gap-2"
                            >
                                {saving ? (
                                    <>
                                        <Loader2 className="w-5 h-5 animate-spin" />
                                        <span>Guardando...</span>
                                    </>
                                ) : (
                                    <>
                                        <Save className="w-5 h-5" />
                                        <span>Guardar Configuración</span>
                                    </>
                                )}
                            </button>
                        </div>
                    </form>

                    {/* Info Card */}
                    <div className="bg-blue-50 border border-blue-200 rounded-sm p-6">
                        <div className="flex gap-3">
                            <div className="flex-shrink-0">
                                <div className="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                                    <Settings className="w-5 h-5 text-white" />
                                </div>
                            </div>
                            <div>
                                <h3 className="font-semibold text-blue-900 mb-1">
                                    Información importante
                                </h3>
                                <p className="text-sm text-blue-800 leading-relaxed">
                                    Estos parámetros se aplicarán automáticamente en todas las operaciones
                                    contables y tributarias. Asegúrate de que estén correctamente configurados
                                    según la legislación vigente.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="hidden lg:flex flex-col gap-6 w-[380px] fixed right-0 top-0 h-screen border-slate-300 overflow-y-auto p-4">
                    {/* Configuración Actual */}
                    <div className="bg-white rounded-sm border border-slate-300 overflow-hidden">
                        <div className="bg-slate-100 px-4 py-2 border-b border-slate-200">
                            <h3 className="text-sm font-semibold text-slate-700 flex items-center gap-2">
                                <Eye className="w-4 h-4" /> Configuración Actual
                            </h3>
                        </div>
                        <div className="p-2 space-y-4">
                            <div>
                                <label className="block text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">
                                    IGV
                                </label>
                                <p className="text-xm text-slate-900 font-bold">
                                    {configuracion.igv_porcentaje || 18}%
                                </p>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">
                                    Moneda
                                </label>
                                <p className="text-sm text-slate-900 font-medium">
                                    {configuracion.moneda_default || "PEN"}
                                </p>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">
                                    Retención
                                </label>
                                <p className="text-sm text-slate-900">
                                    {configuracion.retencion_porcentaje_default || 3}%
                                </p>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">
                                    Percepción
                                </label>
                                <p className="text-sm text-slate-900">
                                    {configuracion.percepcion_porcentaje_default || 2}%
                                </p>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">
                                    Tolerancia
                                </label>
                                <p className="text-sm text-slate-900">
                                    {formData.moneda_default || "PEN"} {configuracion.tolerancia_cuadratura || 1}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Calculadora de Ejemplo */}
                    <div className="bg-white rounded-sm border border-slate-300 overflow-hidden">
                        <div className="bg-slate-100 px-4 py-3 border-b border-slate-200">
                            <h3 className="text-sm font-semibold text-slate-700 flex items-center gap-2">
                                <Calculator className="w-4 h-4" /> Calculadora de Ejemplo
                            </h3>
                        </div>

                        <div className="p-4 space-y-4">
                            <div className="space-y-2">
                                <label className="block text-xs font-medium text-slate-500">
                                    Monto Base ({formData.moneda_default || "PEN"})
                                </label>
                                <input
                                    type="number"
                                    value={montoEjemplo}
                                    onChange={(e) => setMontoEjemplo(parseFloat(e.target.value) || 0)}
                                    step="0.01"
                                    className="w-full px-3 py-2 border border-slate-300 rounded-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                                />
                            </div>

                            <div className="border-t border-slate-200 pt-3 space-y-2">
                                <div className="flex justify-between items-center">
                                    <span className="text-xs text-slate-600">Base:</span>
                                    <span className="text-sm font-medium text-slate-900">
                                        {montoEjemplo.toFixed(2)}
                                    </span>
                                </div>
                                <div className="flex justify-between items-center">
                                    <span className="text-xs text-slate-600">
                                        IGV ({formData.igv_porcentaje || 18}%):
                                    </span>
                                    <span className="text-sm font-medium text-slate-900">
                                        {calculosEjemplo.igv.toFixed(2)}
                                    </span>
                                </div>
                                <div className="flex justify-between items-center pt-2 border-t border-slate-200">
                                    <span className="text-xs font-bold text-slate-700">Total:</span>
                                    <span className="text-base font-bold text-blue-600">
                                        {calculosEjemplo.total.toFixed(2)}
                                    </span>
                                </div>
                            </div>

                            <div className="border-t border-slate-200 pt-3 space-y-2">
                                <div className="flex justify-between items-center">
                                    <span className="text-xs text-slate-600">
                                        Retención ({formData.retencion_porcentaje_default}%):
                                    </span>
                                    <span className="text-sm font-medium text-orange-600">
                                        {calculosEjemplo.retencion.toFixed(2)}
                                    </span>
                                </div>
                                <div className="flex justify-between items-center">
                                    <span className="text-xs text-slate-600">
                                        Percepción ({formData.percepcion_porcentaje_default}%):
                                    </span>
                                    <span className="text-sm font-medium text-green-600">
                                        {calculosEjemplo.percepcion.toFixed(2)}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div >
    );
}