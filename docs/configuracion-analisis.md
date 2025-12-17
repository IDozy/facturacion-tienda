# Revisión de Configuración: Empresa vs. Parámetros Contables

## Hallazgos clave
- **Menú y rutas actuales.** El menú de Configuración expone Empresa, Usuarios y Parámetros Contables, pero la opción de Parámetros Contables reusa el mismo formulario de configuración fiscal/tributaria de Empresa (`ConfiguracionEmpresaForm`), sin lógica propia ni diferenciación funcional.【F:frontend/src/config/navConfig.ts†L79-L109】【F:frontend/src/pages/configuracion/ParametrosContables.tsx†L1-L7】
- **Solapamientos de datos.** El modelo `Empresa` ya almacena moneda, IGV, indicador de inclusión de IGV y numeración de series, además de formato de fecha, decimales y zona horaria.【F:backend/app/Models/Empresa.php†L16-L57】【F:frontend/src/components/empresa/EmpresaForm.tsx†L398-L445】 A la vez, `ConfiguracionEmpresa` mantiene IGV, moneda y porcentajes de retención/percepción para la misma empresa.【F:backend/app/Models/ConfiguracionEmpresa.php†L12-L38】【F:frontend/src/types/ConfiguracionEmpresa.ts†L2-L12】
- **Duplicidad en UI y servicios.** La pantalla de Empresa ya presenta tabs de Datos generales, Facturación (moneda, IGV, series) y Preferencias (formato/decimales/zona horaria), mientras la pantalla de Parámetros Contables apunta al formulario tributario separado, generando dos fuentes para moneda e impuestos.【F:frontend/src/components/empresa/EmpresaForm.tsx†L398-L446】【F:frontend/src/components/configuracionEmpresa/ConfiguracionEmpresaForm.tsx†L25-L176】

## Criterio de unificación propuesto
1. **Fuente única de moneda e impuestos.** Consolidar moneda, IGV y "incluye IGV" en un solo registro por empresa (preferentemente `configuraciones_empresa`), eliminando los campos duplicados en `empresas` para evitar divergencias. Retención, percepción y tolerancia también viven en esa misma tabla.
2. **Pantalla única alineada a Empresa.** Usar el layout/tabs de Empresa como patrón único: 
   - **Datos generales:** identidad y contacto.
   - **Facturación y series:** moneda, IGV, switch de inclusión de IGV, numeraciones y series.
   - **Contabilidad:** retención, percepción y tolerancia (migrados desde Parámetros Contables), siguiendo los mismos componentes y validaciones inline.
   - **Preferencias:** formato de fecha, decimales, zona horaria.
   La ruta de Parámetros Contables puede redirigir a la tab Contabilidad dentro de Empresa para mantener coherencia y evitar pantallas gemelas.
3. **Servicios/backend coherentes.** Centralizar el cliente en `configuracionEmpresaService` y exponer un único endpoint por empresa para leer/actualizar la configuración contable/tributaria. `EmpresaForm` debe consumir esa misma fuente para mostrar/editar moneda e impuestos, evitando campos separados en `/api/empresa/me`.

## Integración del módulo "Series" en Empresa
- **Justificación:** Las series y numeración son atributos fiscales de la empresa y ya se editan en el tab de Facturación de Empresa junto con IGV y moneda.【F:frontend/src/components/empresa/EmpresaForm.tsx†L398-L446】 Mantenerlas en una ventana aparte rompe el contexto tributario y duplica pasos de configuración.
- **Organización recomendada:** Dentro de la pantalla de Empresa, conservar una sección dedicada "Series y numeración" (misma tarjeta actual) y, si se requiere gestión avanzada (CRUD de series), anidar un subtab o acceso contextual que filtre por `empresa_id` usando el servicio de series existente.

## Estructura final sugerida del menú de Configuración
- **Empresa** (incluye tabs Datos generales, Facturación/Series, Contabilidad, Preferencias; cubre Parámetros Contables y Series).
- **Usuarios** (gestión de acceso y roles, independiente).
- **Almacenes** (si se mantiene en Configuración, sigue como módulo separado por alcance operativo).

### Beneficios
- **Mantenibilidad:** Una sola tabla y endpoint para moneda/impuestos reduce migraciones dobles y validaciones duplicadas.
- **Claridad funcional:** El usuario encuentra todas las preferencias fiscales y contables en una sola ventana, evitando conflictos entre "Empresa" y "Parámetros Contables".
- **Experiencia consistente:** Misma UI y componentes; la navegación lateral refleja la estructura real del negocio sin pantallas redundantes.
