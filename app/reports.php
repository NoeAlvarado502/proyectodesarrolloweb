<?php
declare(strict_types=1);

/**
 * Módulo de Reportes Académicos
 * 
 * Principios:
 * - Sobriedad, consistencia, alineación estricta (texto izquierda, números derecha).
 * - Encabezado institucional, fecha de emisión y resumen de una línea.
 * - Integridad de la base de datos (solo lectura, transaccionalmente seguro).
 */

function ensureReportDatabaseObjects(?PDO $pdo = null): void
{
    static $ensured = false;
    if ($ensured) return;
    $ensured = true;
    $pdo ??= db();

    // Crear vista unificada view_report_enrollments si no existe
    try {
        $pdo->exec("
            CREATE OR REPLACE VIEW view_report_enrollments AS
            SELECT 
                se.id AS enrollment_id,
                se.section_id,
                se.student_id,
                se.final_score,
                se.status AS enrollment_status,
                se.created_at AS enrollment_date,
                s.first_name AS student_first_name,
                s.last_name AS student_last_name,
                CONCAT(s.last_name, ', ', s.first_name) AS student_full_name_sort,
                CONCAT(s.first_name, ' ', s.last_name) AS student_full_name,
                CONCAT('AC-', LPAD(s.id, 5, '0')) AS student_code,
                s.birth_date AS student_birth_date,
                s.created_at AS student_entry_date,
                ca.id AS career_id,
                ca.name AS career_name,
                cs.code AS section_code,
                cs.capacity AS section_capacity,
                cs.schedule AS section_schedule,
                cs.classroom AS section_classroom,
                c.id AS course_id,
                c.name AS course_name,
                p.id AS period_id,
                p.name AS period_name,
                p.status AS period_status,
                p.start_date AS period_start_date,
                p.end_date AS period_end_date,
                cs.professor_id,
                COALESCE(CONCAT(pr.first_name, ' ', pr.last_name), cs.teacher, 'Sin asignar') AS professor_name,
                pr.email AS professor_email
            FROM section_enrollments se
            JOIN students s ON s.id = se.student_id
            JOIN careers ca ON ca.id = s.career_id
            JOIN course_sections cs ON cs.id = se.section_id
            JOIN courses c ON c.id = cs.course_id
            JOIN periods p ON p.id = cs.period_id
            LEFT JOIN professors pr ON pr.id = cs.professor_id
        ");
    } catch (Throwable $e) {
        // En caso de permisos limitados, la aplicación puede realizar los joins directamente
    }
}

/**
 * Exporta un conjunto de datos como archivo CSV compatible con Excel en UTF-8.
 */
function exportReportCsv(string $filename, array $headers, array $rows): never
{
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
    header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    // Escribir BOM UTF-8 para apertura correcta en Microsoft Excel en español
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

/**
 * Calcula el perfil y avance curricular de un estudiante:
 * - Semestre actual: primer semestre con cursos obligatorios pendientes.
 * - Cursos aprobados vs obligatorios.
 * - Estado ('activo', 'inactivo', 'graduado', 'retirado').
 */
function calculateStudentAcademicProgress(int $studentId, int $careerId, ?PDO $pdo = null): array
{
    $pdo ??= db();
    $minScore = minPassingGrade();

    // 1. Obtener cursos aprobados del estudiante (de section_enrollments y compatibilidad con grades)
    $approvedStmt = $pdo->prepare('
        SELECT DISTINCT cs.course_id
        FROM section_enrollments se
        JOIN course_sections cs ON cs.id = se.section_id
        WHERE se.student_id = ? AND (se.status = "aprobado" OR se.final_score >= ?)
    ');
    $approvedStmt->execute([$studentId, $minScore]);
    $approvedCourseIds = $approvedStmt->fetchAll(PDO::FETCH_COLUMN);

    // Complementar con tabla grades histórica si existen registros
    try {
        $legacyStmt = $pdo->prepare('SELECT DISTINCT course_id FROM grades WHERE student_id = ? AND score >= ?');
        $legacyStmt->execute([$studentId, $minScore]);
        $legacyIds = $legacyStmt->fetchAll(PDO::FETCH_COLUMN);
        $approvedCourseIds = array_values(array_unique(array_merge($approvedCourseIds, $legacyIds)));
    } catch (Throwable) {}

    $approvedMap = array_fill_keys($approvedCourseIds, true);

    // 2. Obtener la malla curricular de la carrera (semestres y cursos)
    $semStmt = $pdo->prepare('
        SELECT cs.id AS semester_id, cs.number, cs.name AS semester_name,
               csc.course_id, csc.is_mandatory, c.name AS course_name
        FROM career_semesters cs
        LEFT JOIN career_semester_courses csc ON csc.semester_id = cs.id
        LEFT JOIN courses c ON c.id = csc.course_id
        WHERE cs.career_id = ?
        ORDER BY cs.number ASC, c.name ASC
    ');
    $semStmt->execute([$careerId]);
    $planRows = $semStmt->fetchAll();

    $semesters = [];
    $totalMandatory = 0;
    $approvedMandatory = 0;

    foreach ($planRows as $r) {
        $num = (int)$r['number'];
        if (!isset($semesters[$num])) {
            $semesters[$num] = [
                'number' => $num,
                'name' => $r['semester_name'] ?: "Semestre {$num}",
                'total_courses' => 0,
                'mandatory_courses' => 0,
                'approved_courses' => 0,
                'pending_mandatory' => 0,
                'courses' => []
            ];
        }

        if (!empty($r['course_id'])) {
            $isAppr = !empty($approvedMap[$r['course_id']]);
            $isMand = (bool)$r['is_mandatory'];
            $semesters[$num]['total_courses']++;
            if ($isMand) {
                $semesters[$num]['mandatory_courses']++;
                $totalMandatory++;
                if ($isAppr) {
                    $approvedMandatory++;
                } else {
                    $semesters[$num]['pending_mandatory']++;
                }
            }
            if ($isAppr) {
                $semesters[$num]['approved_courses']++;
            }
            $semesters[$num]['courses'][] = [
                'course_id' => $r['course_id'],
                'course_name' => $r['course_name'],
                'is_mandatory' => $isMand,
                'is_approved' => $isAppr
            ];
        }
    }

    // Calcular Semestre actual: primer semestre con cursos obligatorios pendientes
    $currentSemesterNum = 1;
    $foundCurrent = false;

    if (!empty($semesters)) {
        foreach ($semesters as $num => $sdata) {
            // Si tiene cursos obligatorios y no todos están aprobados
            if ($sdata['pending_mandatory'] > 0) {
                $currentSemesterNum = $num;
                $foundCurrent = true;
                break;
            }
        }
        if (!$foundCurrent) {
            // Todos los semestres aprobados
            $currentSemesterNum = max(array_keys($semesters));
        }
    }

    // 3. Determinar Estado del alumno (activo, inactivo, graduado, retirado)
    $status = 'activo';

    // Verificar si es graduado (completó la totalidad de cursos obligatorios de la carrera)
    if ($totalMandatory > 0 && $approvedMandatory >= $totalMandatory) {
        $status = 'graduado';
    } else {
        // Verificar última inscripción en section_enrollments
        $lastEnrollmentStmt = $pdo->prepare('
            SELECT se.status, se.final_score, p.status AS period_status, p.end_date
            FROM section_enrollments se
            JOIN course_sections cs ON cs.id = se.section_id
            JOIN periods p ON p.id = cs.period_id
            WHERE se.student_id = ?
            ORDER BY se.created_at DESC, p.id DESC
            LIMIT 1
        ');
        $lastEnrollmentStmt->execute([$studentId]);
        $lastEnr = $lastEnrollmentStmt->fetch();

        if ($lastEnr) {
            if ($lastEnr['status'] === 'retirado') {
                $status = 'retirado';
            } elseif ($lastEnr['period_status'] === 'cerrado' && $lastEnr['status'] !== 'cursando') {
                // Si su último período registrado ya cerró y no tiene inscripciones activas
                $hasActiveStmt = $pdo->prepare('
                    SELECT COUNT(*) FROM section_enrollments se
                    JOIN course_sections cs ON cs.id = se.section_id
                    JOIN periods p ON p.id = cs.period_id
                    WHERE se.student_id = ? AND (p.status = "activo" OR se.status = "cursando")
                ');
                $hasActiveStmt->execute([$studentId]);
                if ((int)$hasActiveStmt->fetchColumn() === 0) {
                    $status = 'inactivo';
                } else {
                    $status = 'activo';
                }
            } else {
                $status = 'activo';
            }
        } else {
            // Alumno sin inscripciones aún: activo por defecto (recién ingresado)
            $status = 'activo';
        }
    }

    return [
        'current_semester' => $currentSemesterNum,
        'current_semester_label' => "Semestre {$currentSemesterNum}",
        'status' => $status,
        'approved_count' => count($approvedCourseIds),
        'total_mandatory' => $totalMandatory,
        'approved_mandatory' => $approvedMandatory,
        'semesters_progress' => array_values($semesters)
    ];
}

/**
 * Consulta C: Ficha y expediente completo del alumno
 */
function getStudentAcademicProfile(int $studentId, ?PDO $pdo = null): ?array
{
    $pdo ??= db();
    ensureReportDatabaseObjects($pdo);
    $minScore = minPassingGrade();

    // 1. Datos personales y carrera
    $stStmt = $pdo->prepare('
        SELECT s.id, s.first_name, s.last_name, s.birth_date, s.photo, s.career_id, s.created_at,
               c.name AS career_name
        FROM students s
        JOIN careers c ON c.id = s.career_id
        WHERE s.id = ?
    ');
    $stStmt->execute([$studentId]);
    $student = $stStmt->fetch();
    if (!$student) return null;

    $studentCode = 'AC-' . str_pad((string)$student['id'], 5, '0', STR_PAD_LEFT);
    $fullName = trim($student['first_name'] . ' ' . $student['last_name']);
    $entryDate = !empty($student['created_at']) ? date('d/m/Y', strtotime($student['created_at'])) : '—';
    $birthDate = !empty($student['birth_date']) ? date('d/m/Y', strtotime($student['birth_date'])) : '—';
    $age = !empty($student['birth_date']) ? (new DateTime($student['birth_date']))->diff(new DateTime('today'))->y : '—';

    // 2. Progreso académico y semestre actual
    $progress = calculateStudentAcademicProgress($studentId, (int)$student['career_id'], $pdo);

    // 3. Historial de notas agrupado por período (más reciente al más antiguo)
    $histStmt = $pdo->prepare('
        SELECT 
            se.id AS enrollment_id,
            se.final_score,
            se.status AS enrollment_status,
            cs.id AS section_id,
            cs.code AS section_code,
            c.id AS course_id,
            c.name AS course_name,
            p.id AS period_id,
            p.name AS period_name,
            p.status AS period_status,
            p.start_date AS period_start_date,
            COALESCE(CONCAT(pr.first_name, " ", pr.last_name), cs.teacher, "Sin asignar") AS professor_name
        FROM section_enrollments se
        JOIN course_sections cs ON cs.id = se.section_id
        JOIN courses c ON c.id = cs.course_id
        JOIN periods p ON p.id = cs.period_id
        LEFT JOIN professors pr ON pr.id = cs.professor_id
        WHERE se.student_id = ?
        ORDER BY p.start_date DESC, p.id DESC, c.name ASC
    ');
    $histStmt->execute([$studentId]);
    $enrollmentRows = $histStmt->fetchAll();

    // Soporte para registros en tabla legacy `grades` si no están en `section_enrollments`
    try {
        $legStmt = $pdo->prepare('
            SELECT g.score AS final_score, g.course_id, g.section_id,
                   co.name AS course_name, sec.name AS section_code,
                   sem.id AS period_id, sem.name AS period_name
            FROM grades g
            JOIN courses co ON co.id = g.course_id
            JOIN sections sec ON sec.id = g.section_id
            JOIN semesters sem ON sem.id = g.semester_id
            WHERE g.student_id = ?
        ');
        $legStmt->execute([$studentId]);
        $legRows = $legStmt->fetchAll();

        // Evitar duplicados por course_id
        $existingCourseIds = array_column($enrollmentRows, 'course_id');
        foreach ($legRows as $leg) {
            if (!in_array($leg['course_id'], $existingCourseIds)) {
                $enrollmentRows[] = [
                    'enrollment_id' => 0,
                    'final_score' => $leg['final_score'],
                    'enrollment_status' => ($leg['final_score'] >= $minScore) ? 'aprobado' : 'reprobado',
                    'section_id' => $leg['section_id'],
                    'section_code' => $leg['section_code'],
                    'course_id' => $leg['course_id'],
                    'course_name' => $leg['course_name'],
                    'period_id' => $leg['period_id'],
                    'period_name' => $leg['period_name'],
                    'period_status' => 'cerrado',
                    'period_start_date' => '2020-01-01',
                    'professor_name' => 'Sin asignar'
                ];
            }
        }
    } catch (Throwable) {}

    // Agrupar por período
    $periodsGrouped = [];
    $allScores = [];

    foreach ($enrollmentRows as $row) {
        $pName = $row['period_name'] ?: 'Sin período';
        if (!isset($periodsGrouped[$pName])) {
            $periodsGrouped[$pName] = [
                'period_id' => $row['period_id'],
                'period_name' => $pName,
                'period_status' => $row['period_status'] ?? 'cerrado',
                'courses' => [],
                'scores' => []
            ];
        }

        $score = $row['final_score'];
        if ($score !== null && $score !== '') {
            $allScores[] = (float)$score;
            $periodsGrouped[$pName]['scores'][] = (float)$score;
        }

        $periodsGrouped[$pName]['courses'][] = [
            'enrollment_id' => $row['enrollment_id'],
            'section_id' => $row['section_id'],
            'section_code' => $row['section_code'],
            'course_id' => $row['course_id'],
            'course_name' => $row['course_name'],
            'professor_name' => $row['professor_name'],
            'final_score' => $score,
            'status' => $row['enrollment_status'],
            'is_reprobado' => ($score !== null && (int)$score < $minScore) || $row['enrollment_status'] === 'reprobado'
        ];
    }

    // Calcular estadísticas por período
    foreach ($periodsGrouped as &$p) {
        $p['total_courses'] = count($p['courses']);
        $p['average'] = !empty($p['scores']) ? round(array_sum($p['scores']) / count($p['scores']), 1) : null;
        $p['approved_count'] = count(array_filter($p['courses'], fn($c) => !$c['is_reprobado'] && $c['status'] === 'aprobado'));
    }
    unset($p);

    $overallAverage = !empty($allScores) ? round(array_sum($allScores) / count($allScores), 1) : 0.0;

    return [
        'student' => [
            'id' => $student['id'],
            'code' => $studentCode,
            'full_name' => $fullName,
            'first_name' => $student['first_name'],
            'last_name' => $student['last_name'],
            'photo' => $student['photo'],
            'career_id' => (int)$student['career_id'],
            'career_name' => $student['career_name'],
            'entry_date' => $entryDate,
            'birth_date' => $birthDate,
            'age' => $age,
            'status' => $progress['status'],
        ],
        'academic_summary' => [
            'approved_courses' => $progress['approved_count'],
            'total_mandatory' => $progress['total_mandatory'],
            'gpa' => $overallAverage,
            'current_semester' => $progress['current_semester'],
            'current_semester_label' => $progress['current_semester_label'],
            'total_courses_taken' => count($enrollmentRows)
        ],
        'periods_history' => $periodsGrouped,
        'semesters_progress' => $progress['semesters_progress']
    ];
}

/**
 * Reporte A: General de alumnos por carrera
 */
function getStudentsReport(array $filters = [], ?PDO $pdo = null): array
{
    $pdo ??= db();
    ensureReportDatabaseObjects($pdo);

    $careerId = (int)($filters['career_id'] ?? 0);
    $statusFilter = strtolower(trim((string)($filters['status'] ?? 'todas')));
    if ($statusFilter === 'todos' || $statusFilter === 'todas' || $statusFilter === '') {
        $statusFilter = null;
    }
    $query = trim((string)($filters['q'] ?? ''));

    // Obtener catálogo de carreras para el filtro
    $careers = $pdo->query('SELECT id, name FROM careers ORDER BY name ASC')->fetchAll();

    $sql = '
        SELECT s.id, s.first_name, s.last_name, s.birth_date, s.photo, s.career_id, s.created_at,
               c.name AS career_name
        FROM students s
        JOIN careers c ON c.id = s.career_id
        WHERE 1=1
    ';
    $params = [];

    if ($careerId > 0) {
        $sql .= ' AND s.career_id = ?';
        $params[] = $careerId;
    }

    if ($query !== '') {
        // Permitir búsqueda por nombre, apellido o carné AC-xxxxx / número
        $cleanQ = preg_replace('/^AC-0*/i', '', $query);
        if (is_numeric($cleanQ)) {
            $sql .= ' AND (s.id = ? OR CONCAT(s.first_name, " ", s.last_name) LIKE ?)';
            $params[] = (int)$cleanQ;
            $params[] = '%' . $query . '%';
        } else {
            $sql .= ' AND (CONCAT(s.first_name, " ", s.last_name) LIKE ? OR CONCAT(s.last_name, " ", s.first_name) LIKE ?)';
            $params[] = '%' . $query . '%';
            $params[] = '%' . $query . '%';
        }
    }

    $sql .= ' ORDER BY c.name ASC, s.last_name ASC, s.first_name ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rawStudents = $stmt->fetchAll();

    $students = [];
    $summary = [
        'total' => 0,
        'activos' => 0,
        'inactivos' => 0,
        'graduados' => 0,
        'retirados' => 0
    ];

    foreach ($rawStudents as $st) {
        $prog = calculateStudentAcademicProgress((int)$st['id'], (int)$st['career_id'], $pdo);
        $stStatus = $prog['status'];

        // Filtrar por estado si se especificó
        if ($statusFilter !== null && $stStatus !== $statusFilter) {
            continue;
        }

        $summary['total']++;
        if ($stStatus === 'activo') $summary['activos']++;
        elseif ($stStatus === 'inactivo') $summary['inactivos']++;
        elseif ($stStatus === 'graduado') $summary['graduados']++;
        elseif ($stStatus === 'retirado') $summary['retirados']++;

        $students[] = [
            'id' => (int)$st['id'],
            'code' => 'AC-' . str_pad((string)$st['id'], 5, '0', STR_PAD_LEFT),
            'first_name' => $st['first_name'],
            'last_name' => $st['last_name'],
            'full_name_sort' => trim($st['last_name'] . ', ' . $st['first_name']),
            'career_id' => (int)$st['career_id'],
            'career_name' => $st['career_name'],
            'current_semester' => $prog['current_semester_label'],
            'status' => $stStatus,
            'entry_date' => !empty($st['created_at']) ? date('d/m/Y', strtotime($st['created_at'])) : '—'
        ];
    }

    // Agrupamiento por carrera (cuando se selecciona "todas las carreras" o para vista estructurada)
    $groupedByCareer = [];
    foreach ($students as $st) {
        $cName = $st['career_name'];
        if (!isset($groupedByCareer[$cName])) {
            $groupedByCareer[$cName] = [
                'career_name' => $cName,
                'students' => [],
                'subtotal' => 0
            ];
        }
        $groupedByCareer[$cName]['students'][] = $st;
        $groupedByCareer[$cName]['subtotal']++;
    }

    return [
        'students' => $students,
        'grouped' => $groupedByCareer,
        'summary' => $summary,
        'careers' => $careers,
        'filters' => [
            'career_id' => $careerId,
            'status' => $statusFilter ?? 'todas',
            'q' => $query
        ]
    ];
}

/**
 * Reporte B: Notas por carrera, curso y sección
 */
function getGradesReport(array $filters = [], ?PDO $pdo = null): array
{
    $pdo ??= db();
    ensureReportDatabaseObjects($pdo);
    $minScore = minPassingGrade();

    // 1. Obtener catálogos para filtros en cascada
    $periods = $pdo->query('SELECT id, name, status FROM periods ORDER BY id DESC')->fetchAll();
    
    // Período predeterminado: el activo, o en su defecto el más reciente
    $activePeriodId = 0;
    foreach ($periods as $p) {
        if ($p['status'] === 'activo') {
            $activePeriodId = (int)$p['id'];
            break;
        }
    }
    if ($activePeriodId === 0 && !empty($periods)) {
        $activePeriodId = (int)$periods[0]['id'];
    }

    $periodId = isset($filters['period_id']) ? (int)$filters['period_id'] : $activePeriodId;
    $careerId = (int)($filters['career_id'] ?? 0);
    $courseId = (int)($filters['course_id'] ?? 0);
    $sectionId = (int)($filters['section_id'] ?? 0);

    $careers = $pdo->query('SELECT id, name FROM careers ORDER BY name ASC')->fetchAll();

    // Cursos filtrados por la carrera elegida (si se seleccionó una)
    if ($careerId > 0) {
        $crsStmt = $pdo->prepare('
            SELECT DISTINCT c.id, c.name
            FROM courses c
            JOIN career_semester_courses csc ON csc.course_id = c.id
            JOIN career_semesters cs ON cs.id = csc.semester_id
            WHERE cs.career_id = ?
            ORDER BY c.name ASC
        ');
        $crsStmt->execute([$careerId]);
        $courses = $crsStmt->fetchAll();
    } else {
        $courses = $pdo->query('SELECT id, name FROM courses ORDER BY name ASC')->fetchAll();
    }

    // Secciones disponibles para el período y curso seleccionado
    $secQuery = 'SELECT cs.id, cs.code, c.name AS course_name FROM course_sections cs JOIN courses c ON c.id = cs.course_id WHERE cs.period_id = ?';
    $secParams = [$periodId];
    if ($courseId > 0) {
        $secQuery .= ' AND cs.course_id = ?';
        $secParams[] = $courseId;
    }
    $secQuery .= ' ORDER BY c.name ASC, cs.code ASC';
    $secStmt = $pdo->prepare($secQuery);
    $secStmt->execute($secParams);
    $availableSections = $secStmt->fetchAll();

    // 2. Consultar las secciones que corresponden a los filtros
    $secSql = '
        SELECT 
            cs.id AS section_id,
            cs.code AS section_code,
            cs.capacity,
            cs.schedule,
            cs.classroom,
            c.id AS course_id,
            c.name AS course_name,
            p.id AS period_id,
            p.name AS period_name,
            COALESCE(CONCAT(pr.first_name, " ", pr.last_name), cs.teacher, "Sin asignar") AS professor_name
        FROM course_sections cs
        JOIN courses c ON c.id = cs.course_id
        JOIN periods p ON p.id = cs.period_id
        LEFT JOIN professors pr ON pr.id = cs.professor_id
        WHERE cs.period_id = ?
    ';
    $params = [$periodId];

    if ($courseId > 0) {
        $secSql .= ' AND cs.course_id = ?';
        $params[] = $courseId;
    }

    if ($sectionId > 0) {
        $secSql .= ' AND cs.id = ?';
        $params[] = $sectionId;
    }

    if ($careerId > 0) {
        // Filtrar solo cursos que pertenezcan a la carrera
        $secSql .= ' AND cs.course_id IN (
            SELECT csc.course_id 
            FROM career_semester_courses csc
            JOIN career_semesters csem ON csem.id = csc.semester_id
            WHERE csem.career_id = ?
        )';
        $params[] = $careerId;
    }

    $secSql .= ' ORDER BY c.name ASC, cs.code ASC';
    $stmt = $pdo->prepare($secSql);
    $stmt->execute($params);
    $sectionsList = $stmt->fetchAll();

    // Obtener el nombre de la carrera seleccionada
    $selectedCareerName = 'Todas las carreras';
    if ($careerId > 0) {
        foreach ($careers as $car) {
            if ($car['id'] == $careerId) {
                $selectedCareerName = $car['name'];
                break;
            }
        }
    }

    $sectionsData = [];
    $comparativeRows = [];

    $globalTotals = [
        'sections_count' => count($sectionsList),
        'enrolled' => 0,
        'approved' => 0,
        'failed' => 0,
        'withdrawn' => 0,
        'scores_sum' => 0,
        'scores_count' => 0,
    ];

    foreach ($sectionsList as $sec) {
        // Obtener alumnos inscritos en esta sección
        $enrStmt = $pdo->prepare('
            SELECT 
                se.id AS enrollment_id,
                se.final_score,
                se.status AS enrollment_status,
                s.id AS student_id,
                s.first_name,
                s.last_name,
                ca.name AS student_career
            FROM section_enrollments se
            JOIN students s ON s.id = se.student_id
            JOIN careers ca ON ca.id = s.career_id
            WHERE se.section_id = ?
            ORDER BY s.last_name ASC, s.first_name ASC
        ');
        $enrStmt->execute([$sec['section_id']]);
        $enrolledRows = $enrStmt->fetchAll();

        $students = [];
        $scores = [];
        $approvedCount = 0;
        $failedCount = 0;
        $withdrawnCount = 0;

        foreach ($enrolledRows as $idx => $r) {
            $score = $r['final_score'];
            $stStatus = $r['enrollment_status'];
            $isReprobado = false;

            if ($stStatus === 'retirado') {
                $withdrawnCount++;
            } elseif ($score !== null && $score !== '') {
                $numScore = (float)$score;
                $scores[] = $numScore;
                if ($numScore >= $minScore) {
                    $approvedCount++;
                    $stStatus = 'aprobado';
                } else {
                    $failedCount++;
                    $stStatus = 'reprobado';
                    $isReprobado = true;
                }
            } elseif ($stStatus === 'reprobado') {
                $failedCount++;
                $isReprobado = true;
            } elseif ($stStatus === 'aprobado') {
                $approvedCount++;
            }

            $students[] = [
                'num' => $idx + 1,
                'student_id' => $r['student_id'],
                'student_code' => 'AC-' . str_pad((string)$r['student_id'], 5, '0', STR_PAD_LEFT),
                'full_name_sort' => trim($r['last_name'] . ', ' . $r['first_name']),
                'final_score' => $score,
                'status' => $stStatus,
                'is_reprobado' => $isReprobado
            ];
        }

        $enrolledCount = count($students);
        $avgScore = !empty($scores) ? round(array_sum($scores) / count($scores), 1) : null;
        $maxScore = !empty($scores) ? max($scores) : null;
        $minScoreVal = !empty($scores) ? min($scores) : null;
        $approvalRate = $enrolledCount > 0 ? round(($approvedCount / $enrolledCount) * 100, 1) : 0.0;

        $globalTotals['enrolled'] += $enrolledCount;
        $globalTotals['approved'] += $approvedCount;
        $globalTotals['failed'] += $failedCount;
        $globalTotals['withdrawn'] += $withdrawnCount;
        if (!empty($scores)) {
            $globalTotals['scores_sum'] += array_sum($scores);
            $globalTotals['scores_count'] += count($scores);
        }

        $sectionItem = [
            'section_id' => $sec['section_id'],
            'section_code' => $sec['section_code'],
            'course_id' => $sec['course_id'],
            'course_name' => $sec['course_name'],
            'period_name' => $sec['period_name'],
            'professor_name' => $sec['professor_name'],
            'career_name' => $selectedCareerName,
            'capacity' => $sec['capacity'],
            'schedule' => $sec['schedule'],
            'classroom' => $sec['classroom'],
            'students' => $students,
            'summary' => [
                'enrolled' => $enrolledCount,
                'approved' => $approvedCount,
                'failed' => $failedCount,
                'withdrawn' => $withdrawnCount,
                'average' => $avgScore,
                'max_score' => $maxScore,
                'min_score' => $minScoreVal,
                'approval_rate' => $approvalRate
            ]
        ];

        $sectionsData[] = $sectionItem;

        // Fila para el cuadro comparativo gerencial
        $comparativeRows[] = [
            'section_code' => $sec['section_code'],
            'course_name' => $sec['course_name'],
            'professor_name' => $sec['professor_name'],
            'enrolled' => $enrolledCount,
            'approved' => $approvedCount,
            'approval_rate' => $approvalRate,
            'average' => $avgScore !== null ? $avgScore : '—'
        ];
    }

    $globalAvg = $globalTotals['scores_count'] > 0
        ? round($globalTotals['scores_sum'] / $globalTotals['scores_count'], 1)
        : 0.0;
    $globalApprovalRate = $globalTotals['enrolled'] > 0
        ? round(($globalTotals['approved'] / $globalTotals['enrolled']) * 100, 1)
        : 0.0;

    $globalSummary = [
        'enrolled' => $globalTotals['enrolled'],
        'approved' => $globalTotals['approved'],
        'failed' => $globalTotals['failed'],
        'withdrawn' => $globalTotals['withdrawn'],
        'average' => $globalAvg,
        'approval_rate' => $globalApprovalRate
    ];

    return [
        'sections_data' => $sectionsData,
        'comparative_table' => $comparativeRows,
        'global_summary' => $globalSummary,
        'periods' => $periods,
        'careers' => $careers,
        'courses' => $courses,
        'available_sections' => $availableSections,
        'selected' => [
            'period_id' => $periodId,
            'career_id' => $careerId,
            'course_id' => $courseId,
            'section_id' => $sectionId,
            'career_name' => $selectedCareerName
        ]
    ];
}
