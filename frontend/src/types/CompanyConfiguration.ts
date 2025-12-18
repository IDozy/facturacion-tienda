export type DocumentSeriesType = 'FACTURA' | 'BOLETA' | 'NC' | 'ND' | 'GUIA';

export interface CompanyGeneral {
  razonSocial: string;
  nombreComercial?: string;
  ruc: string;
  direccionFiscal: string;
  direccionComercial?: string;
  telefono?: string;
  email?: string;
  logoUrl?: string;
  region?: string;
  ciudad?: string;
  pais?: string;
}

export interface SunatSettings {
  regimen: string;
  tipoContribuyente?: string;
  afectacionIgv: string;
  certificadoUrl?: string;
  certificadoEstado?: string;
  certificadoVigenciaDesde?: string | null;
  certificadoVigenciaHasta?: string | null;
  ambiente: 'PRUEBAS' | 'PRODUCCION';
  hasSolCredentials?: boolean;
  hasCertificate?: boolean;
  certificateStatus?: 'ACTIVE' | 'EXPIRED' | 'REVOKED' | null;
  certificateValidFrom?: string | null;
  certificateValidUntil?: string | null;
}

export interface DocumentSeriesConfig {
  tipo: DocumentSeriesType;
  serie: string;
  correlativoInicial: number;
  correlativoActual: number;
  automatico: boolean;
  activo: boolean;
}

export interface CurrencyConfig {
  code: string;
  name: string;
  isBase?: boolean;
  preciosIncluyenIgv: boolean;
  igvRate: number;
  redondeo: boolean;
  tipoCambioAutomatico: boolean;
}

export interface ExchangeRateConfig {
  currencyCode: string;
  fecha: string;
  compra?: number;
  venta?: number;
  fuente?: string;
  automatico?: boolean;
}

export interface WarehouseConfig {
  nombre: string;
  principal: boolean;
  stockNegativo: boolean;
  manejaSeries: boolean;
  manejaLotes: boolean;
  codigoBarras?: string;
  activo: boolean;
}

export interface CashboxConfig {
  nombre: string;
  moneda: string;
  porDefecto: boolean;
  manejaCheques: boolean;
  liquidacionDiaria: boolean;
  flujoAutomatico: boolean;
}

export interface BankAccountConfig {
  banco: string;
  numero: string;
  moneda: string;
  esPrincipal: boolean;
  manejaCheques: boolean;
}

export interface AccountingConfig {
  planContable?: string;
  cuentaVentas?: string;
  cuentaCompras?: string;
  cuentaIgv?: string;
  cuentaCaja?: string;
  cuentaBancos?: string;
  contabilizacionAutomatica: boolean;
  centrosCostoObligatorios: boolean;
  periodos?: Array<{ nombre: string; estado: 'ABIERTO' | 'CERRADO' }>;
}

export interface SecurityConfig {
  roles?: Array<{ nombre: string; editable?: boolean }>;
  privilegios?: {
    precios: boolean;
    reportes: boolean;
    eliminaciones: boolean;
  };
}

export interface PreferenceConfig {
  idioma: string;
  zonaHoraria: string;
  formatoFecha: string;
  decimales: number;
  alertas?: Array<{ clave: string; activo: boolean }>;
}

export interface IntegrationConfig {
  tipo: string;
  params?: Record<string, unknown>;
  activo: boolean;
}

export interface CompanyConfiguration {
  general: CompanyGeneral;
  sunat: SunatSettings;
  documentos: {
    series: DocumentSeriesConfig[];
  };
  monedas: {
    monedaBase: CurrencyConfig;
    secundarias: CurrencyConfig[];
    exchangeRates?: ExchangeRateConfig[];
  };
  almacenes: WarehouseConfig[];
  cajaBancos: {
    cajas: CashboxConfig[];
    bancos: BankAccountConfig[];
  };
  contabilidad: AccountingConfig;
  seguridad: SecurityConfig;
  preferencias: PreferenceConfig;
  integraciones: IntegrationConfig[];
  completion?: Record<string, boolean>;
  canEdit?: boolean;
  updatedAt?: string;
}
