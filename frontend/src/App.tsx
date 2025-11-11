import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import EmpresaPage from './pages/configuracion/Empresa';
import Layout from './components/Layout';
import './App.css'
import { UsuariosPage } from './pages/configuracion/Usuarios';
import { AuthProvider } from './contexts/AuthContext';
import { ProtectedRoute } from './components/auth/ProtectedRoute';
import Unauthorized from './pages/Unauthorized';

function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          {/* Rutas públicas */}
          <Route path="/login" element={<Login />} />
          <Route path="/unauthorized" element={<Unauthorized />} />

          {/* Rutas protegidas */}
          <Route
            path="/"
            element={
              <ProtectedRoute>
                <Layout>
                  <Dashboard />
                </Layout>
              </ProtectedRoute>
            }
          />

          {/* Rutas de configuración - usando minúsculas normalizadas */}
          <Route
            path="/configuracion/empresa"
            element={
              <ProtectedRoute roles={['admin', 'administrador']}>
                <Layout>
                  <EmpresaPage />
                </Layout>
              </ProtectedRoute>
            }
          />

          <Route
            path="/configuracion/usuarios"
            element={
              <ProtectedRoute roles={['admin', 'administrador']}>
                <Layout>
                  <UsuariosPage />
                </Layout>
              </ProtectedRoute>
            }
          />

          {/* Rutas de productos y ventas */}
          <Route
            path="/productos"
            element={
              <ProtectedRoute roles={['admin', 'administrador', 'vendedor']}>
                <Layout>
                  <div>Productos (próximamente)</div>
                </Layout>
              </ProtectedRoute>
            }
          />

          <Route
            path="/clientes"
            element={
              <ProtectedRoute roles={['admin', 'administrador', 'vendedor']}>
                <Layout>
                  <div>Clientes (próximamente)</div>
                </Layout>
              </ProtectedRoute>
            }
          />

          <Route
            path="/ventas"
            element={
              <ProtectedRoute roles={['admin', 'administrador', 'vendedor', 'cajero']}>
                <Layout>
                  <div>Punto de Venta (próximamente)</div>
                </Layout>
              </ProtectedRoute>
            }
          />

          <Route
            path="/comprobantes"
            element={
              <ProtectedRoute roles={['admin', 'administrador', 'vendedor', 'cajero']}>
                <Layout>
                  <div>Comprobantes (próximamente)</div>
                </Layout>
              </ProtectedRoute>
            }
          />

          {/* Ruta por defecto */}
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
}

export default App;