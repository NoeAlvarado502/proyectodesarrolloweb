# Control Académico

Sistema de gestión académica desarrollado con Flight PHP 3, PHP 8+ y MySQL para entornos XAMPP.

## Requisitos
- XAMPP (Apache con `mod_rewrite`, PHP 8.1+, MySQL/MariaDB).
- Extensiones PHP: `pdo_mysql`, `fileinfo`, `mbstring`.

## Instalación
1. Colocar la carpeta en `C:\xampp\htdocs\ProyectoFinalFlight`.
2. Iniciar Apache y MySQL desde el panel de XAMPP.
3. Importar `database/setup.sql` en phpMyAdmin (crea `control_academico` con datos de prueba).
4. Abrir `http://localhost/ProyectoFinalFlight/` en el navegador.

*(Opcional: ajustar credenciales copiando `.env.example` a `.env`)*

## Módulos
- **Alumnos:** Expedientes, datos personales, fotografías y consultas individuales.
- **Carreras:** Planes de estudio organizados por semestres y asignación de cursos.
- **Cursos:** Catálogo central de materias.
- **Secciones:** Control de cupos, horarios, asignación de docentes y notas finales.
- **Catedráticos:** Registro de profesores, especialidades e historial de secciones.
- **Períodos:** Ciclos académicos con activación y cierre automático de actas.
- **Reportes:** Estadísticas de matrícula, reportes de notas e impresión limpia.

## Estructura
- `app/routes.php`: Enrutamiento y controladores con Flight.
- `app/views/`: Vistas y componentes de la interfaz.
- `assets/`: Estilos (CSS) y lógica de cliente (JS).
- `database/setup.sql`: Esquema y datos iniciales.
- `storage/uploads/`: Almacenamiento de fotografías.
