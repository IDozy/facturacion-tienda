// services/almacen.service.ts

import api from './api'; // Asume que tienes configurado axios con la baseURL
import type {
    Almacen,
    AlmacenDetalle,
    AlmacenFormData,
    AlmacenFilters,
    PaginatedResponse,
    ApiResponse,
    EstadisticasAlmacen,
    AlmacenProducto,
    MovimientoStock,
    ValorizacionAlmacen,
    ComparacionAlmacen,
    VerificarStockResponse,
} from '@/types/Almacen';

class AlmacenService {
    private basePath = '/inventario/almacenes';

    /**
     * Obtener empresa_id del usuario autenticado
     * Puedes ajustar esto según cómo manejes la autenticación en tu app
     */
    private getEmpresaId(): number | null {
        // Opción 1: Desde localStorage (si guardas el user completo)
        const userStr = localStorage.getItem('user');
        if (userStr) {
            try {
                const user = JSON.parse(userStr);
                return user.empresa_id || null;
            } catch (e) {
                console.error('Error parsing user from localStorage', e);
            }
        }

        // Opción 2: Desde un store (Zustand, Redux, Context, etc)
        // return useAuthStore.getState().user?.empresa_id || null;

        return null;
    }

    /**
     * Obtener listado de almacenes con filtros y paginación
     */
    async getAlmacenes(
        filters?: AlmacenFilters
    ): Promise<PaginatedResponse<Almacen> | { data: Almacen[] }> {
        const params = new URLSearchParams();

        if (filters) {
            Object.entries(filters).forEach(([key, value]) => {
                if (value !== undefined && value !== null) {
                    params.append(key, String(value));
                }
            });
        }

        const response = await api.get(`${this.basePath}?${params.toString()}`);
        return response.data;
    }

    /**
     * ✅ NUEVO: Obtener almacenes filtrados por empresa_id del usuario
     */
    async getAlmacenesPorEmpresa(
        empresaId?: number,
        filters?: AlmacenFilters
    ): Promise<PaginatedResponse<Almacen> | { data: Almacen[] }> {
        const empresa = empresaId || this.getEmpresaId();

        if (!empresa) {
            throw new Error('No se pudo obtener el empresa_id del usuario');
        }

        const params = new URLSearchParams();

        // Agregar empresa_id como filtro
        params.append('empresa_id', String(empresa));

        if (filters) {
            Object.entries(filters).forEach(([key, value]) => {
                if (value !== undefined && value !== null) {
                    params.append(key, String(value));
                }
            });
        }

        const response = await api.get(`${this.basePath}?${params.toString()}`);
        return response.data;
    }

    /**
     * Obtener todos los almacenes sin paginación (para selects)
     */
    async getAllAlmacenes(): Promise<Almacen[]> {
        const response = await api.get(`${this.basePath}?all=true`);
        return response.data.data;
    }

    /**
     * ✅ NUEVO: Obtener todos los almacenes de una empresa sin paginación
     */
    async getAllAlmacenesPorEmpresa(empresaId?: number): Promise<Almacen[]> {
        const empresa = empresaId || this.getEmpresaId();

        if (!empresa) {
            throw new Error('No se pudo obtener el empresa_id del usuario');
        }

        const response = await api.get(`${this.basePath}?all=true&empresa_id=${empresa}`);
        return response.data.data;
    }

    /**
     * Obtener almacén por ID con detalles
     */
    async getAlmacen(id: number): Promise<AlmacenDetalle> {
        const response = await api.get(`${this.basePath}/${id}`);
        return response.data.data;
    }

    /**
     * Crear nuevo almacén
     */
    async createAlmacen(data: AlmacenFormData): Promise<ApiResponse<Almacen>> {
        const response = await api.post(this.basePath, data);
        return response.data;
    }

    /**
  * Actualizar almacén existente
  */
    async updateAlmacen(
        id: number,
        data: Partial<AlmacenFormData>
    ): Promise<ApiResponse<Almacen>> {
        // Laravel workaround: usar POST con _method=PUT
        const response = await api.post(`${this.basePath}/${id}`, {
            ...data,
            _method: 'PUT'
        });
        return response.data;
    }

    /**
     * Eliminar almacén
     */
    async deleteAlmacen(id: number): Promise<ApiResponse<null>> {
        const response = await api.delete(`${this.basePath}/${id}`);
        return response.data;
    }

    /**
     * Cambiar estado activo/inactivo del almacén
     */
    async toggleEstado(id: number): Promise<ApiResponse<Almacen>> {
        const response = await api.post(`/inventario/almacenes/${id}/toggle-estado`, {
            _method: 'PATCH',
        });
        return response.data;
    }

    /**
     * Obtener productos del almacén
     */
    async getProductos(
        id: number,
        filters?: {
            con_stock?: boolean;
            bajo_stock?: boolean;
            per_page?: number;
            page?: number;
        }
    ): Promise<PaginatedResponse<any>> {
        const params = new URLSearchParams();

        if (filters) {
            Object.entries(filters).forEach(([key, value]) => {
                if (value !== undefined && value !== null) {
                    params.append(key, String(value));
                }
            });
        }

        const response = await api.get(
            `${this.basePath}/${id}/productos?${params.toString()}`
        );
        return response.data;
    }

    /**
     * Verificar stock de un producto en el almacén
     */
    async verificarStock(
        id: number,
        productoId: number,
        cantidad: number
    ): Promise<VerificarStockResponse> {
        const response = await api.post(`${this.basePath}/${id}/verificar-stock`, {
            producto_id: productoId,
            cantidad: cantidad,
        });
        return response.data;
    }

    /**
     * Obtener productos con bajo stock
     */
    async getProductosBajoStock(
        id: number
    ): Promise<{ data: AlmacenProducto[]; count: number }> {
        const response = await api.get(`${this.basePath}/${id}/productos-bajo-stock`);
        return response.data;
    }

    /**
     * Obtener movimientos de stock del almacén
     */
    async getMovimientos(
        id: number,
        filters?: {
            tipo?: string;
            fecha_desde?: string;
            fecha_hasta?: string;
            per_page?: number;
            page?: number;
        }
    ): Promise<PaginatedResponse<MovimientoStock>> {
        const params = new URLSearchParams();

        if (filters) {
            Object.entries(filters).forEach(([key, value]) => {
                if (value !== undefined && value !== null) {
                    params.append(key, String(value));
                }
            });
        }

        const response = await api.get(
            `${this.basePath}/${id}/movimientos?${params.toString()}`
        );
        return response.data;
    }

    /**
     * Obtener estadísticas del almacén
     */
    async getEstadisticas(id: number): Promise<EstadisticasAlmacen> {
        const response = await api.get(`${this.basePath}/${id}/estadisticas`);
        return response.data;
    }

    /**
     * Obtener valorización del inventario
     */
    async getValorizacion(id: number): Promise<ValorizacionAlmacen> {
        const response = await api.get(`${this.basePath}/${id}/valorizacion`);
        return response.data;
    }

    /**
     * Comparar múltiples almacenes
     */
    async compararAlmacenes(
        almacenIds: number[]
    ): Promise<{ data: ComparacionAlmacen[] }> {
        const response = await api.post(`${this.basePath}/comparar`, {
            almacen_ids: almacenIds,
        });
        return response.data;
    }

    /**
     * Limpiar caché del almacén
     */
    async limpiarCache(id?: number): Promise<ApiResponse<null>> {
        const url = id
            ? `${this.basePath}/limpiar-cache/${id}`
            : `${this.basePath}/limpiar-cache`;
        const response = await api.post(url);
        return response.data;
    }

    /**
     * Importar múltiples almacenes
     */
    async importarAlmacenes(
        almacenes: AlmacenFormData[]
    ): Promise<{
        message: string;
        importados: number;
        errores: Array<{ index: number; nombre: string; error: string }>;
    }> {
        const response = await api.post(`${this.basePath}/importar`, {
            almacenes: almacenes,
        });
        return response.data;
    }
}

export default new AlmacenService();