import { ReactNode } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { Home, Package, Users, FileText, LogOut } from 'lucide-react';
import { authService } from '../services/auth';

interface LayoutProps {
  children: ReactNode;
}

export default function Layout({ children }: LayoutProps) {
  const location = useLocation();
  const navigate = useNavigate();
  const user = authService.getUser();

  const isActive = (path: string) => {
    return location.pathname === path
      ? 'bg-blue-700 text-white'
      : 'text-gray-300 hover:bg-blue-700 hover:text-white';
  };

  const handleLogout = async () => {
    await authService.logout();
    navigate('/login');
  };

  return (
    <div className="min-h-screen bg-gray-100">
      {/* Sidebar */}
      <aside className="fixed top-0 left-0 h-full w-64 bg-blue-900 text-white">
        <div className="p-6">
          <h1 className="text-2xl font-bold">Facturación</h1>
          <p className="text-sm text-blue-300">Sistema POS</p>
        </div>

        <nav className="mt-6">
          <Link
            to="/"
            className={`flex items-center px-6 py-3 transition-colors ${isActive('/')}`}
          >
            <Home className="w-5 h-5 mr-3" />
            Dashboard
          </Link>

          <Link
            to="/productos"
            className={`flex items-center px-6 py-3 transition-colors ${isActive('/productos')}`}
          >
            <Package className="w-5 h-5 mr-3" />
            Productos
          </Link>

          <Link
            to="/clientes"
            className={`flex items-center px-6 py-3 transition-colors ${isActive('/clientes')}`}
          >
            <Users className="w-5 h-5 mr-3" />
            Clientes
          </Link>

          <Link
            to="/ventas"
            className={`flex items-center px-6 py-3 transition-colors ${isActive('/ventas')}`}
          >
            <FileText className="w-5 h-5 mr-3" />
            Nueva Venta
          </Link>

          <Link
            to="/comprobantes"
            className={`flex items-center px-6 py-3 transition-colors ${isActive('/comprobantes')}`}
          >
            <FileText className="w-5 h-5 mr-3" />
            Comprobantes
          </Link>
        </nav>

        <div className="absolute bottom-0 w-full border-t border-blue-800">
          <div className="p-4">
            <p className="text-sm text-blue-300 mb-1">Conectado como:</p>
            <p className="text-white font-medium truncate">{user?.name}</p>
            <p className="text-xs text-blue-300 truncate">{user?.email}</p>
          </div>
          <button
            onClick={handleLogout}
            className="w-full flex items-center px-6 py-3 text-gray-300 hover:bg-red-600 hover:text-white transition-colors"
          >
            <LogOut className="w-5 h-5 mr-3" />
            Cerrar Sesión
          </button>
        </div>
      </aside>

      {/* Main Content */}
      <main className="ml-64 p-8">
        {children}
      </main>
    </div>
  );
}
