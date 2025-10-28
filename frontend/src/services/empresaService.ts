// src/services/empresaService.ts
import type { Empresa } from "../types/Empresa";
import api from "./api";


export const getEmpresa = async (): Promise<Empresa> => {
  const res = await api.get("/empresa");
  return res.data.data;
};

export const updateEmpresa = async (data: Partial<Empresa>): Promise<Empresa> => {
  const res = await api.put("/empresa", data);
  return res.data.data;
};
