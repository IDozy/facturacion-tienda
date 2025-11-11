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


// src/services/empresaService.ts
// En tu empresaService.ts, en la función updateEmpresa
export const updateEmpresa = async (data: Partial<Empresa>): Promise<Empresa> => {
  try {
    if (!data.id) {
      throw new Error("El ID de la empresa es obligatorio para actualizarla.");
    }

    // 🔥 AGREGA ESTE LOG
    console.log('📤 Datos que se van a enviar:', data);
    console.log('📤 RUC específico:', data.ruc);
    console.log('📤 Tipo de RUC:', typeof data.ruc);

    const res = await api.put(`/empresas/${data.id}`, data);
    console.log("Empresa actualizada:", res.data.data);
    return res.data.data;

  } catch (error: any) {
    console.error("Error al actualizar la empresa:", error);

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