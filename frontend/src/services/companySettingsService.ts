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
