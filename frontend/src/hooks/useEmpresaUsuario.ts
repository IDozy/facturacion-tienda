// hooks/useEmpresaUsuario.ts

import { useState, useEffect } from 'react';
import { usuarioService, type Empresa, type UsuarioAutenticado } from '@/services/userService';

interface UseEmpresaUsuarioReturn {
  usuario: UsuarioAutenticado | null;
  empresa: Empresa | null;
  loading: boolean;
  error: string | null;
  refetch: () => Promise<void>;
}

export function useEmpresaUsuario(): UseEmpresaUsuarioReturn {
  const [usuario, setUsuario] = useState<UsuarioAutenticado | null>(null);
  const [empresa, setEmpresa] = useState<Empresa | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchData = async () => {
    try {
      setLoading(true);
      setError(null);
      
      // Obtener perfil del usuario con empresa
      const perfilUsuario = await usuarioService.obtenerPerfilUsuario();
      setUsuario(perfilUsuario);
      setEmpresa(perfilUsuario.empresa || null);
      
    } catch (err: any) {
      setError(err.message || 'Error al cargar datos');
      console.error('Error al obtener empresa del usuario:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, []);

  return {
    usuario,
    empresa,
    loading,
    error,
    refetch: fetchData,
  };
}

// Hook específico solo para obtener la empresa
export function useEmpresa() {
  const { empresa, loading, error, refetch } = useEmpresaUsuario();
  
  return {
    empresa,
    loading,
    error,
    refetch,
  };
}