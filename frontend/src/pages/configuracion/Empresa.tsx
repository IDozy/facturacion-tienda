import { useEffect, useMemo, useState, type ComponentType, type SVGProps } from 'react';
import { useForm, useFieldArray } from 'react-hook-form';
import { z } from 'zod';
import { zodResolver } from '@hookform/resolvers/zod';
import { AlertCircle, Building2, CheckCircle2, Eye, EyeOff, Loader2, PlugZap, Save, Shield, ShieldCheck, Wallet, Warehouse as WarehouseIcon, Wrench } from 'lucide-react';

import { Button } from '../../components/ui/button';
import { Input } from '../../components/ui/input';
import { Label } from '../../components/ui/label';
import { toast } from '../../lib/sonner';
import { authService } from '../../services/auth';
import { fetchCompanyConfiguration, fetchSunatStatus, saveCompanyConfiguration, saveSunatCredentials, testSunatConnection, uploadSunatCertificate } from '../../services/companySettingsService';
import type { CompanyConfiguration } from '../../types/CompanyConfiguration';

const serieSchema = z.object({
  tipo: z.enum(['FACTURA', 'BOLETA', 'NC', 'ND', 'GUIA']),
  serie: z.string().min(1),
  correlativoInicial: z.coerce.number().int().min(1),
  correlativoActual: z.coerce.number().int().min(1),
  automatico: z.boolean().default(true),
  activo: z.boolean().default(true),
});

const currencySchema = z.object({
  code: z.string().min(2).max(3),
  name: z.string().min(2),
  preciosIncluyenIgv: z.boolean(),
  igvRate: z.coerce.number().min(0).max(100),
  redondeo: z.boolean(),
  tipoCambioAutomatico: z.boolean(),
});

const exchangeRateSchema = z.object({
  currencyCode: z.string().min(2).max(3),
  fecha: z.string(),
  compra: z.coerce.number().optional(),
  venta: z.coerce.number().optional(),
  fuente: z.string().optional(),
  automatico: z.boolean().optional(),
});

const warehouseSchema = z.object({
  nombre: z.string().min(2),
  principal: z.boolean(),
  stockNegativo: z.boolean(),
  manejaSeries: z.boolean(),
  manejaLotes: z.boolean(),
  codigoBarras: z.string().optional(),
  activo: z.boolean(),
});

const cashboxSchema = z.object({
  nombre: z.string().min(2),
  moneda: z.string().default('PEN'),
  porDefecto: z.boolean(),
  manejaCheques: z.boolean(),
  liquidacionDiaria: z.boolean(),
  flujoAutomatico: z.boolean(),
});

const bankSchema = z.object({
  banco: z.string().min(2),
  numero: z.string().min(4),
  moneda: z.string().default('PEN'),
  esPrincipal: z.boolean(),
  manejaCheques: z.boolean(),
});

const integrationSchema = z.object({
  tipo: z.string().min(3),
  params: z.union([z.record(z.any()), z.string()]).optional(),
  activo: z.boolean(),
});

const formSchema = z.object({
  general: z.object({
    razonSocial: z.string().min(1),
    nombreComercial: z.string().optional(),
    ruc: z.string().regex(/^\d{11}$/),
    direccionFiscal: z.string().min(1),
    direccionComercial: z.string().optional(),
    telefono: z.string().optional(),
    email: z.string().email().optional(),
    logoUrl: z.string().optional(),
    region: z.string().optional(),
    ciudad: z.string().optional(),
    pais: z.string().default('Perú'),
  }),
  sunat: z.object({
    regimen: z.string().min(1),
    tipoContribuyente: z.string().optional(),
    afectacionIgv: z.string().min(1),
    certificadoUrl: z.string().optional(),
    certificadoEstado: z.string().optional(),
    certificadoVigenciaDesde: z.string().optional(),
    certificadoVigenciaHasta: z.string().optional(),
    ambiente: z.enum(['PRUEBAS', 'PRODUCCION']),
    hasSolCredentials: z.boolean().optional(),
    hasCertificate: z.boolean().optional(),
    certificateStatus: z.enum(['ACTIVE', 'EXPIRED', 'REVOKED']).nullable().optional(),
    certificateValidFrom: z.string().nullable().optional(),
    certificateValidUntil: z.string().nullable().optional(),
  }),
  documentos: z.object({
    series: z.array(serieSchema).min(1),
  }),
  monedas: z.object({
    monedaBase: currencySchema,
    secundarias: z.array(currencySchema).default([]),
    exchangeRates: z.array(exchangeRateSchema).default([]),
  }),
  almacenes: z.array(warehouseSchema).min(1),
  cajaBancos: z.object({
    cajas: z.array(cashboxSchema).min(1),
    bancos: z.array(bankSchema).default([]),
  }),
  contabilidad: z.object({
    planContable: z.string().optional(),
    cuentaVentas: z.string().optional(),
    cuentaCompras: z.string().optional(),
    cuentaIgv: z.string().optional(),
    cuentaCaja: z.string().optional(),
    cuentaBancos: z.string().optional(),
    contabilizacionAutomatica: z.boolean(),
    centrosCostoObligatorios: z.boolean(),
    periodos: z.array(z.object({ nombre: z.string(), estado: z.enum(['ABIERTO', 'CERRADO']) })).default([]),
  }),
  seguridad: z.object({
    roles: z.array(z.object({ nombre: z.string(), editable: z.boolean().optional() })).default([]),
    privilegios: z
      .object({
        precios: z.boolean().default(false),
        reportes: z.boolean().default(false),
        eliminaciones: z.boolean().default(false),
      })
      .default({ precios: false, reportes: false, eliminaciones: false }),
  }),
  preferencias: z.object({
    idioma: z.string().min(2),
    zonaHoraria: z.string().min(3),
    formatoFecha: z.string().min(4),
    decimales: z.coerce.number().min(0).max(6),
    alertas: z.array(z.object({ clave: z.string(), activo: z.boolean() })).default([]),
  }),
  integraciones: z.array(integrationSchema).default([]),
});

type FormValues = z.infer<typeof formSchema>;

const emptyForm: FormValues = {
  general: {
    razonSocial: '',
    nombreComercial: '',
    ruc: '',
    direccionFiscal: '',
    direccionComercial: '',
    telefono: '',
    email: '',
    logoUrl: '',
    region: '',
    ciudad: '',
    pais: 'Perú',
  },
  sunat: {
    regimen: 'GENERAL',
    tipoContribuyente: '',
    afectacionIgv: 'GRAVADO',
    certificadoUrl: '',
    certificadoEstado: '',
    certificadoVigenciaDesde: '',
    certificadoVigenciaHasta: '',
    ambiente: 'PRUEBAS',
    hasSolCredentials: false,
    hasCertificate: false,
    certificateStatus: null,
    certificateValidFrom: null,
    certificateValidUntil: null,
  },
  documentos: {
    series: [
      { tipo: 'FACTURA', serie: 'F001', correlativoInicial: 1, correlativoActual: 1, automatico: true, activo: true },
      { tipo: 'BOLETA', serie: 'B001', correlativoInicial: 1, correlativoActual: 1, automatico: true, activo: true },
    ],
  },
  monedas: {
    monedaBase: {
      code: 'PEN',
      name: 'Sol Peruano',
      preciosIncluyenIgv: true,
      igvRate: 18,
      redondeo: false,
      tipoCambioAutomatico: true,
    },
    secundarias: [
      {
        code: 'USD',
        name: 'Dólar Americano',
        preciosIncluyenIgv: false,
        igvRate: 0,
        redondeo: false,
        tipoCambioAutomatico: true,
      },
    ],
    exchangeRates: [],
  },
  almacenes: [
    {
      nombre: 'Principal',
      principal: true,
      stockNegativo: false,
      manejaSeries: false,
      manejaLotes: false,
      codigoBarras: '',
      activo: true,
    },
  ],
  cajaBancos: {
    cajas: [
      {
        nombre: 'Caja General',
        moneda: 'PEN',
        porDefecto: true,
        manejaCheques: false,
        liquidacionDiaria: true,
        flujoAutomatico: true,
      },
    ],
    bancos: [
      {
        banco: 'Banco de la Nación',
        numero: '000-000',
        moneda: 'PEN',
        esPrincipal: true,
        manejaCheques: true,
      },
    ],
  },
  contabilidad: {
    planContable: 'PCGE 2019',
    cuentaVentas: '7011',
    cuentaCompras: '6011',
    cuentaIgv: '4011',
    cuentaCaja: '1011',
    cuentaBancos: '1041',
    contabilizacionAutomatica: true,
    centrosCostoObligatorios: false,
    periodos: [],
  },
  seguridad: {
    roles: [],
    privilegios: { precios: false, reportes: false, eliminaciones: false },
  },
  preferencias: {
    idioma: 'es-PE',
    zonaHoraria: 'America/Lima',
    formatoFecha: 'dd/MM/yyyy',
    decimales: 2,
    alertas: [],
  },
  integraciones: [
    { tipo: 'facturacion_electronica', params: { endpoint: '' }, activo: true },
    { tipo: 'smtp', params: { host: '', port: 587 }, activo: true },
  ],
};

type SectionKey = keyof FormValues;
type SectionConfig = { key: SectionKey; label: string; icon: ComponentType<{ className?: string }> };

const sections: SectionConfig[] = [
  { key: 'general', label: 'Datos Generales', icon: Building2 },
  { key: 'sunat', label: 'SUNAT', icon: Shield },
  { key: 'documentos', label: 'Documentos', icon: CheckCircle2 },
  { key: 'monedas', label: 'Monedas e Impuestos', icon: Wallet },
  { key: 'almacenes', label: 'Almacenes', icon: WarehouseIcon },
  { key: 'cajaBancos', label: 'Caja y Bancos', icon: Wrench },
  { key: 'contabilidad', label: 'Contabilidad', icon: BookIcon },
  { key: 'seguridad', label: 'Usuarios y Seguridad', icon: Shield },
  { key: 'preferencias', label: 'Preferencias', icon: SettingsIcon },
  { key: 'integraciones', label: 'Integraciones', icon: PlugIcon },
];

function BookIcon(props: SVGProps<SVGSVGElement>) {
  return <svg {...props} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20" /><path d="M6.5 2H20v20" /><path d="M6.5 2A2.5 2.5 0 0 0 4 4.5v15" /><path d="M8 6h6" /></svg>;
}

function SettingsIcon(props: SVGProps<SVGSVGElement>) {
  return <svg {...props} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 8 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9c.7 0 1.34.4 1.64 1.02.3.63.27 1.37-.08 1.96-.35.59-.97 1.02-1.66 1.02z" /></svg>;
}

function PlugIcon(props: SVGProps<SVGSVGElement>) {
  return <svg {...props} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 22v-5" /><path d="M9 7V2" /><path d="M15 7V2" /><path d="M5 7h14" /><path d="M17 7a5 5 0 0 1-10 0" /></svg>;
}

export default function EmpresaPage() {
  const [active, setActive] = useState<SectionKey>('general');
  const [serverCompletion, setServerCompletion] = useState<Record<string, boolean>>({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [serverError, setServerError] = useState<string | null>(null);
  const canEditFromRole = authService.hasRole(['admin', 'administrador']);
  const [sunatStatus, setSunatStatus] = useState<{
    hasSolCredentials: boolean;
    hasCertificate: boolean;
    certificateStatus: 'ACTIVE' | 'EXPIRED' | 'REVOKED' | null;
    certificateValidFrom: string | null;
    certificateValidUntil: string | null;
  }>({
    hasSolCredentials: false,
    hasCertificate: false,
    certificateStatus: null,
    certificateValidFrom: null,
    certificateValidUntil: null,
  });
  const [sunatUser, setSunatUser] = useState('');
  const [sunatPassword, setSunatPassword] = useState('');
  const [sunatPasswordVisible, setSunatPasswordVisible] = useState(false);
  const [certificatePassword, setCertificatePassword] = useState('');
  const [certificateFile, setCertificateFile] = useState<File | null>(null);
  const [testingSunat, setTestingSunat] = useState(false);
  const [savingCreds, setSavingCreds] = useState(false);
  const [uploadingCert, setUploadingCert] = useState(false);

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: emptyForm,
    mode: 'onChange',
  });

  const seriesArray = useFieldArray({ control: form.control, name: 'documentos.series' });
  const secundariaArray = useFieldArray({ control: form.control, name: 'monedas.secundarias' });
  const almacenesArray = useFieldArray({ control: form.control, name: 'almacenes' });
  const cajasArray = useFieldArray({ control: form.control, name: 'cajaBancos.cajas' });
  const bancosArray = useFieldArray({ control: form.control, name: 'cajaBancos.bancos' });
  const integracionesArray = useFieldArray({ control: form.control, name: 'integraciones' });

  const canEdit = useMemo(() => {
    const flag = (form.watch().seguridad.roles ?? []).some((r) => r.editable);
    return canEditFromRole || flag;
  }, [canEditFromRole, form]);

  useEffect(() => {
    const load = async () => {
      setLoading(true);
      try {
        const data = await fetchCompanyConfiguration();
        setServerCompletion(data.completion ?? {});
        form.reset({
          ...emptyForm,
          ...data,
        });
        const status = await fetchSunatStatus();
        setSunatStatus({
          hasSolCredentials: status.hasSolCredentials,
          hasCertificate: status.hasCertificate,
          certificateStatus: (status.certificateStatus as 'ACTIVE' | 'EXPIRED' | 'REVOKED' | null) ?? null,
          certificateValidFrom: status.certificateValidFrom ?? null,
          certificateValidUntil: status.certificateValidUntil ?? null,
        });
      } catch (error: any) {
        setServerError(error?.response?.data?.message || 'No se pudo cargar la configuración');
      } finally {
        setLoading(false);
      }
    };
    load();
  }, [form]);

  const badgeFor = (key: string) => {
    const completed = serverCompletion[key];
    return (
      <span
        className={`rounded-full px-2 py-0.5 text-[11px] font-semibold ${
          completed ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'
        }`}
      >
        {completed ? 'Completo' : 'Incompleto'}
      </span>
    );
  };

  const onSubmit = async (values: FormValues) => {
    setSaving(true);
    setServerError(null);
    try {
      const payload: CompanyConfiguration = {
        ...values,
        integraciones: values.integraciones,
      };
      const saved = await saveCompanyConfiguration(payload);
      toast.success('Configuración guardada');
      setServerCompletion(saved.completion ?? {});
      form.reset({ ...values, ...saved });
    } catch (error: any) {
      setServerError(error?.response?.data?.message || 'No pudimos guardar los cambios');
      toast.error('Error al guardar');
    } finally {
      setSaving(false);
    }
  };

  const renderSeriesControls = () => (
    <div className="space-y-3">
      {seriesArray.fields.map((field, idx) => (
        <div key={field.id} className="grid grid-cols-6 gap-3 items-end border rounded-lg p-3">
          <div>
            <Label>Tipo</Label>
            <select
              className="w-full rounded-md border border-slate-200 px-2 py-2 text-sm"
              disabled={!canEdit}
              {...form.register(`documentos.series.${idx}.tipo` as const)}
            >
              {['FACTURA', 'BOLETA', 'NC', 'ND', 'GUIA'].map((t) => (
                <option key={t} value={t}>{t}</option>
              ))}
            </select>
          </div>
          <div>
            <Label>Serie</Label>
            <Input disabled={!canEdit} {...form.register(`documentos.series.${idx}.serie` as const)} />
          </div>
          <div>
            <Label>Correlativo inicial</Label>
            <Input type="number" disabled={!canEdit} {...form.register(`documentos.series.${idx}.correlativoInicial` as const, { valueAsNumber: true })} />
          </div>
          <div>
            <Label>Correlativo actual</Label>
            <Input type="number" disabled={!canEdit} {...form.register(`documentos.series.${idx}.correlativoActual` as const, { valueAsNumber: true })} />
          </div>
          <div className="flex flex-col gap-1">
            <Label>Automático</Label>
            <input type="checkbox" disabled={!canEdit} {...form.register(`documentos.series.${idx}.automatico` as const)} />
          </div>
          <div className="flex flex-col gap-1">
            <Label>Activo</Label>
            <input type="checkbox" disabled={!canEdit} {...form.register(`documentos.series.${idx}.activo` as const)} />
          </div>
          {canEdit && (
            <div className="col-span-6 text-right">
              <Button type="button" variant="ghost" onClick={() => seriesArray.remove(idx)}>Eliminar</Button>
            </div>
          )}
        </div>
      ))}
      {canEdit && (
        <Button
          type="button"
          variant="outline"
          onClick={() =>
            seriesArray.append({ tipo: 'FACTURA', serie: 'F' + String(seriesArray.fields.length + 1).padStart(3, '0'), correlativoInicial: 1, correlativoActual: 1, automatico: true, activo: true })
          }
        >
          Agregar serie
        </Button>
      )}
    </div>
  );

  const renderMonedas = () => (
    <div className="space-y-4">
      <div className="grid grid-cols-2 gap-4">
        <Field label="Moneda base" required>
          <div className="grid grid-cols-2 gap-2">
            <Input disabled={!canEdit} placeholder="PEN" {...form.register('monedas.monedaBase.code')} />
            <Input disabled={!canEdit} placeholder="Nombre" {...form.register('monedas.monedaBase.name')} />
            <Input disabled={!canEdit} type="number" step="0.01" placeholder="IGV %" {...form.register('monedas.monedaBase.igvRate', { valueAsNumber: true })} />
            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" disabled={!canEdit} {...form.register('monedas.monedaBase.preciosIncluyenIgv')} /> Precios incluyen IGV
            </label>
            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" disabled={!canEdit} {...form.register('monedas.monedaBase.redondeo')} /> Redondeo
            </label>
            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" disabled={!canEdit} {...form.register('monedas.monedaBase.tipoCambioAutomatico')} /> TC automático
            </label>
          </div>
        </Field>
      </div>

      <div className="space-y-2">
        <p className="text-sm font-semibold">Monedas secundarias</p>
        {secundariaArray.fields.map((field, idx) => (
          <div key={field.id} className="grid grid-cols-6 gap-2 items-end border rounded-lg p-3">
            <Input disabled={!canEdit} placeholder="Código" {...form.register(`monedas.secundarias.${idx}.code` as const)} />
            <Input disabled={!canEdit} placeholder="Nombre" {...form.register(`monedas.secundarias.${idx}.name` as const)} />
            <Input disabled={!canEdit} type="number" step="0.01" placeholder="IGV %" {...form.register(`monedas.secundarias.${idx}.igvRate` as const, { valueAsNumber: true })} />
            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" disabled={!canEdit} {...form.register(`monedas.secundarias.${idx}.preciosIncluyenIgv` as const)} /> Incluye IGV
            </label>
            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" disabled={!canEdit} {...form.register(`monedas.secundarias.${idx}.redondeo` as const)} /> Redondeo
            </label>
            {canEdit && <Button type="button" variant="ghost" onClick={() => secundariaArray.remove(idx)}>Quitar</Button>}
          </div>
        ))}
        {canEdit && (
          <Button type="button" variant="outline" onClick={() => secundariaArray.append({ code: 'USD', name: 'USD', preciosIncluyenIgv: false, igvRate: 0, redondeo: false, tipoCambioAutomatico: true })}>
            Agregar moneda
          </Button>
        )}
      </div>
    </div>
  );

  const renderAlmacenes = () => (
    <div className="space-y-3">
      {almacenesArray.fields.map((field, idx) => (
        <div key={field.id} className="grid grid-cols-5 gap-3 items-end border rounded-lg p-3">
          <Input disabled={!canEdit} placeholder="Nombre" {...form.register(`almacenes.${idx}.nombre` as const)} />
          <label className="flex items-center gap-2 text-sm">
            <input type="checkbox" disabled={!canEdit} {...form.register(`almacenes.${idx}.principal` as const)} /> Principal
          </label>
          <label className="flex items-center gap-2 text-sm">
            <input type="checkbox" disabled={!canEdit} {...form.register(`almacenes.${idx}.stockNegativo` as const)} /> Permitir stock negativo
          </label>
          <label className="flex items-center gap-2 text-sm">
            <input type="checkbox" disabled={!canEdit} {...form.register(`almacenes.${idx}.manejaSeries` as const)} /> Series/Lotes
          </label>
          {canEdit && <Button type="button" variant="ghost" onClick={() => almacenesArray.remove(idx)}>Quitar</Button>}
        </div>
      ))}
      {canEdit && <Button type="button" variant="outline" onClick={() => almacenesArray.append({ nombre: 'Nuevo', principal: false, stockNegativo: false, manejaSeries: false, manejaLotes: false, codigoBarras: '', activo: true })}>Agregar almacén</Button>}
    </div>
  );

  const renderCajaBancos = () => (
    <div className="space-y-4">
      <div className="space-y-2">
        <p className="text-sm font-semibold">Cajas</p>
        {cajasArray.fields.map((field, idx) => (
          <div key={field.id} className="grid grid-cols-5 gap-3 items-end border rounded-lg p-3">
            <Input disabled={!canEdit} placeholder="Nombre" {...form.register(`cajaBancos.cajas.${idx}.nombre` as const)} />
            <Input disabled={!canEdit} placeholder="Moneda" {...form.register(`cajaBancos.cajas.${idx}.moneda` as const)} />
            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" disabled={!canEdit} {...form.register(`cajaBancos.cajas.${idx}.porDefecto` as const)} /> Por defecto
            </label>
            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" disabled={!canEdit} {...form.register(`cajaBancos.cajas.${idx}.liquidacionDiaria` as const)} /> Liquidación diaria
            </label>
            {canEdit && <Button type="button" variant="ghost" onClick={() => cajasArray.remove(idx)}>Quitar</Button>}
          </div>
        ))}
        {canEdit && <Button type="button" variant="outline" onClick={() => cajasArray.append({ nombre: 'Caja', moneda: 'PEN', porDefecto: false, manejaCheques: false, liquidacionDiaria: false, flujoAutomatico: true })}>Agregar caja</Button>}
      </div>

      <div className="space-y-2">
        <p className="text-sm font-semibold">Cuentas bancarias</p>
        {bancosArray.fields.map((field, idx) => (
          <div key={field.id} className="grid grid-cols-5 gap-3 items-end border rounded-lg p-3">
            <Input disabled={!canEdit} placeholder="Banco" {...form.register(`cajaBancos.bancos.${idx}.banco` as const)} />
            <Input disabled={!canEdit} placeholder="Número" {...form.register(`cajaBancos.bancos.${idx}.numero` as const)} />
            <Input disabled={!canEdit} placeholder="Moneda" {...form.register(`cajaBancos.bancos.${idx}.moneda` as const)} />
            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" disabled={!canEdit} {...form.register(`cajaBancos.bancos.${idx}.esPrincipal` as const)} /> Principal
            </label>
            {canEdit && <Button type="button" variant="ghost" onClick={() => bancosArray.remove(idx)}>Quitar</Button>}
          </div>
        ))}
        {canEdit && <Button type="button" variant="outline" onClick={() => bancosArray.append({ banco: 'Banco', numero: '', moneda: 'PEN', esPrincipal: false, manejaCheques: false })}>Agregar cuenta</Button>}
      </div>
    </div>
  );

  const renderIntegraciones = () => (
    <div className="space-y-3">
      {integracionesArray.fields.map((field, idx) => (
        <div key={field.id} className="grid grid-cols-4 gap-3 items-end border rounded-lg p-3">
          <Input disabled={!canEdit} placeholder="Tipo" {...form.register(`integraciones.${idx}.tipo` as const)} />
          <div className="col-span-2">
            <Label className="text-xs text-slate-600">Params (JSON)</Label>
            <textarea
              className="mt-1 w-full rounded-md border border-slate-200 px-2 py-2 text-sm"
              rows={2}
              disabled={!canEdit}
              value={
                typeof form.watch(`integraciones.${idx}.params` as const) === 'string'
                  ? (form.watch(`integraciones.${idx}.params` as const) as string)
                  : JSON.stringify(form.watch(`integraciones.${idx}.params` as const) ?? {})
              }
              onChange={(e) => {
                const raw = e.target.value;
                try {
                  const parsed = JSON.parse(raw);
                  form.setValue(`integraciones.${idx}.params`, parsed);
                } catch {
                  form.setValue(`integraciones.${idx}.params`, raw);
                }
              }}
            />
          </div>
          <label className="flex items-center gap-2 text-sm">
            <input type="checkbox" disabled={!canEdit} {...form.register(`integraciones.${idx}.activo` as const)} /> Activo
          </label>
          {canEdit && <Button type="button" variant="ghost" onClick={() => integracionesArray.remove(idx)}>Quitar</Button>}
        </div>
      ))}
      {canEdit && <Button type="button" variant="outline" onClick={() => integracionesArray.append({ tipo: 'nueva_integracion', params: {}, activo: true })}>Agregar integración</Button>}
    </div>
  );

  if (loading) {
    return (
      <div className="flex items-center justify-center py-20 text-slate-500">
        <Loader2 className="mr-2 h-5 w-5 animate-spin" /> Cargando configuración...
      </div>
    );
  }

  return (
    <div className="grid gap-6 lg:grid-cols-[260px_1fr]">
      <aside className="space-y-2 rounded-xl bg-white p-4 shadow-sm">
        <p className="text-xs font-semibold uppercase text-slate-500">Configuración de empresa</p>
        <div className="space-y-1">
          {sections.map((section) => (
            <button
              key={section.key}
              type="button"
              onClick={() => setActive(section.key)}
              className={`flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm transition ${
                active === section.key ? 'bg-blue-50 text-blue-700' : 'hover:bg-slate-50'
              }`}
            >
              <span className="flex items-center gap-2">
                <section.icon className="h-4 w-4" />
                {section.label}
              </span>
              {badgeFor(section.key)}
            </button>
          ))}
        </div>
      </aside>

      <main className="space-y-4">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-xs font-semibold uppercase text-slate-500">/dashboard/configuracion/empresa</p>
            <h1 className="text-2xl font-bold text-slate-900">Configuración integral de empresa</h1>
            <p className="text-sm text-slate-600">Compatible con facturación electrónica SUNAT</p>
          </div>
          <div className="flex items-center gap-2">
            <Button type="button" variant="outline" disabled={!canEdit || saving} onClick={() => form.reset(form.getValues())}>
              Restaurar
            </Button>
            <Button type="button" disabled={!canEdit || saving} onClick={form.handleSubmit(onSubmit)}>
              {saving ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Save className="mr-2 h-4 w-4" />}Guardar
            </Button>
          </div>
        </div>

        {serverError && (
          <div className="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800">
            <AlertCircle className="h-4 w-4" /> {serverError}
          </div>
        )}

        <form className="space-y-6" onSubmit={form.handleSubmit(onSubmit)}>
          {active === 'general' && (
            <Card title="Datos Generales" description="Identidad legal y comercial">
              <div className="grid gap-4 md:grid-cols-2">
                <Field label="Razón Social" required error={form.formState.errors.general?.razonSocial?.message}>
                  <Input disabled={!canEdit} {...form.register('general.razonSocial')} />
                </Field>
                <Field label="Nombre Comercial" error={form.formState.errors.general?.nombreComercial?.message}>
                  <Input disabled={!canEdit} {...form.register('general.nombreComercial')} />
                </Field>
                <Field label="RUC" required error={form.formState.errors.general?.ruc?.message}>
                  <Input disabled={!canEdit} maxLength={11} {...form.register('general.ruc')} />
                </Field>
                <Field label="Dirección fiscal" required error={form.formState.errors.general?.direccionFiscal?.message}>
                  <Input disabled={!canEdit} {...form.register('general.direccionFiscal')} />
                </Field>
                <Field label="Dirección comercial" error={form.formState.errors.general?.direccionComercial?.message}>
                  <Input disabled={!canEdit} {...form.register('general.direccionComercial')} />
                </Field>
                <Field label="Teléfono">
                  <Input disabled={!canEdit} {...form.register('general.telefono')} />
                </Field>
                <Field label="Email">
                  <Input disabled={!canEdit} type="email" {...form.register('general.email')} />
                </Field>
                <Field label="Región/Ciudad">
                  <div className="grid grid-cols-2 gap-2">
                    <Input disabled={!canEdit} placeholder="Región" {...form.register('general.region')} />
                    <Input disabled={!canEdit} placeholder="Ciudad" {...form.register('general.ciudad')} />
                  </div>
                </Field>
              </div>
            </Card>
          )}

          {active === 'sunat' && (
            <div className="space-y-4">
              <Card title="Datos Tributarios SUNAT" description="Compatibles con XML y envíos electrónicos">
                <div className="grid gap-4 md:grid-cols-2">
                  <Field label="Régimen" required>
                    <Input disabled={!canEdit} {...form.register('sunat.regimen')} />
                  </Field>
                  <Field label="Tipo de contribuyente">
                    <Input disabled={!canEdit} {...form.register('sunat.tipoContribuyente')} />
                  </Field>
                  <Field label="Afectación IGV" required>
                    <Input disabled={!canEdit} {...form.register('sunat.afectacionIgv')} />
                  </Field>
                  <Field label="Ambiente" required>
                    <select
                      className="rounded-md border border-slate-200 px-2 py-2"
                      disabled={
                        !canEdit ||
                        (form.watch('sunat.ambiente') === 'PRODUCCION' && (!sunatStatus.hasSolCredentials || !sunatStatus.hasCertificate || sunatStatus.certificateStatus !== 'ACTIVE'))
                      }
                      {...form.register('sunat.ambiente')}
                    >
                      <option value="PRUEBAS">PRUEBAS</option>
                      <option value="PRODUCCION" disabled={!sunatStatus.hasSolCredentials || !sunatStatus.hasCertificate || sunatStatus.certificateStatus !== 'ACTIVE'}>
                        PRODUCCIÓN
                      </option>
                    </select>
                  </Field>
                  <div className="flex items-center gap-3 text-sm">
                    <ShieldCheck className="h-4 w-4 text-emerald-600" />
                    <span className="text-slate-700">SOL: {sunatStatus.hasSolCredentials ? '✅ Configurado' : '❌ No configurado'}</span>
                  </div>
                  <div className="flex items-center gap-3 text-sm">
                    <Shield className="h-4 w-4 text-blue-600" />
                    <span className="text-slate-700">Certificado: {sunatStatus.hasCertificate ? (sunatStatus.certificateStatus === 'ACTIVE' ? '✅ Vigente' : '⚠️ No vigente') : '❌ No configurado'}</span>
                  </div>
                </div>
              </Card>

              <Card title="Credenciales SUNAT (SOL)" description="Se almacenan cifradas. No se muestran valores reales.">
                <div className="grid gap-4 md:grid-cols-2">
                  <Field label="Usuario SOL" required>
                    <Input
                      disabled={!canEdit}
                      value={sunatUser}
                      onChange={(e) => setSunatUser(e.target.value)}
                      placeholder="MODDATOS"
                    />
                  </Field>
                  <Field label="Clave SOL" required>
                    <div className="flex items-center gap-2">
                      <Input
                        type={sunatPasswordVisible ? 'text' : 'password'}
                        disabled={!canEdit}
                        value={sunatPassword}
                        onChange={(e) => setSunatPassword(e.target.value)}
                        placeholder="••••••"
                      />
                      <Button type="button" variant="outline" disabled={!canEdit} onClick={() => setSunatPasswordVisible(!sunatPasswordVisible)}>
                        {sunatPasswordVisible ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                      </Button>
                    </div>
                  </Field>
                  <div className="flex items-center gap-2 text-sm">
                    <ShieldCheck className="h-4 w-4 text-emerald-600" />
                    <span>Estado: {sunatStatus.hasSolCredentials ? '✅ Configurado' : '❌ No configurado'}</span>
                  </div>
                  <div className="flex justify-end">
                    <Button
                      type="button"
                      onClick={async () => {
                        if (!canEdit) return;
                        setSavingCreds(true);
                        try {
                          await saveSunatCredentials({ sunatUser, sunatPassword });
                          setSunatStatus((prev) => ({ ...prev, hasSolCredentials: true }));
                          setSunatPassword('');
                          toast.success('Credenciales guardadas');
                        } catch (error: any) {
                          toast.error(error?.response?.data?.message || 'No se pudieron guardar las credenciales');
                        } finally {
                          setSavingCreds(false);
                        }
                      }}
                      disabled={!canEdit || savingCreds}
                    >
                      {savingCreds ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Save className="mr-2 h-4 w-4" />} Guardar credenciales
                    </Button>
                  </div>
                </div>
              </Card>

              <Card title="Certificado Digital" description="Sube archivo .pfx/.p12 y contraseña. Se guarda cifrado.">
                <div className="space-y-3">
                  <div className="grid gap-4 md:grid-cols-2">
                    <Field label="Archivo .pfx / .p12" required>
                      <Input
                        type="file"
                        accept=".pfx,.p12"
                        disabled={!canEdit}
                        onChange={(e) => setCertificateFile(e.target.files?.[0] ?? null)}
                      />
                    </Field>
                    <Field label="Contraseña" required>
                      <Input
                        type="password"
                        disabled={!canEdit}
                        value={certificatePassword}
                        onChange={(e) => setCertificatePassword(e.target.value)}
                        placeholder="••••••"
                      />
                    </Field>
                  </div>
                  <div className="grid gap-2 text-sm md:grid-cols-2">
                    <div className="rounded-lg border border-slate-100 bg-slate-50 p-3">
                      <p className="font-semibold text-slate-700">Estado</p>
                      <p className="text-slate-600">
                        {sunatStatus.hasCertificate ? (sunatStatus.certificateStatus === 'ACTIVE' ? '✅ Vigente' : '⚠️ No vigente') : '❌ No configurado'}
                      </p>
                    </div>
                    <div className="rounded-lg border border-slate-100 bg-slate-50 p-3">
                      <p className="font-semibold text-slate-700">Vigencia</p>
                      <p className="text-slate-600">
                        {sunatStatus.certificateValidFrom ? `${sunatStatus.certificateValidFrom} → ${sunatStatus.certificateValidUntil ?? '—'}` : 'No disponible'}
                      </p>
                    </div>
                  </div>
                  <div className="flex justify-end">
                    <Button
                      type="button"
                      variant="outline"
                      disabled={!canEdit || uploadingCert || !certificateFile || !certificatePassword}
                      onClick={async () => {
                        if (!certificateFile || !certificatePassword) return;
                        setUploadingCert(true);
                        const fd = new FormData();
                        fd.append('certificate', certificateFile);
                        fd.append('password', certificatePassword);
                        try {
                          const response = await uploadSunatCertificate(fd);
                          setSunatStatus((prev) => ({
                            ...prev,
                            hasCertificate: response.hasCertificate ?? true,
                            certificateStatus: (response.certificateStatus as 'ACTIVE' | 'EXPIRED' | 'REVOKED' | null) ?? 'ACTIVE',
                            certificateValidFrom: response.certificateValidFrom ?? prev.certificateValidFrom,
                            certificateValidUntil: response.certificateValidUntil ?? prev.certificateValidUntil,
                          }));
                          setCertificateFile(null);
                          setCertificatePassword('');
                          toast.success('Certificado actualizado');
                        } catch (error: any) {
                          toast.error(error?.response?.data?.message || 'No se pudo cargar el certificado');
                        } finally {
                          setUploadingCert(false);
                        }
                      }}
                    >
                      {uploadingCert ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Save className="mr-2 h-4 w-4" />} Reemplazar certificado
                    </Button>
                  </div>
                </div>
              </Card>

              <Card title="Probar conexión SUNAT" description="Verifica credenciales y firma (simulada)">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2 text-sm text-slate-700">
                    <PlugZap className="h-4 w-4 text-blue-600" />
                    <span>Ejecuta validación remota</span>
                  </div>
                  <Button
                    type="button"
                    variant="outline"
                    disabled={testingSunat || !canEditFromRole}
                    onClick={async () => {
                      setTestingSunat(true);
                      try {
                        const res = await testSunatConnection();
                        toast.success(res.message || 'Conexión OK');
                      } catch (error: any) {
                        toast.error(error?.response?.data?.message || 'Error en la prueba');
                      } finally {
                        setTestingSunat(false);
                      }
                    }}
                  >
                    {testingSunat ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Shield className="mr-2 h-4 w-4" />} Probar conexión
                  </Button>
                </div>
              </Card>
            </div>
          )}

          {active === 'documentos' && (
            <Card title="Series y numeración" description="Control de facturación, boletas y guías">
              {renderSeriesControls()}
            </Card>
          )}

          {active === 'monedas' && (
            <Card title="Monedas e Impuestos" description="Moneda base, secundarias y redondeos">
              {renderMonedas()}
            </Card>
          )}

          {active === 'almacenes' && (
            <Card title="Almacenes y Operación" description="Stock, lotes y series">
              {renderAlmacenes()}
            </Card>
          )}

          {active === 'cajaBancos' && (
            <Card title="Caja y Bancos" description="Cajas, cuentas y liquidaciones">
              {renderCajaBancos()}
            </Card>
          )}

          {active === 'contabilidad' && (
            <Card title="Contabilidad" description="Plan de cuentas y periodos">
              <div className="grid gap-4 md:grid-cols-2">
                <Field label="Plan contable">
                  <Input disabled={!canEdit} {...form.register('contabilidad.planContable')} />
                </Field>
                <Field label="Cuenta ventas">
                  <Input disabled={!canEdit} {...form.register('contabilidad.cuentaVentas')} />
                </Field>
                <Field label="Cuenta compras">
                  <Input disabled={!canEdit} {...form.register('contabilidad.cuentaCompras')} />
                </Field>
                <Field label="Cuenta IGV">
                  <Input disabled={!canEdit} {...form.register('contabilidad.cuentaIgv')} />
                </Field>
                <Field label="Cuenta caja">
                  <Input disabled={!canEdit} {...form.register('contabilidad.cuentaCaja')} />
                </Field>
                <Field label="Cuenta bancos">
                  <Input disabled={!canEdit} {...form.register('contabilidad.cuentaBancos')} />
                </Field>
                <label className="flex items-center gap-2 text-sm">
                  <input type="checkbox" disabled={!canEdit} {...form.register('contabilidad.contabilizacionAutomatica')} /> Contabilización automática
                </label>
                <label className="flex items-center gap-2 text-sm">
                  <input type="checkbox" disabled={!canEdit} {...form.register('contabilidad.centrosCostoObligatorios')} /> Centros de costo obligatorios
                </label>
              </div>
            </Card>
          )}

          {active === 'seguridad' && (
            <Card title="Usuarios y Seguridad" description="Roles y privilegios">
              <div className="space-y-3">
                <label className="flex items-center gap-2 text-sm">
                  <input type="checkbox" disabled={!canEdit} {...form.register('seguridad.privilegios.precios')} /> Restringir precios
                </label>
                <label className="flex items-center gap-2 text-sm">
                  <input type="checkbox" disabled={!canEdit} {...form.register('seguridad.privilegios.reportes')} /> Reportes sensibles
                </label>
                <label className="flex items-center gap-2 text-sm">
                  <input type="checkbox" disabled={!canEdit} {...form.register('seguridad.privilegios.eliminaciones')} /> Bloquear eliminaciones
                </label>
              </div>
            </Card>
          )}

          {active === 'preferencias' && (
            <Card title="Preferencias del Sistema" description="Idioma, zona horaria y formatos">
              <div className="grid gap-4 md:grid-cols-2">
                <Field label="Idioma">
                  <Input disabled={!canEdit} {...form.register('preferencias.idioma')} />
                </Field>
                <Field label="Zona horaria">
                  <Input disabled={!canEdit} {...form.register('preferencias.zonaHoraria')} />
                </Field>
                <Field label="Formato fecha">
                  <Input disabled={!canEdit} {...form.register('preferencias.formatoFecha')} />
                </Field>
                <Field label="Decimales">
                  <Input disabled={!canEdit} type="number" {...form.register('preferencias.decimales', { valueAsNumber: true })} />
                </Field>
              </div>
            </Card>
          )}

          {active === 'integraciones' && (
            <Card title="Integraciones" description="OSE, portal documentos, SMTP, backups">
              {renderIntegraciones()}
            </Card>
          )}

          <div className="flex items-center justify-end gap-3 border-t pt-4">
            <Button type="button" variant="outline" disabled={!canEdit || saving} onClick={() => form.reset(form.getValues())}>
              Descartar cambios
            </Button>
            <Button type="submit" disabled={!canEdit || saving}>
              {saving ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Save className="mr-2 h-4 w-4" />} Guardar
            </Button>
          </div>
        </form>
      </main>
    </div>
  );
}

function Card({ title, description, children }: { title: string; description?: string; children: React.ReactNode }) {
  return (
    <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
      <div className="mb-4">
        <p className="text-sm font-semibold text-slate-900">{title}</p>
        {description && <p className="text-xs text-slate-500">{description}</p>}
      </div>
      <div className="space-y-3">{children}</div>
    </div>
  );
}

function Field({ label, required, error, children }: { label: string; required?: boolean; error?: string; children: React.ReactNode }) {
  return (
    <div className="space-y-1">
      <Label className="text-sm font-semibold text-slate-700">
        {label} {required && <span className="text-red-500">*</span>}
      </Label>
      {children}
      {error && (
        <p className="flex items-center gap-1 text-xs text-red-600">
          <AlertCircle className="h-3 w-3" /> {error}
        </p>
      )}
    </div>
  );
}
