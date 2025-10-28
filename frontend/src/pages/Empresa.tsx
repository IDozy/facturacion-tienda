// src/pages/EmpresaPage.tsx
import { AlertCircle, Building2, CheckCircle2 } from "lucide-react";
import EmpresaForm from "../components/empresa/EmpresaForm";
import { useState } from "react";

export default function EmpresaPage() {
    const [showSuccess, setShowSuccess] = useState(false);

    return (

        <EmpresaForm />

    );
}
