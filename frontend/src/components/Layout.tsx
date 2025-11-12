// Layout.tsx

import { useState, useEffect } from 'react'
import type { ReactNode } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { motion, AnimatePresence } from 'framer-motion';
import {
  Home,
  Package,
  Users,
  FileText,
  BookOpen,
  BarChart3,
  Settings,
  LogOut,
  Building2,
  Briefcase,
  ClipboardList,
  LineChart,
  UserCog,
  ChevronDown,
  ShoppingCart,
  Calculator,
  FileBarChart,
  Menu,
  X,
  Receipt,
  Wrench,
  DollarSign,
  Warehouse,
  ListOrdered,
} from 'lucide-react';
import { authService } from '../services/auth';



interface LayoutProps {
  children: ReactNode;
}

interface MenuItem {
  key: string;
  label: string;
  icon: any;
  path?: string;
  items?: Array<{
    path: string;
    label: string;
    icon: any;
    roles?: string[];
  }>;
  roles?: string[]; // Roles permitidos para ver este item
}

// Configuración del menú lateral con control de roles
const menuConfig: MenuItem[] = [
  {
    key: 'dashboard',
    label: 'Dashboard',
    icon: Home,
    path: '/dashboard',
    roles: [], // Todos pueden ver (vacío = sin restricción)
  },
  {
    key: 'ventas',
    label: 'Ventas',
    icon: ShoppingCart,
    roles: ['admin',"administrador", 'vendedor', 'cajero'],
    items: [
      { path: '/ventas/nueva', label: 'Nueva Venta', icon: FileText, roles: ['admin', 'administrador', 'vendedor', 'cajero'] },
      { path: '/ventas/comprobantes', label: 'Comprobantes', icon: ClipboardList, roles: ['admin', 'administrador', 'vendedor', 'cajero'] },
      { path: '/ventas/clientes', label: 'Clientes', icon: Users, roles: ['admin', 'administrador', 'vendedor'] },
      { path: '/ventas/productos', label: 'Productos', icon: Package, roles: ['admin', 'administrador', 'vendedor'] },
      { path: '/ventas/servicios', label: 'Servicios', icon: Wrench, roles: ['admin', 'administrador', 'vendedor'] },
    ],
  },
  {
    key: 'contabilidad',
    label: 'Contabilidad',
    icon: Calculator,
    roles: ['admin', 'administrador', 'contador'],
    items: [
      { path: '/contabilidad/asientos', label: 'Asientos', icon: BookOpen, roles: ['admin', 'administrador', 'contador'] },
      { path: '/contabilidad/plan-cuentas', label: 'Plan de Cuentas', icon: Briefcase, roles: ['admin', 'administrador', 'contador'] },
      { path: '/contabilidad/diario', label: 'Diario', icon: FileText, roles: ['admin', 'administrador', 'contador'] },
    ],
  },
  {
    key: 'inventario',
    label: 'Inventario',
    icon: Package,
    roles: ['admin', 'administrador', 'vendedor'],
    items: [
      { path: '/inventario/productos', label: 'Productos', icon: BookOpen, roles: ['admin', 'administrador', 'vendedor'] },
      { path: '/inventario/almacen', label: 'Almacenes', icon: Briefcase, roles: ['admin', 'administrador'] },
    ],
  },
  {
    key: 'reportes',
    label: 'Reportes',
    icon: FileBarChart,
    roles: ['admin', 'administrador', 'contador'],
    items: [
      { path: '/reportes/ventas', label: 'Ventas', icon: BarChart3, roles: ['admin', 'administrador', 'contador'] },
      { path: '/reportes/inventario', label: 'Inventario', icon: LineChart, roles: ['admin', 'administrador', 'contador'] },
      { path: '/reportes/cobranzas', label: 'Cobranzas', icon: DollarSign, roles: ['admin', 'administrador', 'contador'] },
      { path: '/reportes/8-6', label: 'Formato 8.6', icon: FileText, roles: ['admin', 'administrador', 'contador'] },
    ],
  },
  {
    key: 'configuracion',
    label: 'Configuración',
    icon: Settings,
    roles: ['admin', 'administrador'],
    items: [
      { path: '/configuracion/empresa', label: 'Empresa', icon: Building2, roles: ['admin', 'administrador'] },
      { path: '/configuracion/usuarios', label: 'Usuarios', icon: UserCog, roles: ['admin', 'administrador'] },
      { path: '/configuracion/parametroscontables', label: 'Parametros Contables', icon: Settings, roles: ['admin', 'administrador'] },
      { path: '/configuracion/almacenes', label: 'Almacenes', icon: Warehouse, roles: ['admin', 'administrador'] },
      { path: '/configuracion/series', label: 'Series', icon: ListOrdered, roles: ['admin', 'administrador'] },
    ],
  },
];

export default function Layout({ children }: LayoutProps) {
  const location = useLocation();
  const navigate = useNavigate();
  const user = authService.getUser();
  const userRoles = (authService.getUserRoles() || []).map(r => r.toLowerCase());


  const [openSection, setOpenSection] = useState<string>('');
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);
  const [isSidebarCollapsed, setIsSidebarCollapsed] = useState(false);

  // Función para verificar si el usuario tiene acceso a un item
  const hasAccess = (roles?: string[]): boolean => {
    // Si no hay roles definidos, todos tienen acceso
    if (!roles || roles.length === 0) return true;
    // Si el usuario tiene alguno de los roles requeridos
    return authService.hasAnyRole(roles);
  };

  // Filtrar menú según roles del usuario
  const filteredMenuConfig = menuConfig
    .filter(menu => hasAccess(menu.roles))
    .map(menu => ({
      ...menu,
      items: menu.items?.filter(item => hasAccess(item.roles))
    }))
    .filter(menu => !menu.items || menu.items.length > 0); // Eliminar secciones sin items

  // Detectar sección abierta según la ruta actual
  useEffect(() => {
    const path = location.pathname;
    const section = filteredMenuConfig.find(
      (menu) => menu.items && menu.items.some((item) => path.startsWith(item.path))
    );
    if (section) {
      setOpenSection(section.key);
    }
  }, [location.pathname]);

  // Cerrar sidebar al cambiar de ruta en móvil
  useEffect(() => {
    setIsSidebarOpen(false);
  }, [location.pathname]);

  const toggleSection = (section: string) => {
    setOpenSection((prev) => (prev === section ? '' : section));
  };

  const isActive = (path: string) =>
    location.pathname === path
      ? 'bg-blue-100 text-black'
      : 'text-black hover:bg-blue-100 hover:text-black';

  const isParentActive = (paths: string[]) =>
    paths.some((path) => location.pathname.startsWith(path));

  const handleLogout = async () => {
    await authService.logout();
    navigate('/login');
  };

  return (
    <div className="min-h-screen bg-gray-100">
      {/* Overlay móvil */}
      {isSidebarOpen && (
        <div
          className="fixed inset-0 bg-black/50 z-40 md:hidden"
          onClick={() => setIsSidebarOpen(false)}
        />
      )}

      {/* Sidebar */}
      <aside
        className={`fixed top-0 left-0 h-full bg-gray-100 text-black flex flex-col shadow-2xl z-50 transition-all duration-300 ease-in-out ${
          isSidebarCollapsed ? 'w-20' : 'w-64'
        } ${isSidebarOpen ? 'translate-x-0' : '-translate-x-full'} md:translate-x-0`}
      >
        {/* Header */}
        <div className="p-6 border-b border-blue-800">
          <div className="flex items-center justify-between">
            {!isSidebarCollapsed ? (
              <div className="flex items-center space-x-3">
                <Receipt className="w-8 h-8 text-black" />
                <div>
                  <h1 className="text-2xl font-bold">Facturación</h1>
                  <p className="text-xs text-black">Sistema Contable</p>
                </div>
              </div>
            ) : (
              <></>
            )}

            {/* Botón toggle desktop */}
            <button
              onClick={() => setIsSidebarCollapsed(!isSidebarCollapsed)}
              className="hidden md:block text-black hover:text-black transition-colors"
              title={isSidebarCollapsed ? 'Expandir menú' : 'Contraer menú'}
            >
              <Menu className="w-5 h-5" />
            </button>

            {/* Botón cerrar móvil */}
            <button
              onClick={() => setIsSidebarOpen(false)}
              className="md:hidden text-black hover:text-white"
            >
              <X className="w-6 h-6" />
            </button>
          </div>
        </div>

        {/* NAV */}
        <nav className="flex-1 overflow-y-auto py-4 scrollbar-thin flex-1 overflow-y-auto scrollbar-hide [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
          {!isSidebarCollapsed ? (
            filteredMenuConfig.map(({ key, label, icon: Icon, items, path }) => (
              <div key={key} className="mt-2">
                {/* Si tiene submenú */}
                {items ? (
                  <>
                    <button
                      onClick={() => toggleSection(key)}
                      className={`w-full flex items-center justify-between px-6 py-3 transition-colors ${
                        isParentActive(items.map((i) => i.path))
                          ? 'bg-blue-100 text-black'
                          : 'text-black hover:bg-blue-100'
                      }`}
                    >
                      <div className="flex items-center">
                        <Icon className="w-5 h-5 mr-3" />
                        <span className="font-normal">{label}</span>
                      </div>
                      <motion.div
                        animate={{ rotate: openSection === key ? 0 : -90 }}
                        transition={{ duration: 0.2 }}
                      >
                        <ChevronDown className="w-4 h-4" />
                      </motion.div>
                    </button>

                    {/* Submenú */}
                    <AnimatePresence>
                      {openSection === key && (
                        <motion.div
                          initial={{ height: 0, opacity: 0 }}
                          animate={{ height: 'auto', opacity: 1 }}
                          exit={{ height: 0, opacity: 0 }}
                          transition={{ duration: 0.3, ease: 'easeInOut' }}
                          className="overflow-hidden"
                        >
                          <div className="bg-gray-200/50">
                            {items.map(({ path, label: itemLabel, icon: SubIcon }) => (
                              <Link
                                key={path}
                                to={path}
                                className={`flex items-center px-6 py-2.5 pl-14 transition-colors ${isActive(
                                  path
                                )}`}
                              >
                                <SubIcon className="w-4 h-4 mr-3" />
                                <span className="text-sm">{itemLabel}</span>
                              </Link>
                            ))}
                          </div>
                        </motion.div>
                      )}
                    </AnimatePresence>
                  </>
                ) : (
                  // Si NO tiene submenú (Dashboard, por ejemplo)
                  <Link
                    to={path!}
                    className={`flex items-center px-6 py-3 transition-colors ${isActive(path!)}`}
                  >
                    <Icon className="w-5 h-5 mr-3" />
                    <span className="font-normal">{label}</span>
                  </Link>
                )}
              </div>
            ))
          ) : (
            // Menú colapsado
            filteredMenuConfig.map(({ key, label, icon: Icon, items, path }) => (
              <Link
                key={key}
                to={items ? items[0].path : path!}
                className={`flex items-center justify-center px-6 py-3 transition-colors ${
                  items && isParentActive(items.map((i) => i.path))
                    ? 'bg-blue-200 text-black'
                    : 'text-black hover:bg-blue-200'
                }`}
                title={label}
              >
                <Icon className="w-5 h-5" />
              </Link>
            ))
          )}
        </nav>

        {/* Información del usuario */}
        {!isSidebarCollapsed ? (
          <div className="border-t border-blue-800">
            <div className="p-4">
              <p className="text-xs text-gray-500 mb-1">Conectado como:</p>
              <p className="text-blue-600 font-medium truncate">{user?.nombre || 'Invitado'}</p>
              <p className="text-xs text-gray-500 truncate">{user?.email || 'sin correo'}</p>
              <p className="text-xs text-blue-600 font-medium mt-1">
                {userRoles.length > 0 ? userRoles.join(', ') : 'Sin rol'}
              </p>
            </div>
            <button
              onClick={handleLogout}
              className="w-full flex items-center px-6 py-3 text-black hover:bg-red-600 hover:text-white transition-colors"
            >
              <LogOut className="w-5 h-5 mr-3" />
              Cerrar Sesión
            </button>
          </div>
        ) : (
          <div className="border-t border-blue-800">
            <button
              onClick={handleLogout}
              className="w-full flex items-center justify-center px-6 py-4 text-black hover:bg-red-600 hover:text-white transition-colors"
              title="Cerrar Sesión"
            >
              <LogOut className="w-5 h-5" />
            </button>
          </div>
        )}
      </aside>

      {/* Contenido principal */}
      <main
        className={`min-h-screen transition-all duration-300 ${
          isSidebarCollapsed ? 'md:ml-20' : 'md:ml-64'
        }`}
      >
        {/* Header móvil */}
        <div className="md:hidden bg-white shadow-sm p-4 flex items-center justify-between sticky top-0 z-30">
          <button
            onClick={() => setIsSidebarOpen(true)}
            className="text-black hover:text-red-600"
          >
            <Menu className="w-6 h-6" />
          </button>
          <div className="flex items-center space-x-2">
            <Receipt className="w-6 h-6 text-blue-200" />
            <h2 className="text-lg font-bold text-black">Facturación</h2>
          </div>
          <div className="w-6" />
        </div>

        {/* Contenido dinámico */}
        <div className="p-4- md:p-8">{children}</div>
      </main>
    </div>
  );
}