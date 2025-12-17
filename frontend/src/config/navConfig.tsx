import {
  Home,
  Settings,
  ShoppingCart,
  Package,
  Building2,
  UserCog,
  Warehouse,
  Calculator,
  FileText,
} from 'lucide-react';
import type { ComponentType } from 'react';
import Dashboard from '../pages/Dashboard';
import ProductosPage from '../pages/inventario/Productos';
import EmpresaPage from '../pages/configuracion/Empresa';
import { UsuariosPage } from '../pages/configuracion/Usuarios';
import ParametrosContablesPage from '../pages/configuracion/ParametrosContables';
import AlmacenesPage from '../pages/configuracion/Almacen';

export interface NavItemConfig {
  key: string;
  label: string;
  icon: ComponentType<any>;
  path?: string;
  element?: JSX.Element;
  roles?: string[];
  items?: NavItemConfig[];
}

export const navConfig: NavItemConfig[] = [
  {
    key: 'dashboard',
    label: 'Dashboard',
    icon: Home,
    path: '/dashboard',
    element: <Dashboard />,
  },
  {
    key: 'ventas',
    label: 'Ventas',
    icon: ShoppingCart,
    roles: ['admin', 'administrador', 'vendedor', 'cajero'],
    items: [
      {
        key: 'ventas-productos',
        label: 'Productos',
        icon: Package,
        path: '/ventas/productos',
        element: <ProductosPage />,
        roles: ['admin', 'administrador', 'vendedor'],
      },
    ],
  },
  {
    key: 'inventario',
    label: 'Inventario',
    icon: Package,
    roles: ['admin', 'administrador', 'vendedor'],
    items: [
      {
        key: 'inventario-productos',
        label: 'Productos',
        icon: FileText,
        path: '/inventario/productos',
        element: <ProductosPage />,
        roles: ['admin', 'administrador', 'vendedor'],
      },
      {
        key: 'inventario-almacenes',
        label: 'Almacenes',
        icon: Warehouse,
        path: '/configuracion/almacenes',
        element: <AlmacenesPage />,
        roles: ['admin', 'administrador'],
      },
    ],
  },
  {
    key: 'configuracion',
    label: 'Configuración',
    icon: Settings,
    roles: ['admin', 'administrador'],
    items: [
      {
        key: 'config-empresa',
        label: 'Empresa',
        icon: Building2,
        path: '/dashboard/configuracion/empresa',
        element: <EmpresaPage />,
        roles: ['admin', 'administrador'],
      },
      {
        key: 'config-usuarios',
        label: 'Usuarios',
        icon: UserCog,
        path: '/configuracion/usuarios',
        element: <UsuariosPage />,
        roles: ['admin', 'administrador'],
      },
      {
        key: 'config-parametros',
        label: 'Parámetros contables',
        icon: Calculator,
        path: '/configuracion/parametroscontables',
        element: <ParametrosContablesPage />,
        roles: ['admin', 'administrador'],
      },
    ],
  },
];

export const flattenNavItems = (items: NavItemConfig[]): NavItemConfig[] =>
  items.flatMap((item) =>
    item.items && item.items.length > 0 ? [item, ...flattenNavItems(item.items)] : [item]
  );
