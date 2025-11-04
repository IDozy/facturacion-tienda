import { useState } from 'react';
import type {FormEvent} from 'react';
import { useNavigate } from 'react-router-dom';
import { AlertCircle } from 'lucide-react';
import { authService } from '../services/auth';

export default function Login() {
  const navigate = useNavigate();
  const [email, setEmail] = useState('admin@empresademo.com'); // Pre-llenado para testing
  const [password, setPassword] = useState('password123'); // Pre-llenado para testing
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const response = await authService.login({ email, password });
      
      // Verificar que el login fue exitoso
      if (response.success) {
        // Redirigir al dashboard
        navigate('/dashboard');
      } else {
        setError(response.message || 'Error al iniciar sesión');
      }
    } catch (err: any) {
      console.error('Error de login:', err);
      
      // Manejar diferentes tipos de errores
      if (err.response?.status === 422) {
        // Error de validación (credenciales incorrectas)
        const errors = err.response.data.errors;
        setError(errors?.email?.[0] || 'Credenciales incorrectas');
      } else if (err.response?.status === 419) {
        setError('Error de CSRF. Por favor, recarga la página.');
      } else if (err.response?.data?.message) {
        setError(err.response.data.message);
      } else {
        setError('Error de conexión. Intenta nuevamente.');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen grid md:grid-cols-2 bg-gray-50">
      {/* LADO IZQUIERDO */}
      <div className="relative flex items-center justify-center bg-gradient-to-tr from-rose-600 via-rose-500 to-pink-400 text-white p-10">
        {/* Fondo semitransparente con imagen */}
        <div
          className="absolute inset-0 bg-cover bg-center opacity-20"
          style={{ backgroundImage: "url('/bg-login.jpg')" }}
        ></div>

        {/* Contenido del panel izquierdo */}
        <div className="relative z-10 max-w-md">
          <h1 className="text-3xl font-bold mb-4">Bienvenido a Facturación</h1>
          <p className="text-white/90 mb-8">
            Inicia sesión para acceder a tu cuenta y gestionar tu negocio de manera eficiente.
          </p>
          
          {/* Credenciales de prueba */}
          <div className="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20">
            <p className="text-sm font-semibold mb-2">Credenciales de prueba:</p>
            <p className="text-xs text-white/90 font-mono">
              Email: admin@empresademo.com
            </p>
            <p className="text-xs text-white/90 font-mono">
              Password: password123
            </p>
          </div>
        </div>
      </div>

      {/* LADO DERECHO */}
      <div className="flex items-center justify-center p-10 bg-white">
        <div className="w-full max-w-sm">
          <h2 className="text-2xl font-bold text-gray-800 mb-6 text-center">
            Iniciar Sesión
          </h2>

          {/* Error */}
          {error && (
            <div className="bg-red-50 border border-red-200 rounded-lg p-3 mb-6 flex items-center">
              <AlertCircle className="w-5 h-5 text-red-600 mr-2 flex-shrink-0" />
              <p className="text-sm text-red-700">{error}</p>
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-5">
            <div>
              <label className="block text-gray-700 text-sm font-medium mb-1">
                Email
              </label>
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-rose-500 focus:border-transparent transition"
                placeholder="tu@ejemplo.com"
                required
                disabled={loading}
              />
            </div>

            <div>
              <label className="block text-gray-700 text-sm font-medium mb-1">
                Contraseña
              </label>
              <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-rose-500 focus:border-transparent transition"
                placeholder="••••••••"
                required
                disabled={loading}
              />
              <div className="text-right mt-1">
                <a 
                  href="#" 
                  className="text-sm text-rose-500 hover:underline"
                  onClick={(e) => e.preventDefault()}
                >
                  ¿Olvidaste tu contraseña?
                </a>
              </div>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full bg-rose-500 hover:bg-rose-600 text-white font-semibold py-2.5 rounded-lg shadow-md transition-colors disabled:bg-rose-300 disabled:cursor-not-allowed"
            >
              {loading ? (
                <span className="flex items-center justify-center">
                  <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Iniciando sesión...
                </span>
              ) : (
                'Iniciar Sesión'
              )}
            </button>
          </form>

          <p className="text-xs text-center text-gray-500 mt-8">
            © 2025 Sistema de Facturación. Todos los derechos reservados.
          </p>
        </div>
      </div>
    </div>
  );
}