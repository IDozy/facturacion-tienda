import React, { useState } from "react";
import { Button } from "@/components/ui/button";

interface User {
  id: number;
  name: string;
  email: string;
}

interface Props {
  user: User;
  onClose: () => void;
}

const PasswordModal: React.FC<Props> = ({ user, onClose }) => {
  const [password, setPassword] = useState("");
  const [confirm, setConfirm] = useState("");

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (password !== confirm) {
      alert("Las contraseñas no coinciden");
      return;
    }
    // Aquí iría la llamada a la API para actualizar contraseña
    console.log(`Nueva contraseña para ${user.name}: ${password}`);
    onClose();
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
      <div className="bg-white p-6 rounded-xl shadow-lg w-full max-w-md">
        <h3 className="text-lg font-semibold mb-4">
          Cambiar contraseña de {user.name}
        </h3>
        <form onSubmit={handleSubmit} className="space-y-3">
          <div>
            <label className="block text-sm font-medium">Nueva contraseña</label>
            <input
              type="password"
              className="border p-2 w-full rounded-md"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />
          </div>
          <div>
            <label className="block text-sm font-medium">Confirmar contraseña</label>
            <input
              type="password"
              className="border p-2 w-full rounded-md"
              value={confirm}
              onChange={(e) => setConfirm(e.target.value)}
              required
            />
          </div>
          <div className="flex justify-end gap-2 pt-3">
            <Button type="button" variant="outline" onClick={onClose}>
              Cancelar
            </Button>
            <Button type="submit">Guardar</Button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default PasswordModal;
