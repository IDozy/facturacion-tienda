import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { authService } from './services/auth';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import EmpresaPage from './pages/Empresa'; // <-- Importa tu nueva página
import Layout from './components/Layout';
import './App.css'
import { UsuariosPage } from './pages/Usuarios';
import { AuthProvider } from './contexts/AuthContext';

// Componente para rutas protegidas
function ProtectedRoute({ children }: { children: React.ReactNode }) {
  if (!authService.isAuthenticated()) {
    return <Navigate to="/login" replace />;
  }
  return <>{children}</>;
}

function App() {
  return (
    <AuthProvider>

      <BrowserRouter>
        <Routes>
          {/* Ruta pública */}
          <Route path="/login" element={<Login />} />

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

          <Route
            path="/configuracion/empresa"
            element={<ProtectedRoute> <Layout> <EmpresaPage /> </Layout> </ProtectedRoute>}
          />

          <Route
            path="/configuracion/usuarios"
            element={<ProtectedRoute> <Layout> <UsuariosPage /> </Layout> </ProtectedRoute>}
          />

          <Route
            path="/productos"
            element={
              <ProtectedRoute>
                <Layout>
                  <div>Productos (próximamente)</div>
                </Layout>
              </ProtectedRoute>
            }
          />

          <Route
            path="/clientes"
            element={
              <ProtectedRoute>
                <Layout>
                  <div>Clientes (próximamente)</div>
                </Layout>
              </ProtectedRoute>
            }
          />

          <Route
            path="/ventas"
            element={
              <ProtectedRoute>
                <Layout>
                  <div>Punto de Venta (próximamente)</div>
                </Layout>
              </ProtectedRoute>
            }
          />

          <Route
            path="/comprobantes"
            element={
              <ProtectedRoute>
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
