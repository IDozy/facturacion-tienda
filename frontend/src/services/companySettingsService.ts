import api from './api';
import type { CompanyConfiguration } from '../types/CompanyConfiguration';

const unwrap = <T>(response: { data?: unknown }): T => {
  // @ts-expect-error: axios responses include data shape
  return response.data?.data ?? response.data ?? response;
};

export async function fetchCompanyConfiguration(): Promise<CompanyConfiguration> {
  const response = await api.get('/company-settings/me');
  return unwrap<CompanyConfiguration>(response);
}

export async function saveCompanyConfiguration(payload: CompanyConfiguration): Promise<CompanyConfiguration> {
  const response = await api.put('/company-settings/me', payload);
  return unwrap<CompanyConfiguration>(response);
}

export async function fetchSunatStatus() {
  const response = await api.get('/company/sunat/status');
  return unwrap<{
    hasSolCredentials: boolean;
    hasCertificate: boolean;
    certificateStatus: string | null;
    certificateValidFrom: string | null;
    certificateValidUntil: string | null;
    certificateIssuer?: string | null;
  }>(response);
}

export async function saveSunatCredentials(payload: { sunatUser: string; sunatPassword: string }) {
  const response = await api.post('/company/sunat/credentials', payload);
  return unwrap<{ message: string; hasSolCredentials: boolean }>(response);
}

export async function uploadSunatCertificate(payload: FormData) {
  const response = await api.post('/company/sunat/certificate', payload, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });
  return unwrap<{
    message: string;
    hasCertificate: boolean;
    certificateStatus?: string | null;
    certificateValidFrom?: string | null;
    certificateValidUntil?: string | null;
  }>(response);
}

export async function testSunatConnection() {
  const response = await api.post('/company/sunat/test');
  return unwrap<{ message: string }>(response);
}
