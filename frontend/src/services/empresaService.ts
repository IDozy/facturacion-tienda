// src/services/empresaService.ts
import type { Empresa } from "../types/Empresa";
import api from "./api";

// Función para obtener el ID del usuario autenticado
const getUserId = async (): Promise<number> => {
  try {
    const response = await api.get('/user');
    const user = response.data.data || response.data;
    if (!user.id) {
      throw new Error('Usuario no encontrado');
    }
    return user.id;
  } catch (error) {
    throw new Error('Error al obtener información del usuario');
  }
};

// Función para obtener la empresa del usuario autenticado
export const getEmpresa = async (): Promise<Empresa> => {
  try {
    // Opción 1: Obtener empresa directamente del usuario
    const response = await api.get("/user", {
      params: {
        include: 'empresa' // Incluir la relación empresa
      }
    });

    const user = response.data.data || response.data;

    if (!user.empresa) {
      throw new Error('Usuario sin empresa asignada');
    }

    return user.empresa;
  } catch (error: any) {
    // Fallback: intentar con el endpoint original
    try {
      const res = await api.get("/configuraciones-empresa");
      return res.data.data;
    } catch (fallbackError: any) {
      throw new Error(error.response?.data?.message || 'Error al obtener datos de la empresa');
    }
  }
};

export async function getEmpresaById(id: number) {
  const { data } = await api.get(`/empresas/${id}`);
  return data.data; // según la estructura de tu backend
}

// Alternativa: Función específica que usa el ID del usuario
export const getEmpresaByUserId = async (): Promise<Empresa> => {
  try {
    const userId = await getUserId();
    const response = await api.get(`/users/${userId}`);
    console.log(response.data.data, "datos de la empresa");
    return response.data.data;

  } catch (error: any) {
    throw new Error(error.response?.data?.message || 'Error al obtener empresa del usuario');
  }
};

// Función actualizada para manejar tanto FormData como objetos
export const updateEmpresa = async (id: number, data: FormData | Partial<Empresa>): Promise<Empresa> => {
  try {
    console.log('📤 ID de empresa:', id);
    console.log('📤 Tipo de datos:', data instanceof FormData ? 'FormData' : 'Objeto');

    // Si es FormData, mostrar su contenido
    if (data instanceof FormData) {
      console.log('📤 Contenido del FormData:');
      for (const [key, value] of data.entries()) {
        console.log(`  ${key}:`, value);
      }
    } else {
      console.log('📤 Datos del objeto:', data);
    }

    const config = data instanceof FormData ? {
      headers: {
        'Content-Type': 'multipart/form-data',
      }
    } : {};

    const res = await api.post(`/empresas/${id}?_method=PUT`, data, config);
    console.log("✅ Empresa actualizada:", res.data.data);
    return res.data.data || res.data;

  } catch (error: any) {
    console.error("❌ Error al actualizar la empresa:", error);

    if (error.response) {
      console.error("Response data:", error.response.data);
      console.error("Response status:", error.response.status);
    }

    const message =
      error.response?.data?.message ||
      "Error al actualizar la información de la empresa.";

    throw new Error(message);
  }
};

// Función adicional para obtener usuario con empresa
export const getUserWithEmpresa = async () => {
  try {
    const response = await api.get("/user", {
      params: {
        include: 'empresa,roles'
      }
    });
    console.log(response.data, "datos del usuario con empresa");
    return response.data.data || response.data;
  } catch (error: any) {
    throw new Error(error.response?.data?.message || 'Error al obtener usuario con empresa');
  }
};