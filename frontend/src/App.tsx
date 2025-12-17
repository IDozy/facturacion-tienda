import { ReactNode } from 'react';
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';

import Layout from './components/Layout';
import { ProtectedRoute } from './components/auth/ProtectedRoute';
import { AuthProvider } from './contexts/AuthContext';
import './App.css';
import Dashboard from './pages/Dashboard';
import Unauthorized from './pages/Unauthorized';
import Login from './pages/Login';
import AlmacenesPage from './pages/configuracion/Almacen';
import EmpresaPage from './pages/configuracion/Empresa';
import ParametrosContablesPage from './pages/configuracion/ParametrosContables';
import { UsuariosPage } from './pages/configuracion/Usuarios';
import ProductosPage from './pages/inventario/Productos';

const withProtectedLayout = (children: ReactNode, roles?: string[]) => (
  <ProtectedRoute roles={roles}>
    <Layout>{children}</Layout>
  </ProtectedRoute>
);



function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          {/* Rutas públicas */}
          <Route path="/login" element={<Login />} />
          <Route path="/unauthorized" element={<Unauthorized />} />

          {/* Rutas protegidas */}
          <Route path="/" element={withProtectedLayout(<Dashboard />)} />
          <Route path="/dashboard" element={withProtectedLayout(<Dashboard />)} />
          <Route
            path="/inventario/productos"
            element={withProtectedLayout(<ProductosPage />, ['admin', 'administrador', 'vendedor'])}
          />
          <Route
            path="/ventas/productos"
            element={withProtectedLayout(<ProductosPage />, ['admin', 'administrador', 'vendedor'])}
          />

          {/* Rutas de configuración - usando minúsculas normalizadas */}
          <Route
            path="/dashboard/configuracion/empresa"
            element={withProtectedLayout(<EmpresaPage />, ['admin', 'administrador'])}
          />
          <Route
            path="/configuracion/empresa"
            element={<Navigate to="/dashboard/configuracion/empresa" replace />}
          />

          <Route
            path="/configuracion/usuarios"
            element={withProtectedLayout(<UsuariosPage />, ['admin', 'administrador'])}
          />
          <Route
            path="/configuracion/parametroscontables"
            element={withProtectedLayout(<ParametrosContablesPage />, ['admin', 'administrador'])}
          />

          <Route
            path="/configuracion/almacenes"
            element={withProtectedLayout(<AlmacenesPage />, ['admin', 'administrador'])}
          />

          {/* Ruta por defecto */}
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
}

export default App;
