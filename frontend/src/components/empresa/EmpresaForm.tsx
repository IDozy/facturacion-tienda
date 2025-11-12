import { useState, useEffect } from "react";
import {
    Building2,
    Mail,
    Phone,
    MapPin,
    FileText,
    Save,
    Loader2,
    CheckCircle2,
    AlertCircle,
    Eye,
    Image as ImageIcon,
    Shield,
    Key,
} from "lucide-react";
import {
    getEmpresa,
    updateEmpresa,
    getUserWithEmpresa,
    getEmpresaById,
} from "../../services/empresaService";
import type { Empresa } from "../../types/Empresa";

export default function EmpresaForm() {
    const [empresa, setEmpresa] = useState<Partial<Empresa>>({});
    const [formData, setFormData] = useState<Partial<Empresa>>({
        ruc: "",
        razon_social: "",
        direccion: "",
        telefono: "",
        email: "",
        usuario_sol: "",
        clave_sol: "",
        clave_certificado: "",
        modo: "prueba",
        fecha_expiracion_certificado: "",
        pse_autorizado: false,
    });
    const [logoFile, setLogoFile] = useState<File | null>(null);
    const [logoPreview, setLogoPreview] = useState<string | null>(null);
    const [certificadoFile, setCertificadoFile] = useState<File | null>(null);
    const [usuario, setUsuario] = useState<any>(null);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [showSuccess, setShowSuccess] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [rucOriginal, setRucOriginal] = useState<string>("");

    useEffect(() => {
        const fetchData = async () => {
            try {
                const userData = await getUserWithEmpresa();
                setUsuario(userData);
                const empresaId = userData.user?.empresa_id || userData.empresa_id;
                let empresaData;
                if (empresaId) {
                    empresaData = await getEmpresaById(empresaId);
                } else {
                    empresaData = await getEmpresa();
                }
                setEmpresa(empresaData);
                setRucOriginal(empresaData.ruc || "");
                setFormData({
                    ruc: "",
                    razon_social: "",
                    direccion: "",
                    telefono: "",
                    email: "",
                    usuario_sol: "",
                    clave_sol: "",
                    clave_certificado: "",
                    modo: empresaData.modo || "prueba",
                    fecha_expiracion_certificado: "",
                    pse_autorizado: empresaData.pse_autorizado || false,
                });
            } catch (error) {
                console.error("Error al obtener la empresa:", error);
                setErrors({ fetch: "Error al cargar los datos de la empresa" });
            } finally {
                setLoading(false);
            }
        };
        fetchData();
    }, []);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value, type } = e.target;
        let processedValue: any = value;

        if (name === "ruc") {
            processedValue = value.replace(/\D/g, "").slice(0, 11);
        }

        if (type === "checkbox") {
            processedValue = (e.target as HTMLInputElement).checked;
        }

        setFormData({ ...formData, [name]: processedValue });
        if (errors[name]) setErrors({ ...errors, [name]: "" });
    };

    const handleLogoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files && e.target.files[0]) {
            const file = e.target.files[0];

            if (!file.type.startsWith('image/')) {
                setErrors({ ...errors, logo: "Solo se permiten archivos de imagen" });
                return;
            }

            if (file.size > 2048000) {
                setErrors({ ...errors, logo: "El archivo no debe superar 2MB" });
                return;
            }

            setLogoFile(file);

            const reader = new FileReader();
            reader.onloadend = () => {
                setLogoPreview(reader.result as string);
            };
            reader.readAsDataURL(file);

            if (errors.logo) {
                setErrors({ ...errors, logo: "" });
            }
        }
    };

    const handleCertificadoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files && e.target.files[0]) {
            const file = e.target.files[0];

            if (!file.name.endsWith('.pfx')) {
                setErrors({ ...errors, certificado: "Solo se permiten archivos .pfx" });
                return;
            }

            if (file.size > 5242880) { // 5MB
                setErrors({ ...errors, certificado: "El archivo no debe superar 5MB" });
                return;
            }

            setCertificadoFile(file);

            if (errors.certificado) {
                setErrors({ ...errors, certificado: "" });
            }
        }
    };

    const validateForm = () => {
        const newErrors: Record<string, string> = {};
        const hasAnyChange =
            formData.ruc ||
            formData.razon_social ||
            formData.email ||
            formData.direccion ||
            formData.telefono ||
            formData.usuario_sol ||
            formData.clave_sol ||
            formData.clave_certificado ||
            formData.fecha_expiracion_certificado ||
            logoFile ||
            certificadoFile;

        if (!hasAnyChange) {
            newErrors.submit = "Debe modificar al menos un campo";
            setErrors(newErrors);
            return false;
        }

        if (formData.ruc && formData.ruc.length !== 11) {
            newErrors.ruc = "El RUC debe tener 11 dígitos";
        }

        if (formData.razon_social && formData.razon_social.trim() === "") {
            newErrors.razon_social = "La razón social no puede estar vacía";
        }

        if (formData.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
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
            const formDataToSend = new FormData();

            const rucToSend = formData.ruc
                ? formData.ruc.padStart(11, "0")
                : rucOriginal;

            if (rucToSend.length !== 11) {
                throw new Error("El RUC debe tener exactamente 11 dígitos.");
            }

            formDataToSend.append("ruc", rucToSend);

            if (formData.razon_social) {
                formDataToSend.append("razon_social", formData.razon_social);
            }
            if (formData.direccion) {
                formDataToSend.append("direccion", formData.direccion);
            }
            if (formData.telefono) {
                formDataToSend.append("telefono", formData.telefono);
            }
            if (formData.email) {
                formDataToSend.append("email", formData.email);
            }

            // Campos SUNAT
            if (formData.usuario_sol) {
                formDataToSend.append("usuario_sol", formData.usuario_sol);
            }
            if (formData.clave_sol) {
                formDataToSend.append("clave_sol", formData.clave_sol);
            }
            if (formData.clave_certificado) {
                formDataToSend.append("clave_certificado", formData.clave_certificado);
            }
            if (formData.modo) {
                formDataToSend.append("modo", formData.modo);
            }
            if (formData.fecha_expiracion_certificado) {
                formDataToSend.append("fecha_expiracion_certificado", formData.fecha_expiracion_certificado);
            }
            formDataToSend.append("pse_autorizado", formData.pse_autorizado ? "1" : "0");

            if (logoFile) {
                formDataToSend.append("logo", logoFile);
            }

            if (certificadoFile) {
                formDataToSend.append("certificado_digital", certificadoFile);
            }

            await updateEmpresa(empresa.id!, formDataToSend);

            const empresaId = empresa.id;
            let empresaActualizada;
            if (empresaId) {
                empresaActualizada = await getEmpresaById(empresaId);
            } else {
                empresaActualizada = await getEmpresa();
            }
            setEmpresa({ ...empresaActualizada });
            setRucOriginal(empresaActualizada.ruc || "");

            setFormData({
                ruc: "",
                razon_social: "",
                direccion: "",
                telefono: "",
                email: "",
                usuario_sol: "",
                clave_sol: "",
                clave_certificado: "",
                modo: empresaActualizada.modo || "prueba",
                fecha_expiracion_certificado: "",
                pse_autorizado: empresaActualizada.pse_autorizado || false,
            });
            setLogoFile(null);
            setLogoPreview(null);
            setCertificadoFile(null);

            setShowSuccess(true);
            setTimeout(() => setShowSuccess(false), 3000);
        } catch (error: any) {
            console.error("Error al actualizar la empresa:", error);
            setErrors({
                submit:
                    error.message ||
                    "Error al guardar los cambios. Asegúrate de que el RUC tenga 11 dígitos.",
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
                    <p className="text-slate-600 font-medium">Cargando datos de empresa...</p>
                </div>
            </div>
        );
    }

    const logoUrl = (empresa as any).logo_url || (empresa as any).logo;

    return (
        <div className="grid lg:grid-cols-3 gap-6">
            {/* Columna principal - Formulario */}
            <div className="lg:col-span-2 space-y-6">
                {/* Título General */}
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
                        <Building2 className="w-7 h-7 text-blue-600" />
                        Datos Generales
                    </h1>
                </div>

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
                        <p className="text-green-800 font-medium">
                            ¡Cambios guardados exitosamente!
                        </p>
                    </div>
                )}
                {errors.submit && (
                    <div className="p-4 bg-red-50 border border-red-200 rounded-sm flex items-center gap-3">
                        <AlertCircle className="w-5 h-5 text-red-600 flex-shrink-0" />
                        <p className="text-red-800 font-medium">{errors.submit}</p>
                    </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Card: Información de la Empresa */}
                    <div className="bg-white rounded-sm border border-slate-300 overflow-hidden">
                        <div className="bg-blue-100 px-6 py-4">
                            <h2 className="text-lg font-semibold text-black flex items-center gap-2">
                                <Building2 className="w-5 h-5" />
                                Información de la Empresa
                            </h2>
                        </div>

                        <div className="p-6">
                            <div className="grid md:grid-cols-2 gap-6">
                                {/* RUC */}
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
                                            value={formData.ruc || ""}
                                            onChange={handleChange}
                                            maxLength={11}
                                            placeholder={empresa.ruc || "20123456789"}
                                            className={`w-full pl-12 pr-4 py-3 border-1 rounded-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder:text-slate-400 placeholder:italic placeholder:opacity-70 ${errors.ruc ? "border-red-300 bg-red-50" : "border-slate-300 hover:border-slate-400"
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

                                {/* Razón Social */}
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
                                            value={formData.razon_social || ""}
                                            onChange={handleChange}
                                            placeholder={empresa.razon_social || "Mi Empresa SAC"}
                                            className={`w-full pl-12 pr-4 py-3 border-1 rounded-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder:text-slate-400 placeholder:opacity-60 ${errors.razon_social ? "border-red-300 bg-red-50" : "border-slate-300 hover:border-slate-400"
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
                                            value={formData.direccion || ""}
                                            onChange={handleChange}
                                            placeholder={empresa.direccion || "Av. Principal 123, Lima"}
                                            className="w-full pl-12 pr-4 py-3 border-1 border-slate-300 rounded-sm hover:border-slate-400 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder:text-slate-400 placeholder:opacity-60"
                                        />
                                    </div>
                                </div>

                                {/* Teléfono */}
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
                                            value={formData.telefono || ""}
                                            onChange={handleChange}
                                            placeholder={empresa.telefono || "987654321"}
                                            className="w-full pl-12 pr-4 py-3 border-1 border-slate-300 rounded-sm hover:border-slate-400 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder:text-slate-400 placeholder:opacity-60"
                                        />
                                    </div>
                                </div>

                                {/* Email */}
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
                                            value={formData.email || ""}
                                            onChange={handleChange}
                                            placeholder={empresa.email || "contacto@miempresa.com"}
                                            className={`w-full pl-12 pr-4 py-3 border-1 rounded-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder:text-slate-400 placeholder:opacity-60 ${errors.email ? "border-red-300 bg-red-50" : "border-slate-300 hover:border-slate-400"
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

                                {/* Logo */}
                                <div className="space-y-2">
                                    <label className="block text-sm font-semibold text-slate-700">
                                        Logo de la Empresa
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <ImageIcon className="w-5 h-5 text-slate-400" />
                                        </div>
                                        <input
                                            type="file"
                                            name="logo"
                                            accept="image/jpeg,image/png,image/jpg,image/gif,image/svg+xml,image/webp"
                                            onChange={handleLogoChange}
                                            className={`w-full pl-12 pr-4 py-3 border-1 rounded-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${errors.logo ? "border-red-300 bg-red-50" : "border-slate-300 hover:border-slate-400"
                                                }`}
                                        />
                                    </div>
                                    {errors.logo && (
                                        <p className="text-sm text-red-600 flex items-center gap-1">
                                            <AlertCircle className="w-4 h-4" />
                                            {errors.logo}
                                        </p>
                                    )}
                                    {logoPreview && (
                                        <div className="mt-2">
                                            <p className="text-xs text-slate-500 mb-1">Vista previa:</p>
                                            <img
                                                src={logoPreview}
                                                alt="Preview"
                                                className="w-32 h-32 object-contain border border-slate-300 rounded-sm"
                                            />
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Card: Configuración SUNAT */}
                    <div className="bg-white rounded-sm border border-slate-300 overflow-hidden">
                        <div className="bg-green-100 px-6 py-4">
                            <h2 className="text-lg font-semibold text-black flex items-center gap-2">
                                <Shield className="w-5 h-5" />
                                Configuración SUNAT
                            </h2>
                        </div>

                        <div className="p-6">
                            <div className="grid md:grid-cols-2 gap-6">
                                {/* Certificado digital */}
                                <div className="space-y-2">
                                    <label className="block text-sm font-semibold text-slate-700">
                                        Certificado Digital (.pfx)
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <Key className="w-5 h-5 text-slate-400" />
                                        </div>
                                        <input
                                            type="file"
                                            name="certificado_digital"
                                            accept=".pfx"
                                            onChange={handleCertificadoChange}
                                            className={`w-full pl-12 pr-4 py-3 border-1 rounded-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${errors.certificado ? "border-red-300 bg-red-50" : "border-slate-300 hover:border-slate-400"
                                                }`}
                                        />
                                    </div>
                                    {errors.certificado && (
                                        <p className="text-sm text-red-600 flex items-center gap-1">
                                            <AlertCircle className="w-4 h-4" />
                                            {errors.certificado}
                                        </p>
                                    )}
                                    {certificadoFile && (
                                        <p className="text-xs text-green-600 flex items-center gap-1">
                                            <CheckCircle2 className="w-4 h-4" />
                                            {certificadoFile.name}
                                        </p>
                                    )}
                                </div>

                                {/* Clave del certificado */}
                                <div className="space-y-2">
                                    <label className="block text-sm font-semibold text-slate-700">
                                        Clave del Certificado
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <Key className="w-5 h-5 text-slate-400" />
                                        </div>
                                        <input
                                            type="password"
                                            name="clave_certificado"
                                            value={formData.clave_certificado || ""}
                                            onChange={handleChange}
                                            placeholder="••••••••"
                                            className="w-full pl-12 pr-4 py-3 border-1 border-slate-300 rounded-sm hover:border-slate-400 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder:text-slate-400"
                                        />
                                    </div>
                                </div>

                                {/* Usuario SOL */}
                                <div className="space-y-2">
                                    <label className="block text-sm font-semibold text-slate-700">
                                        Usuario SOL
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <Mail className="w-5 h-5 text-slate-400" />
                                        </div>
                                        <input
                                            type="text"
                                            name="usuario_sol"
                                            value={formData.usuario_sol || ""}
                                            onChange={handleChange}
                                            placeholder={empresa.usuario_sol || "MODDATOS"}
                                            className="w-full pl-12 pr-4 py-3 border-1 border-slate-300 rounded-sm hover:border-slate-400 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder:text-slate-400 placeholder:opacity-60"
                                        />
                                    </div>
                                </div>

                                {/* Clave SOL */}
                                <div className="space-y-2">
                                    <label className="block text-sm font-semibold text-slate-700">
                                        Clave SOL
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <Key className="w-5 h-5 text-slate-400" />
                                        </div>
                                        <input
                                            type="password"
                                            name="clave_sol"
                                            value={formData.clave_sol || ""}
                                            onChange={handleChange}
                                            placeholder="••••••••"
                                            className="w-full pl-12 pr-4 py-3 border-1 border-slate-300 rounded-sm hover:border-slate-400 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder:text-slate-400"
                                        />
                                    </div>
                                </div>

                                {/* Modo */}
                                <div className="space-y-2">
                                    <label className="block text-sm font-semibold text-slate-700">
                                        Modo de Operación
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <Shield className="w-5 h-5 text-slate-400" />
                                        </div>
                                        <select
                                            name="modo"
                                            value={formData.modo || empresa.modo || "prueba"}
                                            onChange={handleChange}
                                            className="w-full pl-12 pr-4 py-3 border-1 border-slate-300 rounded-sm hover:border-slate-400 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none bg-white"
                                        >
                                            <option value="prueba">Prueba</option>
                                            <option value="produccion">Producción</option>
                                        </select>
                                    </div>
                                </div>

                                {/* Fecha expiración certificado */}
                                <div className="space-y-2">
                                    <label className="block text-sm font-semibold text-slate-700">
                                        Fecha de Expiración del Certificado
                                    </label>
                                    <input
                                        type="date"
                                        name="fecha_expiracion_certificado"
                                        value={formData.fecha_expiracion_certificado || ""}
                                        onChange={handleChange}
                                        className="w-full px-4 py-3 border-1 border-slate-300 rounded-sm hover:border-slate-400 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    />
                                </div>

                                {/* PSE autorizado */}
                                <div className="flex items-center gap-3 mt-6">
                                    <input
                                        type="checkbox"
                                        name="pse_autorizado"
                                        checked={!!formData.pse_autorizado}
                                        onChange={handleChange}
                                        className="h-4 w-4 text-blue-600 border-slate-300 rounded focus:ring-2 focus:ring-blue-500"
                                    />
                                    <label className="text-sm font-semibold text-slate-700">
                                        PSE Autorizado
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Botón de guardar */}
                    <div className="bg-slate-50 px-8 py-6 border border-slate-300 rounded-sm flex justify-between items-center">
                        <p className="text-sm text-slate-600">
                            <span className="text-red-500">*</span> Campos obligatorios -
                            Deje en blanco los campos que no desea modificar
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
                                    <span>Guardar Cambios</span>
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
                                <Building2 className="w-5 h-5 text-white" />
                            </div>
                        </div>
                        <div>
                            <h3 className="font-semibold text-blue-900 mb-1">
                                Información importante
                            </h3>
                            <p className="text-sm text-blue-800 leading-relaxed">
                                Los datos de la empresa se utilizarán para generar los
                                comprobantes electrónicos que se enviarán a SUNAT. Asegúrate de
                                que la información sea correcta y esté actualizada.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {/* Columna lateral - Vista previa de datos */}
            <div className="lg:col-span-1">
                <div className="bg-white rounded-sm border border-slate-300 overflow-hidden sticky top-4">
                    <div className="bg-slate-100 px-4 py-3 border-b border-slate-200">
                        <h3 className="text-sm font-semibold text-slate-700 flex items-center gap-2">
                            <Eye className="w-4 h-4" />
                            Datos Actuales
                        </h3>
                    </div>

                    <div className="p-4 space-y-4">
                        {logoUrl && (
                            <div className="flex justify-center pb-4 border-b border-slate-200">
                                <img
                                    src={logoUrl}
                                    alt="Logo de la Empresa"
                                    className="w-32 h-32 object-contain rounded-md"
                                />
                            </div>
                        )}

                        <div>
                            <label className="block text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">
                                RUC
                            </label>
                            <p className="text-sm text-slate-900 font-medium">
                                {empresa.ruc || (
                                    <span className="text-slate-400 italic">No especificado</span>
                                )}
                            </p>
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">
                                Razón Social
                            </label>
                            <p className="text-sm text-slate-900 font-medium">
                                {empresa.razon_social || (
                                    <span className="text-slate-400 italic">No especificado</span>
                                )}
                            </p>
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">
                                Dirección Fiscal
                            </label>
                            <p className="text-sm text-slate-900 leading-relaxed">
                                {empresa.direccion || (
                                    <span className="text-slate-400 italic">No especificado</span>
                                )}
                            </p>
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">
                                Teléfono
                            </label>
                            <p className="text-sm text-slate-900">
                                {empresa.telefono || (
                                    <span className="text-slate-400 italic">No especificado</span>
                                )}
                            </p>
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">
                                Correo Electrónico
                            </label>
                            <p className="text-sm text-slate-900 break-words">
                                {empresa.email || (
                                    <span className="text-slate-400 italic">No especificado</span>
                                )}
                            </p>
                        </div>

                        <div className="border-t border-slate-200 pt-4">
                            <label className="block text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">
                                Usuario SOL
                            </label>
                            <p className="text-sm text-slate-900">
                                {empresa.usuario_sol || (
                                    <span className="text-slate-400 italic">No configurado</span>
                                )}
                            </p>
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">
                                Modo
                            </label>
                            <p className="text-sm text-slate-900">
                                {empresa.modo === 'produccion' ? (
                                    <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Producción
                                    </span>
                                ) : (
                                    <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Prueba
                                    </span>
                                )}
                            </p>
                        </div>
                    </div>

                    <div className="bg-slate-50 px-4 py-3 border-t border-slate-200">
                        <p className="text-xs text-slate-500 text-center">
                            Los cambios se reflejan al guardar
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}