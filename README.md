# 🎓 Sistema de Control Académico

Plataforma integral de gestión y control académico desarrollada con **Flight PHP 3**, **PHP 8+** y **MySQL / MariaDB**. Diseñada bajo una arquitectura ligera, modular y de alto rendimiento orientada a entornos locales y servidores LAMP/XAMPP.

---

## 📋 Tabla de Contenido
- [Descripción General](#-descripción-general)
- [Características Principales](#-características-principales)
  - [Módulos del Sistema](#módulos-del-sistema)
  - [Seguridad y Validaciones](#seguridad-y-validaciones)
- [Credenciales de Acceso](#-credenciales-de-acceso)
- [Stack Tecnológico](#-stack-tecnológico)
- [Estructura del Proyecto](#-estructura-del-proyecto)
- [Requisitos del Entorno](#-requisitos-del-entorno)
- [Guía de Instalación Rápida](#-guía-de-instalación-rápida)
  - [Paso 1: Ubicación del Proyecto](#paso-1-ubicación-del-proyecto)
  - [Paso 2: Base de Datos](#paso-2-base-de-datos)
  - [Paso 3: Variables de Entorno](#paso-3-variables-de-entorno)
  - [Paso 4: Acceso al Sistema](#paso-4-acceso-al-sistema)
- [Mapeo de Rutas Principales](#-mapeo-de-rutas-principales)
- [Datos de Demostración y Pruebas](#-datos-de-demostración-y-pruebas)
- [Solución de Problemas Frecuentes](#-solución-de-problemas-frecuentes)
- [Licencia](#-licencia)

---

## 📖 Descripción General

El **Sistema de Control Académico** resuelve de forma unificada la administración académica de instituciones educativas: registro de expedientes estudiantiles, gestión de docentes, mallas curriculares por semestre, asignación de cursos, control de cupos y secciones, registro de notas finales y generación de reportes gerenciales con diseño listo para impresión.

A diferencia de aplicaciones pesadas basadas en CMS o frameworks monolíticos, este proyecto implementa **Flight PHP** como micro-framework central, garantizando tiempos de respuesta ultrarrápidos (< 25 ms), bajo consumo de memoria y código PHP limpio y transparente.

---

## ✨ Características Principales

### Módulos del Sistema

1. **🔐 Autenticación y Control de Sesión (Login / Logout)**:
   - Formulario de ingreso centrado en card minimalista con inputs protegidos.
   - Alternador interactivo para mostrar/ocultar contraseña con icono dinámico.
   - Protección global de rutas mediante filtro de enrutamiento nativo de Flight PHP.
   - Burbuja de usuario en la esquina superior derecha del panel con menú desplegable flotante (información concisa del perfil y acción para cerrar sesión).

2. **👨‍🎓 Gestión de Alumnos**:
   - Registro con nombres, apellidos, fecha de nacimiento y asignación de carrera.
   - Subida y almacenamiento seguro de fotografías de perfil (`storage/uploads/`).
   - Expediente individual con consulta de cursos asignados, calificaciones y promedio.

3. **🏛️ Carreras y Mallas Curriculares**:
   - Catálogo de carreras y planes de estudio.
   - Configuración dinámica de semestres por carrera.
   - Asignación de cursos por semestre curricular (obligatorios / electivos).

4. **📚 Catálogo de Cursos**:
   - Creación, edición y administración de asignaturas.
   - Relación directa con la estructura de semestres y secciones.

5. **👨‍🏫 Catedráticos / Docentes**:
   - Ficha integral con código docente (ej. `C-001`), DPI, correo, teléfono y especialidad/título.
   - Estados de disponibilidad (`activo` / `inactivo`).
   - Vista detallada del docente con historial de secciones y asignaturas impartidas.

6. **🗓️ Períodos Académicos**:
   - Ciclos lectivos (ej. `2026-I`, `2026-II`) con fechas de inicio y cierre.
   - Activación de período vigente para control automático del ciclo actual.

7. **🏷️ Secciones, Horarios y Cupos**:
   - Apertura de secciones vinculando curso, docente, período y aula.
   - Inscripción de alumnos con control estricto de cupo disponible.
   - Registro de ponderaciones y actas de calificaciones finales con cálculo de aprobación.

8. **📊 Reportes y Consultas Especializadas**:
   - **Reporte General de Estudiantes**: Filtros por carrera, período y estado académico.
   - **Reporte de Calificaciones**: Estadísticas de rendimiento y notas por sección.
   - **Consulta Individual de Alumno**: Búsqueda instantánea por carné o apellido con historial académico completo.
   - **Vistas preparadas para Impresión**: Reglas CSS `@media print` para exportación limpia a PDF o impresión física sin barras laterales ni botones innecesarios.

### Seguridad y Validaciones
- **Filtro de Enrutamiento y Control de Sesión**: Validación de sesiones activas en cada petición mediante `Flight::before('start', ...)` con redirección automática al login.
- **Hashing Criptográfico de Contraseñas**: Manejo de credenciales mediante `password_hash()` con algoritmo Bcrypt y verificación mediante `password_verify()`.
- **Prevención de Fijación de Sesión**: Regeneración de identificador de sesión (`session_regenerate_id(true)`) al autenticarse exitosamente.
- **Prevención de Inyecciones SQL**: Todas las transacciones se realizan mediante PDO con sentencias preparadas nativas (`PDO::ATTR_EMULATE_PREPARES => false`).
- **Protección CSRF**: Formularios protegidos mediante generación y validación de tokens criptográficos por sesión (`csrfToken()`, `csrfField()`).
- **Mitigación XSS**: Sanitización de salidas dinámicas con función auxiliar `e()`.
- **Subida Segura de Archivos**: Validación de tipos MIME (`image/jpeg`, `image/png`, `image/webp`), renombramiento con hashes únicos y protección contra ejecución directa en `storage/uploads/.htaccess`.

---

## 🔑 Credenciales de Acceso

El sistema incluye un usuario administrativo precargado en la base de datos:

| Campo | Valor |
| :--- | :--- |
| **Usuario** | `admin` |
| **Contraseña** | `admin123` |
| **Rol** | `Administrador` |
| **Correo** | `admin@colegio.edu.gt` |

> [!NOTE]
> Al intentar acceder a cualquier ruta del sistema sin haber iniciado sesión, el sistema redirigirá automáticamente a la pantalla de ingreso (`/login`).

---

## 🛠️ Stack Tecnológico

| Capa | Tecnología | Propósito |
| :--- | :--- | :--- |
| **Backend** | PHP 8.1+ | Lógica de negocio con tipado estricto y PSR-4 |
| **Framework** | [Flight PHP 3](https://flightphp.com/) | Micro-framework RESTful y enrutador de alto rendimiento |
| **Base de Datos** | MySQL 5.7+ / MariaDB 10.4+ | Motor InnoDB con integridad referencial y claves foráneas |
| **Frontend** | HTML5 / CSS3 / Vanilla JS | Diseño responsivo, sistema de tokens CSS y componentes reutilizables |
| **Servidor** | Apache 2.4+ (`mod_rewrite`) | Servidor web con reescritura limpia de URLs |
| **Gestor de Paquetes** | Composer | Gestión de dependencias (`flightphp/core`) |

---

## 📂 Estructura del Proyecto

```text
ProyectoFinalFlight/
├── .env.example             # Plantilla de variables de entorno
├── .gitignore               # Exclusiones de Git (vendor, .env, uploads)
├── .htaccess                # Redirección Apache al enrutador central
├── composer.json            # Definición del paquete y dependencias
├── index.php                # Punto de entrada de la aplicación
├── README.md                # Documentación del proyecto
│
├── app/
│   ├── bootstrap.php        # Configuración PDO, variables de entorno y helpers
│   ├── routes.php           # Definición de rutas, controladores y vistas
│   └── views/               # Plantillas PHP y componentes
│       ├── layout.php       # Layout maestro (Sidebar, Navbar, Burbuja de perfil)
│       ├── dashboard.php    # Panel de control con métricas rápidas
│       ├── auth/            # Módulo de autenticación (login con card interactiva)
│       ├── careers/         # Módulo de carreras y planes de estudio
│       ├── courses/         # Módulo de asignaturas
│       ├── periods/         # Módulo de ciclos y períodos académicos
│       ├── professors/      # Módulo y ficha de catedráticos
│       ├── sections/        # Módulo de secciones, horarios e inscripciones
│       ├── students/        # Módulo de expedientes de alumnos
│       ├── reports/         # Reportes de notas, alumnos y consulta rápida
│       ├── components/      # Componentes UI (empty states, alertas, headers)
│       └── errors/          # Plantillas de error (404, 500)
│
├── assets/
│   ├── app.css              # Sistema de estilos y variables de diseño
│   ├── app.js               # Comportamientos interactivos de UI
│   └── avatar.svg           # Recurso gráfico por defecto para perfiles
│
├── database/
│   └── setup.sql            # Esquema DDL completo con 12 tablas y datos muestra
│
└── storage/
    ├── logs/                # Registro de actividad y errores
    └── uploads/             # Directorio seguro de fotografías de alumnos
```

---

## 💻 Requisitos del Entorno

1. **Servidor Web**: Apache 2.4 con módulo `mod_rewrite` activo.
2. **PHP**: Versión 8.1 o superior.
   - Extensiones requeridas: `pdo_mysql`, `fileinfo`, `mbstring`, `openssl`, `json`.
3. **Base de Datos**: MySQL 5.7+ o MariaDB 10.4+.
4. **Entorno recomendado**: XAMPP para Windows o equivalente (LAMP / Docker).

---

## 🚀 Guía de Instalación Rápida

### Paso 1: Ubicación del Proyecto
Ubica la carpeta del proyecto en el directorio raíz de documentos de tu servidor:
- **XAMPP Windows**: `C:\xampp\htdocs\ProyectoFinalFlight`
- **Linux LAMP**: `/var/www/html/ProyectoFinalFlight`

### Paso 2: Base de Datos
Importa el esquema y los datos iniciales incluidos en `database/setup.sql`.

**Opción A: Vía Terminal / Consola de MySQL:**
```bash
mysql -u root -p < database/setup.sql
```

**Opción B: Vía phpMyAdmin:**
1. Ingresa a `http://localhost/phpmyadmin/`.
2. Dirígete a la pestaña **Importar**.
3. Selecciona el archivo `database/setup.sql` y pulsa **Continuar**.
*(El script creará automáticamente la base de datos `control_academico` con sus tablas y registros de prueba).*

### Paso 3: Variables de Entorno (Opcional)
Si usas las credenciales por defecto de XAMPP (`root` sin contraseña en el puerto `3306`), **no requiere configuración adicional**.

Si tus credenciales de base de datos son distintas, copia `.env.example` como `.env` y ajusta tus valores:
```ini
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=control_academico
DB_USER=root
DB_PASSWORD=tu_clave
```

### Paso 4: Acceso al Sistema
Asegúrate de que Apache y MySQL estén iniciados en el panel de XAMPP y abre tu navegador:
```text
http://localhost/ProyectoFinalFlight/
```

---

## 🗺️ Mapeo de Rutas Principales

| Módulo | Ruta | Descripción |
| :--- | :--- | :--- |
| **Ingreso / Login** | `/login` | Pantalla de inicio de sesión con card interactiva |
| **Cierre de Sesión** | `/logout` | Destrucción de sesión y redirección protegida |
| **Inicio** | `/` | Dashboard principal con estadísticas del ciclo |
| **Alumnos** | `/alumnos` | Listado, registro y expediente de estudiantes |
| **Alumnos (Nuevo)** | `/alumnos/nuevo` | Formulario con carga de fotografía |
| **Carreras** | `/carreras` | Malla curricular y semestres del plan |
| **Cursos** | `/cursos` | Catálogo de asignaturas |
| **Catedráticos** | `/catedraticos` | Registro de docentes y ficha personal |
| **Períodos** | `/periodos` | Administración de ciclos lectivos |
| **Secciones** | `/secciones` | Asignación de cupos, horarios y notas |
| **Reporte Alumnos** | `/reportes/alumnos` | Matrícula general y filtros por carrera |
| **Reporte Notas** | `/reportes/notas` | Cuadro de calificaciones por sección |
| **Consulta Alumno** | `/reportes/consulta-alumno` | Búsqueda por carné e historial académico |

---

## 🧪 Datos de Demostración y Pruebas

El archivo `database/setup.sql` precarga datos listos para probar inmediatamente:

- **Usuario administrativo inicial**:
  - **Usuario**: `admin`
  - **Contraseña**: `admin123` (o `admin`)
  - **Perfil**: Coordinación Académica (`admin@colegio.edu.gt`)
- **Períodos configurados**:
  - `2026-I`: Período activo actual.
  - `2026-II`: Período programado / cerrado.
- **Carreras registradas**:
  - Bachillerato en Computación
  - Bachillerato en Ciencias y Letras
  - Bachillerato en Diseño Gráfico
  - Perito Contador
- **Catedráticos**:
  - `C-001`: Ing. Juan Pérez García (Sistemas)
  - `C-002`: Licda. Ana López Estrada (Pedagogía)
  - `C-003`: Msc. Luis Gómez Morales (Matemáticas)
- **Estudiantes de prueba**:
  - Diego Alejandro Morales Pérez
  - Sofía Isabel Ramírez Méndez

---

## 🔧 Solución de Problemas Frecuentes

### 1. Error 404 al navegar entre módulos
- **Causa**: El módulo `mod_rewrite` de Apache no está activo o `.htaccess` está ignorado.
- **Solución**: En `httpd.conf` de Apache, descomenta la línea:
  ```apache
  LoadModule rewrite_module modules/mod_rewrite.so
  ```
  Y asegúrate de que en el bloque de tu directorio esté configurado:
  ```apache
  AllowOverride All
  ```

### 2. Error de conexión con la base de datos (PDOException)
- Verifica que el servicio MySQL esté corriendo en XAMPP.
- Si MySQL usa un puerto distinto al estándar (ej. `3307`), configúralo en el archivo `.env`:
  ```ini
  DB_PORT=3307
  ```

### 3. Las imágenes de alumnos no se guardan
- Asegúrate de que el directorio `storage/uploads/` tenga permisos de escritura.
- Verifica en `php.ini` que `file_uploads = On` y que los límites `upload_max_filesize` y `post_max_size` sean suficientes (mínimo 5M).

---

## 📄 Licencia

Este proyecto es de código abierto bajo la licencia [MIT](LICENSE).
