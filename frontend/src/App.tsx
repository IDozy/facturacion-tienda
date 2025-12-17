import { ReactNode } from 'react';
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';

import Layout from './components/Layout';
import { ProtectedRoute } from './components/auth/ProtectedRoute';
import { AuthProvider } from './contexts/AuthContext';
import './App.css';
import Unauthorized from './pages/Unauthorized';
import Login from './pages/Login';
import { navConfig, flattenNavItems } from './config/navConfig';

const withProtectedLayout = (children: ReactNode, roles?: string[]) => (
  <ProtectedRoute roles={roles}>
    <Layout>{children}</Layout>
  </ProtectedRoute>
);

function App() {
  const protectedNavRoutes = flattenNavItems(navConfig).filter(
    (item) => item.path && item.element
  );

  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          {/* Rutas públicas */}
          <Route path="/login" element={<Login />} />
          <Route path="/unauthorized" element={<Unauthorized />} />

          {/* Rutas protegidas derivadas del navConfig */}
          {protectedNavRoutes.map((item) => (
            <Route
              key={item.key}
              path={item.path}
              element={withProtectedLayout(item.element!, item.roles)}
            />
          ))}

          {/* Redirecciones (legacy) */}
          <Route
            path="/configuracion/empresa"
            element={<Navigate to="/dashboard/configuracion/empresa" replace />}
          />

          {/* Ruta por defecto */}
          <Route path="*" element={<Navigate to="/dashboard" replace />} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
}

export default App;

