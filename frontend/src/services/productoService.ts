import api from './api';
import type { Producto, ProductoFilters, ProductoResumen } from '@/types/Producto';
import type { PaginatedResponse, ApiResponse } from '@/types/Almacen';

class ProductoService {
  private basePath = '/inventario/productos';

  async getProductos(filters?: ProductoFilters): Promise<PaginatedResponse<Producto>> {
    const params = new URLSearchParams();

    if (filters) {
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
          params.append(key, String(value));
        }
      });
    }

    const response = await api.get(`${this.basePath}?${params.toString()}`);
    return response.data;
  }

  async getResumen(): Promise<ProductoResumen> {
    const response = await api.get('/inventario/productos-resumen');
    return response.data;
  }

  async toggleEstado(id: number): Promise<ApiResponse<Producto>> {
    const response = await api.post(`${this.basePath}/${id}/toggle-estado`, {
      _method: 'PATCH',
    });
    return response.data;
  }
}

export default new ProductoService();
