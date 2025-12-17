import type { Empresa } from "../types/Empresa";
import api from "./api";

const unwrapData = <T>(response: any): T => response.data?.data ?? response.data ?? response;

export const getEmpresaActual = async (): Promise<Empresa> => {
  const response = await api.get("/empresa/me");
  return unwrapData<Empresa>(response);
};

export const actualizarEmpresaActual = async (
  payload: Partial<Empresa>,
): Promise<Empresa> => {
  const response = await api.put("/empresa/me", payload);
  return unwrapData<Empresa>(response);
};

export const subirLogoEmpresa = async (file: File): Promise<{ logoUrl: string; empresa: Empresa }> => {
  const formData = new FormData();
  formData.append("logo", file);

  const response = await api.post("/empresa/me/logo", formData, {
    headers: { "Content-Type": "multipart/form-data" },
  });

  const data = unwrapData<{ logoUrl: string; empresa: Empresa }>(response);

  return {
    logoUrl: (data as any).logoUrl ?? (data as any).logo_url ?? "",
    empresa: (data as any).empresa ?? (data as any),
  };
};
