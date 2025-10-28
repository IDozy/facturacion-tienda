// src/components/empresa/EmpresaForm.tsx
import { useState, useEffect } from "react";
import { Building2, Mail, Phone, MapPin, FileText, Save, Loader2, CheckCircle2, AlertCircle } from "lucide-react";
import { getEmpresa, updateEmpresa } from "../../services/empresaService";
import type { Empresa } from "../../types/Empresa";

export default function EmpresaForm() {
    const [empresa, setEmpresa] = useState<Partial<Empresa>>({});
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [showSuccess, setShowSuccess] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    useEffect(() => {
        const fetchData = async () => {
            try {
                const data = await getEmpresa();
                setEmpresa(data);
            } catch (error) {
                console.error("Error al obtener la empresa:", error);
            } finally {
                setLoading(false);
            }
        };
        fetchData();
    }, []);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const { name, value } = e.target;
        setEmpresa({ ...empresa, [name]: value });
        if (errors[name]) {
            setErrors({ ...errors, [name]: "" });
        }
    };

    const validateForm = () => {
        const newErrors: Record<string, string> = {};
        if (!empresa.ruc || empresa.ruc.length !== 11) {
            newErrors.ruc = "El RUC debe tener 11 dígitos";
        }
        if (!empresa.razon_social) {
            newErrors.razon_social = "La razón social es requerida";
        }
        if (!empresa.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(empresa.email)) {
            newErrors.email = "Ingrese un correo válido";
        }
        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!validateForm()) return;

        setSaving(true);
        setShowSuccess(false);

        try {
            const updated = await updateEmpresa(empresa);
            setEmpresa(updated);
            setShowSuccess(true);
            setTimeout(() => setShowSuccess(false), 3000);
        } catch (error) {
            console.error("Error al actualizar la empresa:", error);
            setErrors({ submit: "Error al guardar los cambios. Intente nuevamente." });
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return (
            <div className="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center p-4">
                <div className="flex flex-col items-center gap-4">
                    <Loader2 className="w-12 h-12 text-blue-600 animate-spin" />
                    <p className="text-slate-600 font-medium">Cargando datos de empresa...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="">
            
                {/* Success Alert */}
                {showSuccess && (
                    <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3 animate-pulse">
                        <CheckCircle2 className="w-5 h-5 text-green-600 flex-shrink-0" />
                        <p className="text-green-800 font-medium">¡Cambios guardados exitosamente!</p>
                    </div>
                )}

                {/* Error Alert */}
                {errors.submit && (
                    <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3">
                        <AlertCircle className="w-5 h-5 text-red-600 flex-shrink-0" />
                        <p className="text-red-800 font-medium">{errors.submit}</p>
                    </div>
                )}

                {/* Form Card */}
                <div className="bg-white rounded-xl  border border-slate-200 overflow-hidden">
                    <form onSubmit={handleSubmit}>
                        {/* Form Header */}
                        <div className="bg-blue-300 px-6 py-4">
                            <h2 className="text-xl font-semibold text-black flex items-center gap-2">
                                <FileText className="w-5 h-5" />
                                Datos Generales
                            </h2>
                        </div>

                        {/* Form Body */}
                        <div className="p-6 space-y-4">
                            {/* RUC y Razón Social */}
                            <div className="grid md:grid-cols-2 gap-6">
                                <div className="space-y-2">
                                    <label className="block text-sm font-semibold text-slate-700">
                                        RUC <span className="text-red-500">*</span>
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <FileText className="w-5 h-5 text-slate-400" />
                                        </div>
                                        <input
                                            type="text"
                                            name="ruc"
                                            value={empresa.ruc || ""}
                                            onChange={handleChange}
                                            maxLength={11}
                                            placeholder="20123456789"
                                            className={`w-full pl-12 pr-4 py-3 border-2 rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${errors.ruc ? 'border-red-300 bg-red-50' : 'border-slate-200 hover:border-slate-300'
                                                }`}
                                        />
                                    </div>
                                    {errors.ruc && (
                                        <p className="text-sm text-red-600 flex items-center gap-1">
                                            <AlertCircle className="w-4 h-4" />
                                            {errors.ruc}
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <label className="block text-sm font-semibold text-slate-700">
                                        Razón Social <span className="text-red-500">*</span>
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <Building2 className="w-5 h-5 text-slate-400" />
                                        </div>
                                        <input
                                            type="text"
                                            name="razon_social"
                                            value={empresa.razon_social || ""}
                                            onChange={handleChange}
                                            placeholder="Mi Empresa SAC"
                                            className={`w-full pl-12 pr-4 py-3 border-2 rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${errors.razon_social ? 'border-red-300 bg-red-50' : 'border-slate-200 hover:border-slate-300'
                                                }`}
                                        />
                                    </div>
                                    {errors.razon_social && (
                                        <p className="text-sm text-red-600 flex items-center gap-1">
                                            <AlertCircle className="w-4 h-4" />
                                            {errors.razon_social}
                                        </p>
                                    )}
                                </div>
                            </div>

                            {/* Nombre Comercial */}
                            <div className="space-y-2">
                                <label className="block text-sm font-semibold text-slate-700">
                                    Nombre Comercial
                                </label>
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <Building2 className="w-5 h-5 text-slate-400" />
                                    </div>
                                    <input
                                        type="text"
                                        name="nombre_comercial"
                                        value={empresa.nombre_comercial || ""}
                                        onChange={handleChange}
                                        placeholder="Nombre con el que opera la empresa"
                                        className="w-full pl-12 pr-4 py-3 border-2 border-slate-200 rounded-xl hover:border-slate-300 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    />
                                </div>
                            </div>

                            {/* Dirección */}
                            <div className="space-y-2">
                                <label className="block text-sm font-semibold text-slate-700">
                                    Dirección Fiscal
                                </label>
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <MapPin className="w-5 h-5 text-slate-400" />
                                    </div>
                                    <input
                                        type="text"
                                        name="direccion"
                                        value={empresa.direccion || ""}
                                        onChange={handleChange}
                                        placeholder="Av. Principal 123, Lima"
                                        className="w-full pl-12 pr-4 py-3 border-2 border-slate-200 rounded-xl hover:border-slate-300 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    />
                                </div>
                            </div>

                            {/* Teléfono y Email */}
                            <div className="grid md:grid-cols-2 gap-6">
                                <div className="space-y-2">
                                    <label className="block text-sm font-semibold text-slate-700">
                                        Teléfono
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <Phone className="w-5 h-5 text-slate-400" />
                                        </div>
                                        <input
                                            type="tel"
                                            name="telefono"
                                            value={empresa.telefono || ""}
                                            onChange={handleChange}
                                            placeholder="987654321"
                                            className="w-full pl-12 pr-4 py-3 border-2 border-slate-200 rounded-xl hover:border-slate-300 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        />
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <label className="block text-sm font-semibold text-slate-700">
                                        Correo Electrónico <span className="text-red-500">*</span>
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <Mail className="w-5 h-5 text-slate-400" />
                                        </div>
                                        <input
                                            type="email"
                                            name="email"
                                            value={empresa.email || ""}
                                            onChange={handleChange}
                                            placeholder="contacto@miempresa.com"
                                            className={`w-full pl-12 pr-4 py-3 border-2 rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${errors.email ? 'border-red-300 bg-red-50' : 'border-slate-200 hover:border-slate-300'
                                                }`}
                                        />
                                    </div>
                                    {errors.email && (
                                        <p className="text-sm text-red-600 flex items-center gap-1">
                                            <AlertCircle className="w-4 h-4" />
                                            {errors.email}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Form Footer */}
                        <div className="bg-slate-50 px-8 py-6 border-t border-slate-200 flex justify-between items-center">
                            <p className="text-sm text-slate-600">
                                <span className="text-red-500">*</span> Campos obligatorios
                            </p>
                            <button
                                type="submit"
                                disabled={saving}
                                className="group relative px-8 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl flex items-center gap-2"
                            >
                                {saving ? (
                                    <>
                                        <Loader2 className="w-5 h-5 animate-spin" />
                                        <span>Guardando...</span>
                                    </>
                                ) : (
                                    <>
                                        <Save className="w-5 h-5" />
                                        <span>Guardar Cambios</span>
                                    </>
                                )}
                            </button>
                        </div>
                    </form>
                </div>

                {/* Info Card */}
                <div className="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-6">
                    <div className="flex gap-3">
                        <div className="flex-shrink-0">
                            <div className="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                                <Building2 className="w-5 h-5 text-white" />
                            </div>
                        </div>
                        <div>
                            <h3 className="font-semibold text-blue-900 mb-1">Información importante</h3>
                            <p className="text-sm text-blue-800 leading-relaxed">
                                Los datos de la empresa se utilizarán para generar los comprobantes electrónicos que se enviarán a SUNAT.
                                Asegúrate de que la información sea correcta y esté actualizada.
                            </p>
                        </div>
                    </div>
                </div>
            
        </div>
    );
}