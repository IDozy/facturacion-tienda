import { useEffect, useMemo, useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import {
  AlertCircle,
  Building2,
  CheckCircle2,
  Image as ImageIcon,
  Loader2,
  RefreshCcw,
  Save,
  Settings2,
  ShieldCheck,
  Upload,
} from "lucide-react";
import { z } from "zod";
import toast from "react-hot-toast";

import { actualizarEmpresaActual, getEmpresaActual, subirLogoEmpresa } from "../../services/empresaService";
import type { Empresa } from "../../types/Empresa";
import { Button } from "../ui/button";
import { Input } from "../ui/input";
import { Label } from "../ui/label";

const formSchema = z.object({
  razon_social: z.string().min(1, "La razón social es obligatoria"),
  nombre_comercial: z.string().optional().or(z.literal("")),
  ruc: z
    .string()
    .trim()
    .regex(/^\d{11}$/g, "El RUC debe tener 11 dígitos"),
  telefono: z.string().optional().or(z.literal("")),
  email: z.string().email("Correo inválido").optional().or(z.literal("")),
  direccion_fiscal: z.string().optional().or(z.literal("")),
  departamento: z.string().optional().or(z.literal("")),
  provincia: z.string().optional().or(z.literal("")),
  distrito: z.string().optional().or(z.literal("")),
  moneda: z.enum(["PEN", "USD"]),
  igv_porcentaje: z.coerce.number().min(0).max(100),
  incluye_igv_por_defecto: z.boolean(),
  serie_factura: z.string().optional().or(z.literal("")),
  serie_boleta: z.string().optional().or(z.literal("")),
  numero_factura_actual: z.coerce.number().min(0),
  numero_boleta_actual: z.coerce.number().min(0),
  formato_fecha: z.enum(["DD/MM/YYYY", "MM/DD/YYYY", "YYYY-MM-DD"]),
  decimales: z.coerce.number().min(0).max(6),
  zona_horaria: z.string().min(1, "Selecciona una zona horaria"),
});

const defaultValues: z.infer<typeof formSchema> = {
  razon_social: "",
  nombre_comercial: "",
  ruc: "",
  telefono: "",
  email: "",
  direccion_fiscal: "",
  departamento: "",
  provincia: "",
  distrito: "",
  moneda: "PEN",
  igv_porcentaje: 18,
  incluye_igv_por_defecto: true,
  serie_factura: "",
  serie_boleta: "",
  numero_factura_actual: 1,
  numero_boleta_actual: 1,
  formato_fecha: "DD/MM/YYYY",
  decimales: 2,
  zona_horaria: "America/Lima",
};

const zonasHorarias = ["America/Lima", "America/Bogota", "America/Mexico_City", "America/Santiago", "UTC"];

function buildFormValues(empresa: Empresa | null): z.infer<typeof formSchema> {
  if (!empresa) return defaultValues;

  return {
    ...defaultValues,
    razon_social: empresa.razon_social || "",
    nombre_comercial: empresa.nombre_comercial || "",
    ruc: empresa.ruc || "",
    telefono: empresa.telefono || "",
    email: empresa.email || "",
    direccion_fiscal: empresa.direccion_fiscal || empresa.direccion || "",
    departamento: empresa.departamento || "",
    provincia: empresa.provincia || "",
    distrito: empresa.distrito || "",
    moneda: (empresa.moneda as "PEN" | "USD") || "PEN",
    igv_porcentaje: Number(empresa.igv_porcentaje ?? 18),
    incluye_igv_por_defecto: Boolean(empresa.incluye_igv_por_defecto ?? true),
    serie_factura: empresa.serie_factura || "",
    serie_boleta: empresa.serie_boleta || "",
    numero_factura_actual: Number(empresa.numero_factura_actual ?? 1),
    numero_boleta_actual: Number(empresa.numero_boleta_actual ?? 1),
    formato_fecha: (empresa.formato_fecha as any) || "DD/MM/YYYY",
    decimales: Number(empresa.decimales ?? 2),
    zona_horaria: empresa.zona_horaria || "America/Lima",
  };
}

export default function EmpresaForm() {
  const [empresa, setEmpresa] = useState<Empresa | null>(null);
  const [initialValues, setInitialValues] = useState<z.infer<typeof formSchema>>(defaultValues);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [uploadingLogo, setUploadingLogo] = useState(false);
  const [serverError, setServerError] = useState<string | null>(null);
  const [logoPreview, setLogoPreview] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState<"general" | "facturacion" | "preferencias">("general");

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    mode: "onChange",
    defaultValues,
  });

  const { register, handleSubmit, formState, reset, setError, watch, trigger } = form;

  useEffect(() => {
    const load = async () => {
      setLoading(true);
      setServerError(null);
      try {
        const data = await getEmpresaActual();
        setEmpresa(data);
        const normalized = buildFormValues(data);
        setInitialValues(normalized);
        reset(normalized);
        await trigger();
      } catch (error: any) {
        setServerError(error?.response?.data?.message || "No se pudo cargar la empresa.");
      } finally {
        setLoading(false);
      }
    };

    load();
  }, [reset, trigger]);

  const onSubmit = async (values: z.infer<typeof formSchema>) => {
    setSaving(true);
    setServerError(null);

    const payload: Partial<Empresa> = {
      ...values,
      email: values.email || undefined,
      telefono: values.telefono || undefined,
      direccion_fiscal: values.direccion_fiscal || undefined,
      departamento: values.departamento || undefined,
      provincia: values.provincia || undefined,
      distrito: values.distrito || undefined,
      serie_factura: values.serie_factura || undefined,
      serie_boleta: values.serie_boleta || undefined,
    };

    try {
      const updated = await actualizarEmpresaActual(payload);
      setEmpresa(updated);
      const normalized = buildFormValues(updated);
      setInitialValues(normalized);
      reset(normalized);
      toast.success("Cambios guardados correctamente");
    } catch (error: any) {
      if (error?.response?.data?.errors) {
        Object.entries(error.response.data.errors).forEach(([field, messages]) => {
          const message = Array.isArray(messages) ? messages[0] : (messages as string);
          setError(field as any, { message });
        });
      }
      setServerError(error?.response?.data?.message || "No pudimos guardar los cambios");
    } finally {
      setSaving(false);
    }
  };

  const handleRestore = () => {
    reset(initialValues);
    toast.success("Datos restaurados desde el servidor");
  };

  const handleLogoChange = async (event: React.ChangeEvent<HTMLInputElement>) => {
    if (!event.target.files?.length) return;

    const file = event.target.files[0];
    const previewUrl = URL.createObjectURL(file);
    setLogoPreview(previewUrl);

    setUploadingLogo(true);
    setServerError(null);
    try {
      const { logoUrl, empresa: updatedEmpresa } = await subirLogoEmpresa(file);
      setEmpresa(updatedEmpresa);
      toast.success("Logo actualizado");
      if (logoUrl) {
        setLogoPreview(logoUrl);
      }
    } catch (error: any) {
      setServerError(error?.response?.data?.message || "No pudimos subir el logo");
    } finally {
      setUploadingLogo(false);
    }
  };

  const currentLogo = useMemo(() => {
    if (logoPreview) return logoPreview;
    if (empresa?.logo_url) return empresa.logo_url;
    if ((empresa as any)?.logoUrl) return (empresa as any).logoUrl;
    return null;
  }, [empresa, logoPreview]);

  const renderSkeleton = () => (
    <div className="space-y-4">
      {[1, 2, 3].map((key) => (
        <div key={key} className="rounded-xl bg-white p-6 shadow-sm">
          <div className="flex gap-4">
            <div className="h-10 w-10 rounded-lg bg-slate-100" />
            <div className="flex-1 space-y-2">
              <div className="h-4 w-40 rounded bg-slate-100" />
              <div className="h-3 w-64 rounded bg-slate-100" />
            </div>
          </div>
          <div className="mt-6 grid grid-cols-2 gap-4 md:grid-cols-3">
            {[1, 2, 3, 4, 5, 6].map((field) => (
              <div key={field} className="h-12 rounded-md bg-slate-100" />
            ))}
          </div>
        </div>
      ))}
    </div>
  );

  if (loading) {
    return renderSkeleton();
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 rounded-xl bg-white p-6 shadow-sm">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p className="text-sm font-semibold uppercase tracking-wide text-blue-600">Configuración</p>
            <h1 className="text-2xl font-bold text-slate-900">Configuración de Empresa</h1>
            <p className="text-sm text-slate-600">
              Actualiza los datos fiscales y preferencias de facturación.
            </p>
          </div>
          <div className="flex flex-wrap gap-2">
            <Button
              type="button"
              variant="outline"
              disabled={saving}
              onClick={handleRestore}
            >
              <RefreshCcw className="size-4" />
              Restaurar
            </Button>
            <Button
              type="submit"
              form="empresa-config-form"
              disabled={saving || !formState.isValid}
            >
              {saving ? <Loader2 className="size-4 animate-spin" /> : <Save className="size-4" />}
              <span>Guardar cambios</span>
            </Button>
          </div>
        </div>
        {serverError && (
          <div className="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800">
            <AlertCircle className="size-4" />
            <span>{serverError}</span>
          </div>
        )}
      </div>

      <div className="overflow-hidden rounded-xl bg-white shadow-sm">
        <div className="flex flex-wrap gap-2 border-b border-slate-100 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-600">
          {[
            { id: "general", label: "Datos generales", icon: Building2 },
            { id: "facturacion", label: "Facturación", icon: ShieldCheck },
            { id: "preferencias", label: "Preferencias", icon: Settings2 },
          ].map((tab) => (
            <button
              key={tab.id}
              type="button"
              onClick={() => setActiveTab(tab.id as any)}
              className={`flex items-center gap-2 rounded-lg px-3 py-2 transition ${
                activeTab === tab.id
                  ? "bg-white text-blue-600 shadow-sm"
                  : "text-slate-600 hover:bg-white/80"
              }`}
            >
              <tab.icon className="size-4" />
              {tab.label}
            </button>
          ))}
        </div>

        <form id="empresa-config-form" onSubmit={handleSubmit(onSubmit)} className="space-y-6 p-6">
          {activeTab === "general" && (
            <div className="grid gap-6 lg:grid-cols-3">
              <div className="lg:col-span-2 space-y-6">
                <div className="rounded-xl border border-slate-100 p-5 shadow-sm">
                  <div className="flex items-center gap-2 pb-4">
                    <div className="flex size-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                      <Building2 className="size-5" />
                    </div>
                    <div>
                      <p className="text-xs font-semibold uppercase text-slate-500">Identidad</p>
                      <p className="text-base font-semibold text-slate-900">Datos generales</p>
                    </div>
                  </div>
                  <div className="grid gap-4 md:grid-cols-2">
                    <Field label="Razón social" required error={formState.errors.razon_social?.message}>
                      <Input placeholder="Mi Empresa SAC" {...register("razon_social")}/>
                    </Field>
                    <Field label="Nombre comercial" error={formState.errors.nombre_comercial?.message}>
                      <Input placeholder="Marca comercial" {...register("nombre_comercial")}/>
                    </Field>
                    <Field label="RUC" required error={formState.errors.ruc?.message}>
                      <Input placeholder="20123456789" maxLength={11} {...register("ruc")} />
                    </Field>
                    <Field label="Correo" error={formState.errors.email?.message}>
                      <Input placeholder="contacto@empresa.com" type="email" {...register("email")} />
                    </Field>
                    <Field label="Teléfono" error={formState.errors.telefono?.message}>
                      <Input placeholder="987654321" {...register("telefono")} />
                    </Field>
                    <Field label="Dirección fiscal" error={formState.errors.direccion_fiscal?.message}>
                      <Input placeholder="Av. Siempre Viva 123" {...register("direccion_fiscal")} />
                    </Field>
                    <Field label="Departamento" error={formState.errors.departamento?.message}>
                      <Input placeholder="Lima" {...register("departamento")} />
                    </Field>
                    <Field label="Provincia" error={formState.errors.provincia?.message}>
                      <Input placeholder="Lima" {...register("provincia")} />
                    </Field>
                    <Field label="Distrito" error={formState.errors.distrito?.message}>
                      <Input placeholder="Miraflores" {...register("distrito")} />
                    </Field>
                  </div>
                </div>
              </div>

              <div className="space-y-6">
                <div className="rounded-xl border border-slate-100 p-5 shadow-sm">
                  <div className="flex items-center gap-2 pb-4">
                    <div className="flex size-10 items-center justify-center rounded-lg bg-slate-50 text-slate-600">
                      <ImageIcon className="size-5" />
                    </div>
                    <div>
                      <p className="text-xs font-semibold uppercase text-slate-500">Identidad visual</p>
                      <p className="text-base font-semibold text-slate-900">Logo</p>
                    </div>
                  </div>

                  <div className="flex items-center gap-4">
                    <div className="flex size-20 items-center justify-center overflow-hidden rounded-xl border border-dashed border-slate-200 bg-slate-50">
                      {currentLogo ? (
                        <img src={currentLogo} alt="Logo" className="h-full w-full object-contain" />
                      ) : (
                        <ImageIcon className="size-8 text-slate-400" />
                      )}
                    </div>
                    <div className="flex-1 space-y-2">
                      <Label className="text-sm font-medium text-slate-700">Actualizar logo</Label>
                      <Input type="file" accept="image/*" onChange={handleLogoChange} disabled={uploadingLogo} />
                      <p className="text-xs text-slate-500">Formatos: JPG, PNG, SVG o WebP. Máx 2MB.</p>
                      {uploadingLogo && (
                        <div className="flex items-center gap-2 text-xs text-blue-600">
                          <Loader2 className="size-3 animate-spin" /> Subiendo logo...
                        </div>
                      )}
                    </div>
                  </div>
                </div>

                <div className="rounded-xl border border-slate-100 p-5 shadow-sm">
                  <div className="flex items-center gap-2 pb-3">
                    <div className="flex size-10 items-center justify-center rounded-lg bg-green-50 text-green-600">
                      <CheckCircle2 className="size-5" />
                    </div>
                    <div>
                      <p className="text-xs font-semibold uppercase text-slate-500">Estado</p>
                      <p className="text-base font-semibold text-slate-900">Resumen rápido</p>
                    </div>
                  </div>
                  <div className="space-y-2 text-sm text-slate-700">
                    <p><span className="font-semibold">Moneda:</span> {watch("moneda")}</p>
                    <p><span className="font-semibold">IGV:</span> {watch("igv_porcentaje")}%</p>
                    <p><span className="font-semibold">Serie Factura:</span> {watch("serie_factura") || "—"}</p>
                    <p><span className="font-semibold">Serie Boleta:</span> {watch("serie_boleta") || "—"}</p>
                  </div>
                </div>
              </div>
            </div>
          )}

          {activeTab === "facturacion" && (
            <div className="grid gap-6 lg:grid-cols-2">
              <div className="rounded-xl border border-slate-100 p-5 shadow-sm space-y-4">
                <SectionTitle title="Facturación" subtitle="Preferencias tributarias" />
                <div className="grid gap-4 md:grid-cols-2">
                  <Field label="Moneda" required error={formState.errors.moneda?.message}>
                    <select
                      {...register("moneda")}
                      className="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-400 focus:outline-none"
                    >
                      <option value="PEN">Soles (PEN)</option>
                      <option value="USD">Dólares (USD)</option>
                    </select>
                  </Field>
                  <Field label="IGV %" required error={formState.errors.igv_porcentaje?.message}>
                    <Input type="number" step="0.01" {...register("igv_porcentaje", { valueAsNumber: true })} />
                  </Field>
                  <Field label="Incluye IGV por defecto" error={formState.errors.incluye_igv_por_defecto?.message}>
                    <label className="flex cursor-pointer items-center gap-3">
                      <input
                        type="checkbox"
                        className="size-4 accent-blue-600"
                        {...register("incluye_igv_por_defecto")}
                      />
                      <span className="text-sm text-slate-700">Aplicar IGV automáticamente</span>
                    </label>
                  </Field>
                </div>
              </div>

              <div className="rounded-xl border border-slate-100 p-5 shadow-sm space-y-4">
                <SectionTitle title="Series y numeración" subtitle="Control de comprobantes" />
                <div className="grid gap-4 md:grid-cols-2">
                  <Field label="Serie factura" error={formState.errors.serie_factura?.message}>
                    <Input placeholder="F001" {...register("serie_factura")} />
                  </Field>
                  <Field label="Serie boleta" error={formState.errors.serie_boleta?.message}>
                    <Input placeholder="B001" {...register("serie_boleta")} />
                  </Field>
                  <Field label="Número actual factura" error={formState.errors.numero_factura_actual?.message}>
                    <Input type="number" min={0} {...register("numero_factura_actual", { valueAsNumber: true })} />
                  </Field>
                  <Field label="Número actual boleta" error={formState.errors.numero_boleta_actual?.message}>
                    <Input type="number" min={0} {...register("numero_boleta_actual", { valueAsNumber: true })} />
                  </Field>
                </div>
              </div>
            </div>
          )}

          {activeTab === "preferencias" && (
            <div className="grid gap-6 lg:grid-cols-2">
              <div className="rounded-xl border border-slate-100 p-5 shadow-sm space-y-4">
                <SectionTitle title="Formato" subtitle="Fechas y decimales" />
                <div className="grid gap-4 md:grid-cols-2">
                  <Field label="Formato de fecha" required error={formState.errors.formato_fecha?.message}>
                    <select
                      {...register("formato_fecha")}
                      className="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-400 focus:outline-none"
                    >
                      <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                      <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                      <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                    </select>
                  </Field>
                  <Field label="Decimales" required error={formState.errors.decimales?.message}>
                    <Input type="number" min={0} max={6} {...register("decimales", { valueAsNumber: true })} />
                  </Field>
                </div>
              </div>

              <div className="rounded-xl border border-slate-100 p-5 shadow-sm space-y-4">
                <SectionTitle title="Zona horaria" subtitle="Hora local del negocio" />
                <Field label="Zona horaria" required error={formState.errors.zona_horaria?.message}>
                  <select
                    {...register("zona_horaria")}
                    className="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-400 focus:outline-none"
                  >
                    {zonasHorarias.map((zone) => (
                      <option key={zone} value={zone}>
                        {zone}
                      </option>
                    ))}
                  </select>
                </Field>
              </div>
            </div>
          )}

          <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
            <div className="flex items-center gap-2 text-sm text-slate-600">
              <Upload className="size-4" />
              <span>Guarda tus cambios para que entren en vigencia.</span>
            </div>
            <div className="flex flex-wrap gap-2">
              <Button type="button" variant="outline" disabled={saving} onClick={handleRestore}>
                <RefreshCcw className="size-4" /> Restaurar
              </Button>
              <Button type="submit" disabled={saving || !formState.isValid}>
                {saving ? <Loader2 className="size-4 animate-spin" /> : <Save className="size-4" />}
                <span>Guardar cambios</span>
              </Button>
            </div>
          </div>
        </form>
      </div>
    </div>
  );
}

function SectionTitle({ title, subtitle }: { title: string; subtitle: string }) {
  return (
    <div>
      <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{title}</p>
      <p className="text-sm text-slate-600">{subtitle}</p>
    </div>
  );
}

function Field({
  label,
  children,
  error,
  required,
}: {
  label: string;
  children: React.ReactNode;
  error?: string;
  required?: boolean;
}) {
  return (
    <div className="space-y-1.5">
      <Label className="text-sm font-medium text-slate-700">
        {label} {required && <span className="text-red-500">*</span>}
      </Label>
      {children}
      {error && (
        <p className="flex items-center gap-1 text-xs text-red-600">
          <AlertCircle className="size-3" />
          {error}
        </p>
      )}
    </div>
  );
}
