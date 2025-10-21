import { useState, FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import { AlertCircle } from 'lucide-react';
import { authService } from '../services/auth';

export default function Login() {
  const navigate = useNavigate();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      await authService.login({ email, password });
      navigate('/');
    } catch (err: any) {
      setError(err.response?.data?.message || 'Error al iniciar sesión');
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
          <h1 className="text-3xl font-bold mb-4">Welcome to My Dashboard</h1>
          <p className="text-white/90 mb-8">
            Login to access your account and manage your business efficiently.
          </p>
          
        </div>
      </div>

      {/* LADO DERECHO */}
      <div className="flex items-center justify-center p-10 bg-white">
        <div className="w-full max-w-sm">
          <h2 className="text-2xl font-bold text-gray-800 mb-6 text-center">
            Login
          </h2>

          {/* Error */}
          {error && (
            <div className="bg-red-50 border border-red-200 rounded-lg p-3 mb-6 flex items-center">
              <AlertCircle className="w-5 h-5 text-red-600 mr-2" />
              <p className="text-sm text-red-700">{error}</p>
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-5">
            <div>
              <label className="block text-gray-700 text-sm mb-1">Email</label>
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-rose-500 focus:border-transparent"
                placeholder="you@example.com"
                required
              />
            </div>

            <div>
              <label className="block text-gray-700 text-sm mb-1">Password</label>
              <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-rose-500 focus:border-transparent"
                placeholder="••••••••"
                required
              />
              <div className="text-right mt-1">
                <a href="#" className="text-sm text-rose-500 hover:underline">
                  Forgot password?
                </a>
              </div>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full bg-rose-500 hover:bg-rose-600 text-white font-semibold py-2.5 rounded-lg shadow-md transition-colors disabled:bg-rose-300"
            >
              {loading ? 'Loading...' : 'Log in'}
            </button>
          </form>

          <p className="text-xs text-center text-gray-500 mt-8">
            © 2025 My Dashboard. All rights reserved.
          </p>
        </div>
      </div>
    </div>
  );
}
