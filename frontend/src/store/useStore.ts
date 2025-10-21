import { create } from 'zustand';
import { Producto, Cliente } from '../types';

interface CartItem extends Producto {
  cantidad: number;
  descuento: number;
}

interface Store {
  // Carrito de compras
  cart: CartItem[];
  addToCart: (producto: Producto) => void;
  removeFromCart: (id: number) => void;
  updateQuantity: (id: number, cantidad: number) => void;
  updateDescuento: (id: number, descuento: number) => void;
  clearCart: () => void;
  
  // Cliente seleccionado
  selectedCliente: Cliente | null;
  setSelectedCliente: (cliente: Cliente | null) => void;
  
  // Cálculos
  getSubtotal: () => number;
  getDescuentoTotal: () => number;
  getIGV: () => number;
  getTotal: () => number;
}

export const useStore = create<Store>((set, get) => ({
  cart: [],
  selectedCliente: null,

  addToCart: (producto) => {
    const exists = get().cart.find(item => item.id === producto.id);
    
    if (exists) {
      set({
        cart: get().cart.map(item =>
          item.id === producto.id
            ? { ...item, cantidad: item.cantidad + 1 }
            : item
        ),
      });
    } else {
      set({
        cart: [...get().cart, { ...producto, cantidad: 1, descuento: 0 }],
      });
    }
  },

  removeFromCart: (id) => {
    set({
      cart: get().cart.filter(item => item.id !== id),
    });
  },

  updateQuantity: (id, cantidad) => {
    set({
      cart: get().cart.map(item =>
        item.id === id ? { ...item, cantidad } : item
      ),
    });
  },

  updateDescuento: (id, descuento) => {
    set({
      cart: get().cart.map(item =>
        item.id === id ? { ...item, descuento } : item
      ),
    });
  },

  clearCart: () => {
    set({ cart: [], selectedCliente: null });
  },

  setSelectedCliente: (cliente) => {
    set({ selectedCliente: cliente });
  },

  getSubtotal: () => {
    return get().cart.reduce((total, item) => {
      const subtotal = item.cantidad * item.precio_unitario - item.descuento;
      return total + subtotal;
    }, 0);
  },

  getDescuentoTotal: () => {
    return get().cart.reduce((total, item) => total + item.descuento, 0);
  },

  getIGV: () => {
    const subtotal = get().getSubtotal();
    return subtotal * 0.18; // 18% IGV
  },

  getTotal: () => {
    return get().getSubtotal() + get().getIGV();
  },
}));
