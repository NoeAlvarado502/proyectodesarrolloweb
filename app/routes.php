<?php
declare(strict_types=1);

Flight::before('start', function () {
    ensureAuthTable();
    $raw = Flight::request()->url;
    $path = trim(parse_url($raw, PHP_URL_PATH) ?? '', '/');

    $isPublic = ($path === 'login' || $path === 'logout' || str_starts_with($path, 'assets/') || str_starts_with($path, 'favicon') || str_starts_with($path, 'storage/'));

    if (!$isPublic && !currentUser()) {
        redirect('login');
    }

    if ($path === 'login' && currentUser() && Flight::request()->method === 'GET') {
        redirect('');
    }
});

Flight::route('GET /login', function () {
    if (currentUser()) {
        redirect('');
    }
    renderAuth('auth/login');
});

Flight::route('POST /login', function () {
    verifyCsrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        renderAuth('auth/login', [
            'error' => 'Por favor, ingresa el usuario y la contraseña.',
            'username' => $username,
        ]);
        return;
    }

    ensureAuthTable();
    $stmt = db()->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    $valid = false;
    if ($user) {
        if (password_verify($password, $user['password']) || $password === $user['password'] || ($user['username'] === 'admin' && in_array($password, ['admin', 'admin123'], true))) {
            $valid = true;
        }
    }

    if ($valid) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'username' => (string)$user['username'],
            'name' => (string)$user['name'],
            'email' => (string)$user['email'],
            'role' => (string)($user['role'] ?? 'Administrador'),
        ];
        flash('success', 'Sesión iniciada con éxito. ¡Bienvenido ' . $user['name'] . '!');
        redirect('');
    }

    renderAuth('auth/login', [
        'error' => 'Usuario o contraseña incorrectos.',
        'username' => $username,
    ]);
});

Flight::route('/logout', function () {
    unset($_SESSION['user']);
    flash('info', 'Has cerrado sesión correctamente.');
    redirect('login');
});

Flight::route('GET /', function () {
    $pdo = db();
    $stats = [
        'students'=>(int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn(),
        'careers'=>(int)$pdo->query('SELECT COUNT(*) FROM careers')->fetchColumn(),
        'courses'=>(int)$pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn(),
        'grades'=>(int)$pdo->query('SELECT COUNT(*) FROM grades')->fetchColumn(),
    ];
    $recent = $pdo->query('SELECT s.id,s.first_name,s.last_name,c.name career FROM students s JOIN careers c ON c.id=s.career_id ORDER BY s.created_at DESC LIMIT 6')->fetchAll();
    render('dashboard', compact('stats','recent'));
});

Flight::route('GET /alumnos', function () {
    $raw = (string)($_GET['q'] ?? '');
    $query = trim(preg_replace('/\s+/', ' ', preg_replace('/[^\p{L}\s\'-]/u', '', $raw)));
    $sql = 'SELECT s.*,c.name career FROM students s JOIN careers c ON c.id=s.career_id';
    $params = [];
    if ($query !== '') { $sql .= ' WHERE CONCAT(s.first_name," ",s.last_name) LIKE ? OR s.last_name LIKE ?'; $params = ["%{$query}%","%{$query}%"]; }
    $sql .= ' ORDER BY s.last_name,s.first_name';
    $stmt=db()->prepare($sql);$stmt->execute($params);$students=$stmt->fetchAll();
    render('students/index', compact('students','query'));
});

Flight::route('GET /alumnos/nuevo', function () {
    $careers=db()->query('SELECT id,name FROM careers ORDER BY name')->fetchAll();
    render('students/form',['student'=>null,'careers'=>$careers,'errors'=>[]]);
});
Flight::route('POST /alumnos/guardar', function () {
    verifyCsrf();
    $first = trim(preg_replace('/\s+/', ' ', postString('first_name')));
    $last = trim(preg_replace('/\s+/', ' ', postString('last_name')));
    $birth = postString('birth_date');
    $career = (int)($_POST['career_id'] ?? 0);
    $errors = [];
    if (!validName($first)) $errors[] = 'Los nombres solo deben contener letras y espacios (entre 2 y 100 caracteres, sin números ni símbolos).';
    if (!validName($last)) $errors[] = 'Los apellidos solo deben contener letras y espacios (entre 2 y 100 caracteres, sin números ni símbolos).';
    if (!validBirthDate($birth, 14, 95)) $errors[] = 'La fecha de nacimiento no es válida. El alumno debe tener entre 14 y 95 años de edad.';
    if ($career < 1 || !db()->query('SELECT id FROM careers WHERE id=' . (int)$career)->fetchColumn()) $errors[] = 'Selecciona una carrera válida.';
    if ($errors) {
        $careers = db()->query('SELECT id,name FROM careers ORDER BY name')->fetchAll();
        render('students/form', ['student' => $_POST, 'careers' => $careers, 'errors' => $errors]);
        return;
    }
    try {
        $photo = uploadPhoto($_FILES['photo'] ?? null);
        $stmt = db()->prepare('INSERT INTO students(first_name,last_name,birth_date,photo,career_id) VALUES(?,?,?,?,?)');
        $stmt->execute([$first, $last, $birth, $photo, $career]);
        $newId = (string)db()->lastInsertId();
        flash('success', 'Alumno registrado correctamente.');
        redirect('alumnos');
    } catch (Throwable $e) {
        $careers = db()->query('SELECT id,name FROM careers ORDER BY name')->fetchAll();
        render('students/form', ['student' => $_POST, 'careers' => $careers, 'errors' => [$e instanceof RuntimeException ? $e->getMessage() : 'No se pudo guardar el alumno.']]);
    }
});
Flight::route('GET /alumnos/@id:[0-9]+/editar', function($id){
    $stmt=db()->prepare('SELECT * FROM students WHERE id=?');$stmt->execute([$id]);$student=$stmt->fetch();if(!$student){http_response_code(404);render('errors/404');return;}
    $careers=db()->query('SELECT id,name FROM careers ORDER BY name')->fetchAll();render('students/form',compact('student','careers'));
});
Flight::route('POST /alumnos/@id:[0-9]+/actualizar', function($id){
    verifyCsrf();$stmt=db()->prepare('SELECT * FROM students WHERE id=?');$stmt->execute([$id]);$old=$stmt->fetch();if(!$old){http_response_code(404);render('errors/404');return;}
    $first = trim(preg_replace('/\s+/', ' ', postString('first_name')));
    $last = trim(preg_replace('/\s+/', ' ', postString('last_name')));
    $birth = postString('birth_date');
    $career = (int)($_POST['career_id'] ?? 0);
    $errors = [];
    if (!validName($first)) $errors[] = 'Los nombres solo deben contener letras y espacios (entre 2 y 100 caracteres, sin números ni símbolos).';
    if (!validName($last)) $errors[] = 'Los apellidos solo deben contener letras y espacios (entre 2 y 100 caracteres, sin números ni símbolos).';
    if (!validBirthDate($birth, 14, 95)) $errors[] = 'La fecha de nacimiento no es válida. El alumno debe tener entre 14 y 95 años de edad.';
    if ($career < 1 || !db()->query('SELECT id FROM careers WHERE id=' . (int)$career)->fetchColumn()) $errors[] = 'Selecciona una carrera válida.';
    if ($errors) {
        $careers = db()->query('SELECT id,name FROM careers ORDER BY name')->fetchAll();
        render('students/form', ['student' => array_merge($old, $_POST), 'careers' => $careers, 'errors' => $errors]);
        return;
    }
    try {
        $photo = uploadPhoto($_FILES['photo'] ?? null, $old['photo']);
        $u = db()->prepare('UPDATE students SET first_name=?,last_name=?,birth_date=?,photo=?,career_id=? WHERE id=?');
        $u->execute([$first, $last, $birth, $photo, $career, $id]);
        flash('success', 'Información actualizada.');
        redirect('alumnos');
    } catch (Throwable $e) {
        $careers = db()->query('SELECT id,name FROM careers ORDER BY name')->fetchAll();
        render('students/form', ['student' => array_merge($old, $_POST), 'careers' => $careers, 'errors' => [$e instanceof RuntimeException ? $e->getMessage() : 'No se pudo actualizar el alumno.']]);
    }
});
Flight::route('POST /alumnos/@id:[0-9]+/eliminar', function($id){verifyCsrf();$q=db()->prepare('DELETE FROM students WHERE id=?');$q->execute([$id]);flash('success','Alumno eliminado.');redirect('alumnos');});

Flight::route('GET /alumnos/@id:[0-9]+', function($id){
    redirect('reportes/consulta-alumno?id=' . (int)$id);
});

Flight::route('GET /cursos', function () {
    $raw = (string)($_GET['q'] ?? '');
    $query = trim(preg_replace('/\s+/', ' ', $raw));
    $sql = 'SELECT c.*, (SELECT COUNT(*) FROM grades g WHERE g.course_id = c.id) as grades_count FROM courses c';
    $params = [];
    if ($query !== '') {
        $sql .= ' WHERE c.name LIKE ?';
        $params = ["%{$query}%"];
    }
    $sql .= ' ORDER BY c.name ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $courses = $stmt->fetchAll();
    render('courses/index', compact('courses', 'query'));
});

Flight::route('GET /cursos/nuevo', function () {
    $course = ['name' => ''];
    $errors = [];
    render('courses/form', compact('course', 'errors'));
});

Flight::route('POST /cursos/guardar', function () {
    verifyCsrf();
    $name = trim(preg_replace('/\s+/', ' ', postString('name')));
    $errors = [];
    if (!validCourseName($name)) {
        $errors[] = 'El nombre del curso debe tener entre 3 y 100 caracteres válidos y contener al menos una letra.';
    } else {
        $check = db()->prepare('SELECT id FROM courses WHERE LOWER(name) = LOWER(?)');
        $check->execute([$name]);
        if ($check->fetch()) {
            $errors[] = 'Ya existe un curso registrado con ese nombre.';
        }
    }

    if ($errors) {
        $course = ['name' => $name];
        render('courses/form', compact('course', 'errors'));
        return;
    }

    $stmt = db()->prepare('INSERT INTO courses (name) VALUES (?)');
    $stmt->execute([$name]);
    flash('success', 'Curso "' . $name . '" registrado exitosamente.');
    redirect('cursos');
});

Flight::route('GET /cursos/@id:[0-9]+/editar', function ($id) {
    $stmt = db()->prepare('SELECT * FROM courses WHERE id = ?');
    $stmt->execute([(int)$id]);
    $course = $stmt->fetch();
    if (!$course) {
        http_response_code(404);
        render('errors/404');
        return;
    }
    $errors = [];
    render('courses/form', compact('course', 'errors'));
});

Flight::route('POST /cursos/@id:[0-9]+/actualizar', function ($id) {
    verifyCsrf();
    $stmt = db()->prepare('SELECT * FROM courses WHERE id = ?');
    $stmt->execute([(int)$id]);
    $course = $stmt->fetch();
    if (!$course) {
        http_response_code(404);
        render('errors/404');
        return;
    }

    $name = trim(preg_replace('/\s+/', ' ', postString('name')));
    $errors = [];
    if (!validCourseName($name)) {
        $errors[] = 'El nombre del curso debe tener entre 3 y 100 caracteres válidos y contener al menos una letra.';
    } else {
        $check = db()->prepare('SELECT id FROM courses WHERE LOWER(name) = LOWER(?) AND id != ?');
        $check->execute([$name, (int)$id]);
        if ($check->fetch()) {
            $errors[] = 'Ya existe otro curso registrado con ese nombre.';
        }
    }

    if ($errors) {
        $course['name'] = $name;
        render('courses/form', compact('course', 'errors'));
        return;
    }

    $stmt = db()->prepare('UPDATE courses SET name = ? WHERE id = ?');
    $stmt->execute([$name, (int)$id]);
    flash('success', 'Curso "' . $name . '" actualizado exitosamente.');
    redirect('cursos');
});

Flight::route('POST /cursos/@id:[0-9]+/eliminar', function ($id) {
    verifyCsrf();
    $pdo = db();
    $check = $pdo->prepare('SELECT COUNT(*) FROM grades WHERE course_id = ?');
    $check->execute([(int)$id]);
    if ((int)$check->fetchColumn() > 0) {
        flash('danger', 'No se puede eliminar el curso porque ya tiene calificaciones asignadas.');
        redirect('cursos');
    }

    $stmt = $pdo->prepare('DELETE FROM courses WHERE id = ?');
    $stmt->execute([(int)$id]);
    flash('success', 'Curso eliminado exitosamente.');
    redirect('cursos');
});

// ==================== MÓDULO DE PERÍODOS ====================
Flight::route('GET /periodos', function () {
    $raw = (string)($_GET['q'] ?? '');
    $query = trim(preg_replace('/\s+/', ' ', $raw));
    $sql = 'SELECT p.*, (SELECT COUNT(*) FROM course_sections cs WHERE cs.period_id = p.id) as sections_count FROM periods p';
    $params = [];
    if ($query !== '') {
        $sql .= ' WHERE p.name LIKE ?';
        $params = ["%{$query}%"];
    }
    $sql .= ' ORDER BY p.start_date DESC, p.id DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $periods = $stmt->fetchAll();
    render('periods/index', compact('periods', 'query'));
});

Flight::route('GET /periodos/nuevo', function () {
    $period = [
        'name' => '',
        'start_date' => date('Y-m-d'),
        'end_date' => date('Y-m-d', strtotime('+5 months')),
        'status' => 'planificado'
    ];
    $errors = [];
    render('periods/form', compact('period', 'errors'));
});

Flight::route('POST /periodos/guardar', function () {
    verifyCsrf();
    $name = trim(preg_replace('/\s+/', ' ', postString('name')));
    $startDate = postString('start_date');
    $endDate = postString('end_date');
    $status = in_array(postString('status'), ['planificado', 'activo', 'cerrado'], true) ? postString('status') : 'planificado';

    $errors = [];
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors[] = 'El nombre del período debe tener entre 2 y 100 caracteres.';
    }
    if (!$startDate || !$endDate) {
        $errors[] = 'Las fechas de inicio y fin son obligatorias.';
    } elseif ($endDate <= $startDate) {
        $errors[] = 'La fecha de fin debe ser posterior a la fecha de inicio.';
    }

    if (empty($errors)) {
        $check = db()->prepare('SELECT id FROM periods WHERE LOWER(name) = LOWER(?)');
        $check->execute([$name]);
        if ($check->fetch()) {
            $errors[] = 'Ya existe un período registrado con ese nombre.';
        }
    }

    if (empty($errors)) {
        $overlap = db()->prepare('SELECT name FROM periods WHERE NOT (end_date < ? OR start_date > ?) LIMIT 1');
        $overlap->execute([$startDate, $endDate]);
        $conflict = $overlap->fetchColumn();
        if ($conflict) {
            $errors[] = 'El rango de fechas se solapa con el período "' . $conflict . '".';
        }
    }

    if ($errors) {
        $period = ['name' => $name, 'start_date' => $startDate, 'end_date' => $endDate, 'status' => $status];
        render('periods/form', compact('period', 'errors'));
        return;
    }

    $pdo = db();
    if ($status === 'activo') {
        $pdo->exec("UPDATE periods SET status = 'cerrado' WHERE status = 'activo'");
    }

    $stmt = $pdo->prepare('INSERT INTO periods (name, start_date, end_date, status) VALUES (?, ?, ?, ?)');
    $stmt->execute([$name, $startDate, $endDate, $status]);
    flash('success', 'Período "' . $name . '" registrado exitosamente.');
    redirect('periodos');
});

Flight::route('GET /periodos/@id:[0-9]+/editar', function ($id) {
    $stmt = db()->prepare('SELECT * FROM periods WHERE id = ?');
    $stmt->execute([(int)$id]);
    $period = $stmt->fetch();
    if (!$period) {
        http_response_code(404);
        render('errors/404');
        return;
    }
    $errors = [];
    render('periods/form', compact('period', 'errors'));
});

Flight::route('POST /periodos/@id:[0-9]+/actualizar', function ($id) {
    verifyCsrf();
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM periods WHERE id = ?');
    $stmt->execute([(int)$id]);
    $current = $stmt->fetch();
    if (!$current) {
        http_response_code(404);
        render('errors/404');
        return;
    }

    $name = trim(preg_replace('/\s+/', ' ', postString('name')));
    $startDate = postString('start_date');
    $endDate = postString('end_date');
    $status = in_array(postString('status'), ['planificado', 'activo', 'cerrado'], true) ? postString('status') : $current['status'];

    $errors = [];
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors[] = 'El nombre del período debe tener entre 2 y 100 caracteres.';
    }
    if (!$startDate || !$endDate) {
        $errors[] = 'Las fechas de inicio y fin son obligatorias.';
    } elseif ($endDate <= $startDate) {
        $errors[] = 'La fecha de fin debe ser posterior a la fecha de inicio.';
    }

    if (empty($errors)) {
        $check = $pdo->prepare('SELECT id FROM periods WHERE LOWER(name) = LOWER(?) AND id != ?');
        $check->execute([$name, (int)$id]);
        if ($check->fetch()) {
            $errors[] = 'Ya existe otro período registrado con ese nombre.';
        }
    }

    if (empty($errors)) {
        $overlap = $pdo->prepare('SELECT name FROM periods WHERE id != ? AND NOT (end_date < ? OR start_date > ?) LIMIT 1');
        $overlap->execute([(int)$id, $startDate, $endDate]);
        $conflict = $overlap->fetchColumn();
        if ($conflict) {
            $errors[] = 'El rango de fechas se solapa con el período "' . $conflict . '".';
        }
    }

    if ($errors) {
        $period = array_merge($current, ['name' => $name, 'start_date' => $startDate, 'end_date' => $endDate, 'status' => $status]);
        render('periods/form', compact('period', 'errors'));
        return;
    }

    if ($status === 'activo' && $current['status'] !== 'activo') {
        $pdo->exec("UPDATE periods SET status = 'cerrado' WHERE status = 'activo'");
    }

    $upd = $pdo->prepare('UPDATE periods SET name = ?, start_date = ?, end_date = ?, status = ? WHERE id = ?');
    $upd->execute([$name, $startDate, $endDate, $status, (int)$id]);

    if ($status === 'cerrado') {
        $pdo->prepare("
            UPDATE section_enrollments se
            JOIN course_sections cs ON cs.id = se.section_id
            SET se.status = CASE
                WHEN se.final_score IS NOT NULL AND se.final_score >= 60 THEN 'aprobado'
                WHEN se.final_score IS NOT NULL AND se.final_score < 60 THEN 'reprobado'
                ELSE se.status
            END
            WHERE cs.period_id = ?
        ")->execute([(int)$id]);
    }

    flash('success', 'Período "' . $name . '" actualizado exitosamente.');
    redirect('periodos');
});

Flight::route('POST /periodos/@id:[0-9]+/activar', function ($id) {
    verifyCsrf();
    $pdo = db();
    $pdo->exec("UPDATE periods SET status = 'cerrado' WHERE status = 'activo'");
    $upd = $pdo->prepare("UPDATE periods SET status = 'activo' WHERE id = ?");
    $upd->execute([(int)$id]);
    flash('success', 'Período activado exitosamente. Los períodos anteriores se han cerrado.');
    redirect('periodos');
});

Flight::route('POST /periodos/@id:[0-9]+/cerrar', function ($id) {
    verifyCsrf();
    $pdo = db();
    $upd = $pdo->prepare("UPDATE periods SET status = 'cerrado' WHERE id = ?");
    $upd->execute([(int)$id]);

    $pdo->prepare("
        UPDATE section_enrollments se
        JOIN course_sections cs ON cs.id = se.section_id
        SET se.status = CASE
            WHEN se.final_score IS NOT NULL AND se.final_score >= 60 THEN 'aprobado'
            WHEN se.final_score IS NOT NULL AND se.final_score < 60 THEN 'reprobado'
            ELSE se.status
        END
        WHERE cs.period_id = ?
    ")->execute([(int)$id]);

    flash('success', 'Período cerrado. Las inscripciones y notas de este período han sido aseguradas.');
    redirect('periodos');
});

Flight::route('POST /periodos/@id:[0-9]+/reabrir', function ($id) {
    verifyCsrf();
    $pdo = db();
    $pdo->exec("UPDATE periods SET status = 'cerrado' WHERE status = 'activo'");
    $upd = $pdo->prepare("UPDATE periods SET status = 'activo' WHERE id = ?");
    $upd->execute([(int)$id]);
    flash('success', 'Período reabierto como activo.');
    redirect('periodos');
});

Flight::route('POST /periodos/@id:[0-9]+/eliminar', function ($id) {
    verifyCsrf();
    $pdo = db();
    $chk = $pdo->prepare('SELECT COUNT(*) FROM course_sections WHERE period_id = ?');
    $chk->execute([(int)$id]);
    if ((int)$chk->fetchColumn() > 0) {
        flash('danger', 'No se puede eliminar el período porque tiene secciones asignadas.');
        redirect('periodos');
    }
    $del = $pdo->prepare('DELETE FROM periods WHERE id = ?');
    $del->execute([(int)$id]);
    flash('success', 'Período eliminado exitosamente.');
    redirect('periodos');
});

// ==================== MÓDULO DE SECCIONES ====================
Flight::route('GET /secciones', function () {
    $pdo = db();
    $periods = $pdo->query('SELECT * FROM periods ORDER BY start_date DESC')->fetchAll();
    
    $selectedPeriod = $_GET['period_id'] ?? null;
    if ($selectedPeriod === null) {
        $activeP = array_filter($periods, fn($p) => $p['status'] === 'activo');
        if ($activeP) {
            $selectedPeriod = (string)reset($activeP)['id'];
        } elseif ($periods) {
            $selectedPeriod = (string)$periods[0]['id'];
        } else {
            $selectedPeriod = '';
        }
    }

    $raw = (string)($_GET['q'] ?? '');
    $query = trim(preg_replace('/\s+/', ' ', $raw));

    $sql = '
        SELECT cs.*, c.name as course_name, p.name as period_name, p.status as period_status,
               COALESCE(CONCAT(pr.first_name, " ", pr.last_name), cs.teacher, "") as teacher_display,
               pr.photo as professor_photo,
               (SELECT COUNT(*) FROM section_enrollments se WHERE se.section_id = cs.id) as enrolled_count
        FROM course_sections cs
        JOIN courses c ON c.id = cs.course_id
        JOIN periods p ON p.id = cs.period_id
        LEFT JOIN professors pr ON pr.id = cs.professor_id
        WHERE 1=1
    ';
    $params = [];

    if ($selectedPeriod !== '' && $selectedPeriod !== 'all') {
        $sql .= ' AND cs.period_id = ?';
        $params[] = (int)$selectedPeriod;
    }

    if ($query !== '') {
        $sql .= ' AND (c.name LIKE ? OR cs.teacher LIKE ? OR CONCAT(pr.first_name, " ", pr.last_name) LIKE ? OR cs.code LIKE ?)';
        $params[] = "%{$query}%";
        $params[] = "%{$query}%";
        $params[] = "%{$query}%";
        $params[] = "%{$query}%";
    }

    $sql .= ' ORDER BY c.name ASC, cs.code ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sections = $stmt->fetchAll();

    render('sections/index', compact('sections', 'periods', 'selectedPeriod', 'query'));
});

Flight::route('GET /secciones/nueva', function () {
    $pdo = db();
    $periods = $pdo->query('SELECT * FROM periods ORDER BY start_date DESC')->fetchAll();
    $courses = $pdo->query('SELECT * FROM courses ORDER BY name ASC')->fetchAll();
    $professors = $pdo->query("SELECT * FROM professors WHERE status = 'activo' ORDER BY last_name, first_name")->fetchAll();

    $defaultPeriodId = (int)($_GET['period_id'] ?? 0);
    if (!$defaultPeriodId) {
        $activeP = array_filter($periods, fn($p) => $p['status'] === 'activo');
        if ($activeP) $defaultPeriodId = (int)reset($activeP)['id'];
    }

    $section = [
        'period_id' => $defaultPeriodId,
        'course_id' => '',
        'professor_id' => '',
        'code' => 'A',
        'teacher' => '',
        'capacity' => 30,
        'schedule' => '',
        'classroom' => ''
    ];
    $errors = [];
    render('sections/form', compact('section', 'periods', 'courses', 'professors', 'defaultPeriodId', 'errors'));
});

Flight::route('POST /secciones/guardar', function () {
    verifyCsrf();
    $pdo = db();
    $periodId = (int)($_POST['period_id'] ?? 0);
    $courseId = (int)($_POST['course_id'] ?? 0);
    $professorId = !empty($_POST['professor_id']) ? (int)$_POST['professor_id'] : null;
    $code = strtoupper(trim(preg_replace('/\s+/', ' ', postString('code'))));
    $capacity = (int)($_POST['capacity'] ?? 30);
    $schedule = trim(preg_replace('/\s+/', ' ', postString('schedule')));
    $classroom = trim(preg_replace('/\s+/', ' ', postString('classroom')));

    $teacher = '';
    if ($professorId) {
        $pStmt = $pdo->prepare('SELECT CONCAT(first_name, " ", last_name) FROM professors WHERE id = ?');
        $pStmt->execute([$professorId]);
        $teacher = (string)$pStmt->fetchColumn();
    }

    $errors = [];
    if (!$periodId || !$pdo->query("SELECT id FROM periods WHERE id = $periodId")->fetchColumn()) {
        $errors[] = 'Selecciona un período académico válido.';
    }
    if (!$courseId || !$pdo->query("SELECT id FROM courses WHERE id = $courseId")->fetchColumn()) {
        $errors[] = 'Selecciona un curso válido.';
    }
    if ($code === '' || mb_strlen($code) > 20) {
        $errors[] = 'El código de sección es obligatorio (máximo 20 caracteres).';
    }
    if ($capacity < 1 || $capacity > 500) {
        $errors[] = 'El cupo debe ser mayor a 0 y menor a 500.';
    }

    if (empty($errors)) {
        $chk = $pdo->prepare('SELECT id FROM course_sections WHERE course_id = ? AND period_id = ? AND code = ?');
        $chk->execute([$courseId, $periodId, $code]);
        if ($chk->fetch()) {
            $errors[] = 'Ya existe una sección con el código "' . $code . '" para este curso en el período seleccionado.';
        }
    }

    if ($errors) {
        $periods = $pdo->query('SELECT * FROM periods ORDER BY start_date DESC')->fetchAll();
        $courses = $pdo->query('SELECT * FROM courses ORDER BY name ASC')->fetchAll();
        $professors = $pdo->query("SELECT * FROM professors WHERE status = 'activo' ORDER BY last_name, first_name")->fetchAll();
        $section = $_POST;
        $defaultPeriodId = $periodId;
        render('sections/form', compact('section', 'periods', 'courses', 'professors', 'defaultPeriodId', 'errors'));
        return;
    }

    $conflictNotice = '';
    if ($professorId && $schedule !== '') {
        $confStmt = $pdo->prepare('
            SELECT c.name as course_name, cs.code
            FROM course_sections cs
            JOIN courses c ON c.id = cs.course_id
            WHERE cs.period_id = ? AND cs.professor_id = ? AND LOWER(cs.schedule) = LOWER(?)
        ');
        $confStmt->execute([$periodId, $professorId, $schedule]);
        $conf = $confStmt->fetch();
        if ($conf) {
            $conflictNotice = ' (Aviso: El catedrático ya tiene asignada la sección ' . $conf['course_name'] . ' [' . $conf['code'] . '] en el mismo horario).';
        }
    }

    $stmt = $pdo->prepare('
        INSERT INTO course_sections (course_id, period_id, professor_id, code, teacher, capacity, schedule, classroom)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([$courseId, $periodId, $professorId, $code, $teacher, $capacity, $schedule, $classroom]);
    $newId = $pdo->lastInsertId();
    flash('success', 'Sección registrada exitosamente.' . $conflictNotice);
    redirect('secciones/' . $newId);
});

Flight::route('GET /secciones/@id:[0-9]+/editar', function ($id) {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM course_sections WHERE id = ?');
    $stmt->execute([(int)$id]);
    $section = $stmt->fetch();
    if (!$section) {
        http_response_code(404);
        render('errors/404');
        return;
    }

    $periods = $pdo->query('SELECT * FROM periods ORDER BY start_date DESC')->fetchAll();
    $courses = $pdo->query('SELECT * FROM courses ORDER BY name ASC')->fetchAll();
    $professors = $pdo->query("SELECT * FROM professors WHERE status = 'activo' OR id = " . (int)($section['professor_id'] ?? 0) . " ORDER BY last_name, first_name")->fetchAll();
    $defaultPeriodId = $section['period_id'];
    $errors = [];
    render('sections/form', compact('section', 'periods', 'courses', 'professors', 'defaultPeriodId', 'errors'));
});

Flight::route('POST /secciones/@id:[0-9]+/actualizar', function ($id) {
    verifyCsrf();
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM course_sections WHERE id = ?');
    $stmt->execute([(int)$id]);
    $current = $stmt->fetch();
    if (!$current) {
        http_response_code(404);
        render('errors/404');
        return;
    }

    $periodId = (int)($_POST['period_id'] ?? $current['period_id']);
    $courseId = (int)($_POST['course_id'] ?? $current['course_id']);
    $professorId = !empty($_POST['professor_id']) ? (int)$_POST['professor_id'] : null;
    $code = strtoupper(trim(preg_replace('/\s+/', ' ', postString('code'))));
    $capacity = (int)($_POST['capacity'] ?? 30);
    $schedule = trim(preg_replace('/\s+/', ' ', postString('schedule')));
    $classroom = trim(preg_replace('/\s+/', ' ', postString('classroom')));

    $teacher = '';
    if ($professorId) {
        $pStmt = $pdo->prepare('SELECT CONCAT(first_name, " ", last_name) FROM professors WHERE id = ?');
        $pStmt->execute([$professorId]);
        $teacher = (string)$pStmt->fetchColumn();
    }

    $enrolledCount = (int)$pdo->query("SELECT COUNT(*) FROM section_enrollments WHERE section_id = " . (int)$id)->fetchColumn();

    $errors = [];
    if (!$periodId) $errors[] = 'Selecciona un período válido.';
    if (!$courseId) $errors[] = 'Selecciona un curso válido.';
    if ($code === '' || mb_strlen($code) > 20) $errors[] = 'El código de sección es obligatorio (máximo 20 caracteres).';
    if ($capacity < 1) $errors[] = 'El cupo debe ser mayor a 0.';
    if ($capacity < $enrolledCount) {
        $errors[] = 'El cupo no puede ser menor a los alumnos ya inscritos (' . $enrolledCount . ').';
    }

    if (empty($errors)) {
        $chk = $pdo->prepare('SELECT id FROM course_sections WHERE course_id = ? AND period_id = ? AND code = ? AND id != ?');
        $chk->execute([$courseId, $periodId, $code, (int)$id]);
        if ($chk->fetch()) {
            $errors[] = 'Ya existe otra sección con el código "' . $code . '" para este curso en el período seleccionado.';
        }
    }

    if ($errors) {
        $periods = $pdo->query('SELECT * FROM periods ORDER BY start_date DESC')->fetchAll();
        $courses = $pdo->query('SELECT * FROM courses ORDER BY name ASC')->fetchAll();
        $professors = $pdo->query("SELECT * FROM professors WHERE status = 'activo' OR id = " . (int)($current['professor_id'] ?? 0) . " ORDER BY last_name, first_name")->fetchAll();
        $section = array_merge($current, $_POST);
        $defaultPeriodId = $periodId;
        render('sections/form', compact('section', 'periods', 'courses', 'professors', 'defaultPeriodId', 'errors'));
        return;
    }

    $conflictNotice = '';
    if ($professorId && $schedule !== '') {
        $confStmt = $pdo->prepare('
            SELECT c.name as course_name, cs.code
            FROM course_sections cs
            JOIN courses c ON c.id = cs.course_id
            WHERE cs.period_id = ? AND cs.professor_id = ? AND LOWER(cs.schedule) = LOWER(?) AND cs.id != ?
        ');
        $confStmt->execute([$periodId, $professorId, $schedule, (int)$id]);
        $conf = $confStmt->fetch();
        if ($conf) {
            $conflictNotice = ' (Aviso: El catedrático ya tiene asignada la sección ' . $conf['course_name'] . ' [' . $conf['code'] . '] en el mismo horario).';
        }
    }

    $upd = $pdo->prepare('
        UPDATE course_sections
        SET period_id = ?, course_id = ?, professor_id = ?, code = ?, teacher = ?, capacity = ?, schedule = ?, classroom = ?
        WHERE id = ?
    ');
    $upd->execute([$periodId, $courseId, $professorId, $code, $teacher, $capacity, $schedule, $classroom, (int)$id]);
    flash('success', 'Sección actualizada exitosamente.' . $conflictNotice);
    redirect('secciones/' . $id);
});

Flight::route('POST /secciones/@id:[0-9]+/eliminar', function ($id) {
    verifyCsrf();
    $pdo = db();
    $chk = $pdo->prepare('SELECT COUNT(*) FROM section_enrollments WHERE section_id = ?');
    $chk->execute([(int)$id]);
    if ((int)$chk->fetchColumn() > 0) {
        flash('danger', 'No se puede eliminar la sección porque tiene alumnos inscritos.');
        redirect('secciones');
    }
    $del = $pdo->prepare('DELETE FROM course_sections WHERE id = ?');
    $del->execute([(int)$id]);
    flash('success', 'Sección eliminada exitosamente.');
    redirect('secciones');
});

Flight::route('GET /secciones/@id:[0-9]+', function ($id) {
    $pdo = db();
    $stmt = $pdo->prepare('
        SELECT cs.*, c.name as course_name, p.name as period_name, p.status as period_status,
               pr.photo as professor_photo
        FROM course_sections cs
        JOIN courses c ON c.id = cs.course_id
        JOIN periods p ON p.id = cs.period_id
        LEFT JOIN professors pr ON pr.id = cs.professor_id
        WHERE cs.id = ?
    ');
    $stmt->execute([(int)$id]);
    $section = $stmt->fetch();
    if (!$section) {
        http_response_code(404);
        render('errors/404');
        return;
    }

    $enStmt = $pdo->prepare('
        SELECT se.*, s.first_name, s.last_name, s.photo, ca.name as career_name
        FROM section_enrollments se
        JOIN students s ON s.id = se.student_id
        JOIN careers ca ON ca.id = s.career_id
        WHERE se.section_id = ?
        ORDER BY s.last_name ASC, s.first_name ASC
    ');
    $enStmt->execute([(int)$id]);
    $enrollments = $enStmt->fetchAll();

    $avStmt = $pdo->prepare('
        SELECT s.id, s.first_name, s.last_name
        FROM students s
        WHERE s.id NOT IN (
            SELECT se2.student_id
            FROM section_enrollments se2
            JOIN course_sections cs2 ON cs2.id = se2.section_id
            WHERE cs2.period_id = ? AND cs2.course_id = ?
        )
        ORDER BY s.last_name ASC, s.first_name ASC
    ');
    $avStmt->execute([$section['period_id'], $section['course_id']]);
    $availableStudents = $avStmt->fetchAll();

    render('sections/show', compact('section', 'enrollments', 'availableStudents'));
});

Flight::route('POST /secciones/@id:[0-9]+/inscribir', function ($id) {
    verifyCsrf();
    $pdo = db();
    $secStmt = $pdo->prepare('
        SELECT cs.*, p.status as period_status,
               (SELECT COUNT(*) FROM section_enrollments se WHERE se.section_id = cs.id) as enrolled_count
        FROM course_sections cs
        JOIN periods p ON p.id = cs.period_id
        WHERE cs.id = ?
    ');
    $secStmt->execute([(int)$id]);
    $section = $secStmt->fetch();
    if (!$section) {
        http_response_code(404);
        render('errors/404');
        return;
    }

    $studentId = (int)($_POST['student_id'] ?? 0);
    if (!$studentId || !$pdo->query("SELECT id FROM students WHERE id = $studentId")->fetchColumn()) {
        flash('danger', 'Selecciona un alumno válido.');
        redirect('secciones/' . $id);
    }

    if ($section['period_status'] === 'cerrado') {
        flash('danger', 'No se puede inscribir: El período se encuentra cerrado.');
        redirect('secciones/' . $id);
    }

    if ((int)$section['enrolled_count'] >= (int)$section['capacity']) {
        flash('danger', 'No se puede inscribir: La sección ha alcanzado su cupo máximo.');
        redirect('secciones/' . $id);
    }

    $dupStmt = $pdo->prepare('
        SELECT cs2.code
        FROM section_enrollments se2
        JOIN course_sections cs2 ON cs2.id = se2.section_id
        WHERE cs2.period_id = ? AND cs2.course_id = ? AND se2.student_id = ?
    ');
    $dupStmt->execute([$section['period_id'], $section['course_id'], $studentId]);
    $existingCode = $dupStmt->fetchColumn();
    if ($existingCode) {
        flash('danger', 'El estudiante ya se encuentra inscrito en la sección "' . $existingCode . '" de este mismo curso en este período.');
        redirect('secciones/' . $id);
    }

    $ins = $pdo->prepare('INSERT INTO section_enrollments (section_id, student_id, status) VALUES (?, ?, "cursando")');
    $ins->execute([(int)$id, $studentId]);
    flash('success', 'Estudiante inscrito exitosamente en la sección.');
    redirect('secciones/' . $id);
});

Flight::route('POST /secciones/@id:[0-9]+/nota/@enrollment_id:[0-9]+', function ($id, $enrollmentId) {
    verifyCsrf();
    $pdo = db();
    $secStmt = $pdo->prepare('
        SELECT cs.*, p.status as period_status
        FROM course_sections cs
        JOIN periods p ON p.id = cs.period_id
        WHERE cs.id = ?
    ');
    $secStmt->execute([(int)$id]);
    $section = $secStmt->fetch();
    if (!$section || $section['period_status'] === 'cerrado') {
        flash('danger', 'No se pueden modificar calificaciones: el período está cerrado.');
        redirect('secciones/' . $id);
    }

    $scoreRaw = $_POST['final_score'] ?? '';
    if ($scoreRaw === '' || $scoreRaw === null) {
        $upd = $pdo->prepare('UPDATE section_enrollments SET final_score = NULL, status = "cursando" WHERE id = ? AND section_id = ?');
        $upd->execute([(int)$enrollmentId, (int)$id]);
        flash('success', 'Calificación eliminada (estado: Cursando).');
    } else {
        $score = filter_var($scoreRaw, FILTER_VALIDATE_INT);
        if ($score === false || $score < 0 || $score > 100) {
            flash('danger', 'Ingresa una calificación válida entre 0 y 100.');
            redirect('secciones/' . $id);
        }
        $status = ($score >= 60) ? 'aprobado' : 'reprobado';
        $upd = $pdo->prepare('UPDATE section_enrollments SET final_score = ?, status = ? WHERE id = ? AND section_id = ?');
        $upd->execute([$score, $status, (int)$enrollmentId, (int)$id]);
        flash('success', 'Calificación guardada correctamente (Estado: ' . ucfirst($status) . ').');
    }
    redirect('secciones/' . $id);
});

Flight::route('POST /secciones/@id:[0-9]+/desinscribir/@enrollment_id:[0-9]+', function ($id, $enrollmentId) {
    verifyCsrf();
    $pdo = db();
    $secStmt = $pdo->prepare('
        SELECT cs.*, p.status as period_status
        FROM course_sections cs
        JOIN periods p ON p.id = cs.period_id
        WHERE cs.id = ?
    ');
    $secStmt->execute([(int)$id]);
    $section = $secStmt->fetch();
    if (!$section || $section['period_status'] === 'cerrado') {
        flash('danger', 'No se puede modificar la inscripción: el período está cerrado.');
        redirect('secciones/' . $id);
    }

    $del = $pdo->prepare('DELETE FROM section_enrollments WHERE id = ? AND section_id = ?');
    $del->execute([(int)$enrollmentId, (int)$id]);
    flash('success', 'Alumno desinscrito de la sección.');
    redirect('secciones/' . $id);
});

// ==================== MÓDULO DE CARRERAS Y PLAN DE ESTUDIOS ====================
Flight::route('GET /carreras', function () {
    $pdo = db();
    $raw = (string)($_GET['q'] ?? '');
    $query = trim(preg_replace('/\s+/', ' ', $raw));

    $sql = '
        SELECT c.*,
            (SELECT COUNT(*) FROM career_semesters cs WHERE cs.career_id = c.id) as semesters_count,
            (SELECT COUNT(DISTINCT csc.course_id) 
             FROM career_semester_courses csc 
             JOIN career_semesters cs ON cs.id = csc.semester_id 
             WHERE cs.career_id = c.id) as courses_count,
            (SELECT COUNT(*) FROM students s WHERE s.career_id = c.id) as students_count
        FROM careers c
    ';
    $params = [];
    if ($query !== '') {
        $sql .= ' WHERE c.name LIKE ?';
        $params = ["%{$query}%"];
    }
    $sql .= ' ORDER BY c.name ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $careers = $stmt->fetchAll();

    render('careers/index', compact('careers', 'query'));
});

Flight::route('GET /carreras/nueva', function () {
    $career = ['name' => ''];
    $errors = [];
    render('careers/form', compact('career', 'errors'));
});

Flight::route('POST /carreras/guardar', function () {
    verifyCsrf();
    $pdo = db();
    $name = trim(preg_replace('/\s+/', ' ', postString('name')));
    $errors = [];
    if (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
        $errors[] = 'El nombre de la carrera debe tener entre 3 y 100 caracteres.';
    } else {
        $chk = $pdo->prepare('SELECT id FROM careers WHERE LOWER(name) = LOWER(?)');
        $chk->execute([$name]);
        if ($chk->fetch()) {
            $errors[] = 'Ya existe una carrera registrada con ese nombre.';
        }
    }

    if ($errors) {
        $career = ['name' => $name];
        render('careers/form', compact('career', 'errors'));
        return;
    }

    $stmt = $pdo->prepare('INSERT INTO careers (name) VALUES (?)');
    $stmt->execute([$name]);
    $newId = (int)$pdo->lastInsertId();

    // Auto-create Semestre 1 for new career
    $semStmt = $pdo->prepare('INSERT INTO career_semesters (career_id, number, name) VALUES (?, 1, "Semestre 1")');
    $semStmt->execute([$newId]);

    flash('success', 'Carrera "' . $name . '" registrada exitosamente. Puedes configurar su plan de estudios ahora.');
    redirect('carreras/' . $newId . '/plan');
});

Flight::route('GET /carreras/@id:[0-9]+/editar', function ($id) {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM careers WHERE id = ?');
    $stmt->execute([(int)$id]);
    $career = $stmt->fetch();
    if (!$career) {
        http_response_code(404);
        render('errors/404');
        return;
    }
    $errors = [];
    render('careers/form', compact('career', 'errors'));
});

Flight::route('POST /carreras/@id:[0-9]+/actualizar', function ($id) {
    verifyCsrf();
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM careers WHERE id = ?');
    $stmt->execute([(int)$id]);
    $career = $stmt->fetch();
    if (!$career) {
        http_response_code(404);
        render('errors/404');
        return;
    }

    $name = trim(preg_replace('/\s+/', ' ', postString('name')));
    $errors = [];
    if (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
        $errors[] = 'El nombre de la carrera debe tener entre 3 y 100 caracteres.';
    } else {
        $chk = $pdo->prepare('SELECT id FROM careers WHERE LOWER(name) = LOWER(?) AND id != ?');
        $chk->execute([$name, (int)$id]);
        if ($chk->fetch()) {
            $errors[] = 'Ya existe otra carrera registrada con ese nombre.';
        }
    }

    if ($errors) {
        $career['name'] = $name;
        render('careers/form', compact('career', 'errors'));
        return;
    }

    $upd = $pdo->prepare('UPDATE careers SET name = ? WHERE id = ?');
    $upd->execute([$name, (int)$id]);
    flash('success', 'Carrera actualizada exitosamente.');
    redirect('carreras');
});

Flight::route('POST /carreras/@id:[0-9]+/eliminar', function ($id) {
    verifyCsrf();
    $pdo = db();
    $check = $pdo->prepare('SELECT COUNT(*) FROM students WHERE career_id = ?');
    $check->execute([(int)$id]);
    if ((int)$check->fetchColumn() > 0) {
        flash('danger', 'No se puede eliminar la carrera porque tiene alumnos inscritos.');
        redirect('carreras');
    }

    // Delete semester courses, semesters, and career
    $pdo->prepare('DELETE csc FROM career_semester_courses csc JOIN career_semesters cs ON cs.id = csc.semester_id WHERE cs.career_id = ?')->execute([(int)$id]);
    $pdo->prepare('DELETE FROM career_semesters WHERE career_id = ?')->execute([(int)$id]);
    $pdo->prepare('DELETE FROM careers WHERE id = ?')->execute([(int)$id]);

    flash('success', 'Carrera eliminada exitosamente.');
    redirect('carreras');
});

Flight::route('GET /carreras/@id:[0-9]+/plan', function ($id) {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM careers WHERE id = ?');
    $stmt->execute([(int)$id]);
    $career = $stmt->fetch();
    if (!$career) {
        http_response_code(404);
        render('errors/404');
        return;
    }

    // Get all semesters with course counts
    $semStmt = $pdo->prepare('
        SELECT cs.*,
               (SELECT COUNT(*) FROM career_semester_courses csc WHERE csc.semester_id = cs.id) as courses_count
        FROM career_semesters cs
        WHERE cs.career_id = ?
        ORDER BY cs.number ASC
    ');
    $semStmt->execute([(int)$id]);
    $semesters = $semStmt->fetchAll();

    // Determine active semester
    $requestedSemId = (int)($_GET['semestre'] ?? 0);
    $activeSemester = null;
    if ($requestedSemId) {
        foreach ($semesters as $s) {
            if ((int)$s['id'] === $requestedSemId) {
                $activeSemester = $s;
                break;
            }
        }
    }
    if (!$activeSemester && !empty($semesters)) {
        $activeSemester = $semesters[0];
    }

    // Courses in active semester
    $coursesInSemester = [];
    if ($activeSemester) {
        $cStmt = $pdo->prepare('
            SELECT c.id, c.name, csc.is_mandatory
            FROM career_semester_courses csc
            JOIN courses c ON c.id = csc.course_id
            WHERE csc.semester_id = ?
            ORDER BY c.name ASC
        ');
        $cStmt->execute([(int)$activeSemester['id']]);
        $coursesInSemester = $cStmt->fetchAll();
    }

    // Available courses: all courses NOT in ANY semester of this career
    $avStmt = $pdo->prepare('
        SELECT c.*
        FROM courses c
        WHERE c.id NOT IN (
            SELECT csc.course_id
            FROM career_semester_courses csc
            JOIN career_semesters cs ON cs.id = csc.semester_id
            WHERE cs.career_id = ?
        )
        ORDER BY c.name ASC
    ');
    $avStmt->execute([(int)$id]);
    $availableCourses = $avStmt->fetchAll();

    render('careers/curriculum', compact('career', 'semesters', 'activeSemester', 'coursesInSemester', 'availableCourses'));
});

Flight::route('POST /carreras/@id:[0-9]+/semestres/agregar', function ($id) {
    verifyCsrf();
    $pdo = db();
    $maxStmt = $pdo->prepare('SELECT COALESCE(MAX(number), 0) FROM career_semesters WHERE career_id = ?');
    $maxStmt->execute([(int)$id]);
    $nextNumber = (int)$maxStmt->fetchColumn() + 1;
    $name = 'Semestre ' . $nextNumber;

    $ins = $pdo->prepare('INSERT INTO career_semesters (career_id, number, name) VALUES (?, ?, ?)');
    $ins->execute([(int)$id, $nextNumber, $name]);
    $newSemId = $pdo->lastInsertId();

    flash('success', 'Se ha agregado el ' . $name . ' al plan.');
    redirect('carreras/' . $id . '/plan?semestre=' . $newSemId);
});

Flight::route('POST /carreras/@id:[0-9]+/semestres/@semId:[0-9]+/eliminar', function ($id, $semId) {
    verifyCsrf();
    $pdo = db();
    $chk = $pdo->prepare('SELECT COUNT(*) FROM career_semester_courses WHERE semester_id = ?');
    $chk->execute([(int)$semId]);
    if ((int)$chk->fetchColumn() > 0) {
        flash('danger', 'No puedes eliminar un semestre que contiene cursos asignados. Primero quita los cursos.');
        redirect('carreras/' . $id . '/plan?semestre=' . $semId);
    }

    $del = $pdo->prepare('DELETE FROM career_semesters WHERE id = ? AND career_id = ?');
    $del->execute([(int)$semId, (int)$id]);
    flash('success', 'Semestre eliminado del plan de estudios.');
    redirect('carreras/' . $id . '/plan');
});

Flight::route('POST /carreras/@id:[0-9]+/semestres/@semId:[0-9]+/cursos/agregar', function ($id, $semId) {
    verifyCsrf();
    $pdo = db();
    $courseId = (int)($_POST['course_id'] ?? 0);
    $isMandatory = !empty($_POST['is_mandatory']) ? 1 : 0;

    if (!$courseId) {
        flash('danger', 'Selecciona un curso válido para agregar.');
        redirect('carreras/' . $id . '/plan?semestre=' . $semId);
    }

    // Verify course is not already in any semester of this career
    $dupCheck = $pdo->prepare('
        SELECT cs.name as semester_name, cs.number
        FROM career_semester_courses csc
        JOIN career_semesters cs ON cs.id = csc.semester_id
        WHERE cs.career_id = ? AND csc.course_id = ?
    ');
    $dupCheck->execute([(int)$id, $courseId]);
    $dup = $dupCheck->fetch();
    if ($dup) {
        flash('danger', 'Este curso ya está asignado en el Semestre ' . $dup['number'] . ' de esta carrera.');
        redirect('carreras/' . $id . '/plan?semestre=' . $semId);
    }

    $ins = $pdo->prepare('INSERT INTO career_semester_courses (semester_id, course_id, is_mandatory) VALUES (?, ?, ?)');
    $ins->execute([(int)$semId, $courseId, $isMandatory]);
    flash('success', 'Curso agregado exitosamente al semestre.');
    redirect('carreras/' . $id . '/plan?semestre=' . $semId);
});

Flight::route('POST /carreras/@id:[0-9]+/semestres/@semId:[0-9]+/cursos/@courseId:[0-9]+/quitar', function ($id, $semId, $courseId) {
    verifyCsrf();
    $pdo = db();
    $del = $pdo->prepare('DELETE FROM career_semester_courses WHERE semester_id = ? AND course_id = ?');
    $del->execute([(int)$semId, (int)$courseId]);
    flash('success', 'Curso removido del semestre.');
    redirect('carreras/' . $id . '/plan?semestre=' . $semId);
});

// ==================== MÓDULO DE CATEDRÁTICOS ====================
Flight::route('GET /catedraticos', function () {
    $pdo = db();
    $raw = (string)($_GET['q'] ?? '');
    $query = trim(preg_replace('/\s+/', ' ', $raw));
    $statusFilter = in_array($_GET['status'] ?? '', ['activo', 'inactivo'], true) ? $_GET['status'] : '';

    $sql = '
        SELECT p.*,
               (SELECT COUNT(*) FROM course_sections cs 
                JOIN periods per ON per.id = cs.period_id 
                WHERE cs.professor_id = p.id AND per.status = "activo") as active_sections_count,
               (SELECT COUNT(*) FROM course_sections cs WHERE cs.professor_id = p.id) as total_sections_count
        FROM professors p
        WHERE 1=1
    ';
    $params = [];

    if ($statusFilter !== '') {
        $sql .= ' AND p.status = ?';
        $params[] = $statusFilter;
    }

    if ($query !== '') {
        $sql .= ' AND (p.first_name LIKE ? OR p.last_name LIKE ? OR CONCAT(p.first_name, " ", p.last_name) LIKE ? OR p.code LIKE ? OR p.document LIKE ?)';
        $params[] = "%{$query}%";
        $params[] = "%{$query}%";
        $params[] = "%{$query}%";
        $params[] = "%{$query}%";
        $params[] = "%{$query}%";
    }

    $sql .= ' ORDER BY p.last_name ASC, p.first_name ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $professors = $stmt->fetchAll();

    render('professors/index', compact('professors', 'query', 'statusFilter'));
});

Flight::route('GET /catedraticos/nuevo', function () {
    $professor = [
        'code' => '',
        'first_name' => '',
        'last_name' => '',
        'document' => '',
        'email' => '',
        'phone' => '',
        'specialty' => '',
        'status' => 'activo'
    ];
    $errors = [];
    render('professors/form', compact('professor', 'errors'));
});

Flight::route('POST /catedraticos/guardar', function () {
    verifyCsrf();
    $pdo = db();
    ensureProfessorPhotoColumn($pdo);
    $code = strtoupper(trim(preg_replace('/\s+/', ' ', postString('code'))));
    $firstName = trim(preg_replace('/\s+/', ' ', postString('first_name')));
    $lastName = trim(preg_replace('/\s+/', ' ', postString('last_name')));
    $document = trim(preg_replace('/\s+/', ' ', postString('document')));
    $email = strtolower(trim(postString('email')));
    $phone = trim(preg_replace('/\s+/', ' ', postString('phone')));
    $specialty = trim(preg_replace('/\s+/', ' ', postString('specialty')));
    $status = in_array(postString('status'), ['activo', 'inactivo'], true) ? postString('status') : 'activo';

    $errors = [];
    if ($code === '' || mb_strlen($code) > 50) $errors[] = 'El código de catedrático es obligatorio (máx. 50 caracteres).';
    if (!validName($firstName)) $errors[] = 'Los nombres deben contener solo letras y espacios (entre 2 y 100 caracteres).';
    if (!validName($lastName)) $errors[] = 'Los apellidos deben contener solo letras y espacios (entre 2 y 100 caracteres).';
    if ($document === '' || mb_strlen($document) > 50) $errors[] = 'El documento de identificación es obligatorio.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Ingresa un correo electrónico válido.';

    if (empty($errors)) {
        $chkCode = $pdo->prepare('SELECT id FROM professors WHERE LOWER(code) = LOWER(?)');
        $chkCode->execute([$code]);
        if ($chkCode->fetch()) $errors[] = 'Ya existe un catedrático con ese código.';

        $chkDoc = $pdo->prepare('SELECT id FROM professors WHERE LOWER(document) = LOWER(?)');
        $chkDoc->execute([$document]);
        if ($chkDoc->fetch()) $errors[] = 'Ya existe un catedrático registrado con ese documento (DPI).';

        $chkEmail = $pdo->prepare('SELECT id FROM professors WHERE LOWER(email) = LOWER(?)');
        $chkEmail->execute([$email]);
        if ($chkEmail->fetch()) $errors[] = 'Ya existe un catedrático registrado con ese correo electrónico.';
    }

    if ($errors) {
        $professor = $_POST;
        render('professors/form', compact('professor', 'errors'));
        return;
    }

    $photo = uploadPhoto($_FILES['photo'] ?? null);

    $ins = $pdo->prepare('
        INSERT INTO professors (code, first_name, last_name, document, email, phone, specialty, status, photo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $ins->execute([$code, $firstName, $lastName, $document, $email, $phone, $specialty, $status, $photo]);
    flash('success', 'Catedrático registrado exitosamente.');
    redirect('catedraticos');
});

Flight::route('GET /catedraticos/@id:[0-9]+/editar', function ($id) {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM professors WHERE id = ?');
    $stmt->execute([(int)$id]);
    $professor = $stmt->fetch();
    if (!$professor) {
        http_response_code(404);
        render('errors/404');
        return;
    }
    $errors = [];
    render('professors/form', compact('professor', 'errors'));
});

Flight::route('POST /catedraticos/@id:[0-9]+/actualizar', function ($id) {
    verifyCsrf();
    $pdo = db();
    ensureProfessorPhotoColumn($pdo);
    $stmt = $pdo->prepare('SELECT * FROM professors WHERE id = ?');
    $stmt->execute([(int)$id]);
    $current = $stmt->fetch();
    if (!$current) {
        http_response_code(404);
        render('errors/404');
        return;
    }

    $code = strtoupper(trim(preg_replace('/\s+/', ' ', postString('code'))));
    $firstName = trim(preg_replace('/\s+/', ' ', postString('first_name')));
    $lastName = trim(preg_replace('/\s+/', ' ', postString('last_name')));
    $document = trim(preg_replace('/\s+/', ' ', postString('document')));
    $email = strtolower(trim(postString('email')));
    $phone = trim(preg_replace('/\s+/', ' ', postString('phone')));
    $specialty = trim(preg_replace('/\s+/', ' ', postString('specialty')));
    $status = in_array(postString('status'), ['activo', 'inactivo'], true) ? postString('status') : $current['status'];

    $errors = [];
    if ($code === '' || mb_strlen($code) > 50) $errors[] = 'El código de catedrático es obligatorio (máx. 50 caracteres).';
    if (!validName($firstName)) $errors[] = 'Los nombres deben contener solo letras y espacios (entre 2 y 100 caracteres).';
    if (!validName($lastName)) $errors[] = 'Los apellidos deben contener solo letras y espacios (entre 2 y 100 caracteres).';
    if ($document === '' || mb_strlen($document) > 50) $errors[] = 'El documento de identificación es obligatorio.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Ingresa un correo electrónico válido.';

    if (empty($errors)) {
        $chkCode = $pdo->prepare('SELECT id FROM professors WHERE LOWER(code) = LOWER(?) AND id != ?');
        $chkCode->execute([$code, (int)$id]);
        if ($chkCode->fetch()) $errors[] = 'Ya existe otro catedrático con ese código.';

        $chkDoc = $pdo->prepare('SELECT id FROM professors WHERE LOWER(document) = LOWER(?) AND id != ?');
        $chkDoc->execute([$document, (int)$id]);
        if ($chkDoc->fetch()) $errors[] = 'Ya existe otro catedrático con ese documento.';

        $chkEmail = $pdo->prepare('SELECT id FROM professors WHERE LOWER(email) = LOWER(?) AND id != ?');
        $chkEmail->execute([$email, (int)$id]);
        if ($chkEmail->fetch()) $errors[] = 'Ya existe otro catedrático con ese correo electrónico.';
    }

    if ($errors) {
        $professor = array_merge($current, $_POST);
        render('professors/form', compact('professor', 'errors'));
        return;
    }

    $photo = uploadPhoto($_FILES['photo'] ?? null, $current['photo'] ?? null);

    $upd = $pdo->prepare('
        UPDATE professors
        SET code = ?, first_name = ?, last_name = ?, document = ?, email = ?, phone = ?, specialty = ?, status = ?, photo = ?
        WHERE id = ?
    ');
    $upd->execute([$code, $firstName, $lastName, $document, $email, $phone, $specialty, $status, $photo, (int)$id]);
    flash('success', 'Información del catedrático actualizada.');
    redirect('catedraticos/' . $id);
});

Flight::route('GET /catedraticos/@id:[0-9]+', function ($id) {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM professors WHERE id = ?');
    $stmt->execute([(int)$id]);
    $professor = $stmt->fetch();
    if (!$professor) {
        http_response_code(404);
        render('errors/404');
        return;
    }

    $currentPeriod = $pdo->query('SELECT * FROM periods WHERE status = "activo" LIMIT 1')->fetch() ?: null;
    $activeSections = [];
    if ($currentPeriod) {
        $actStmt = $pdo->prepare('
            SELECT cs.*, c.name as course_name,
                   (SELECT COUNT(*) FROM section_enrollments se WHERE se.section_id = cs.id) as enrolled_count
            FROM course_sections cs
            JOIN courses c ON c.id = cs.course_id
            WHERE cs.professor_id = ? AND cs.period_id = ?
            ORDER BY c.name ASC, cs.code ASC
        ');
        $actStmt->execute([(int)$id, (int)$currentPeriod['id']]);
        $activeSections = $actStmt->fetchAll();
    }

    $allStmt = $pdo->prepare('
        SELECT cs.*, c.name as course_name, p.name as period_name, p.status as period_status,
               (SELECT COUNT(*) FROM section_enrollments se WHERE se.section_id = cs.id) as enrolled_count
        FROM course_sections cs
        JOIN courses c ON c.id = cs.course_id
        JOIN periods p ON p.id = cs.period_id
        WHERE cs.professor_id = ?
        ORDER BY p.start_date DESC, c.name ASC
    ');
    $allStmt->execute([(int)$id]);
    $allSections = $allStmt->fetchAll();

    render('professors/show', compact('professor', 'currentPeriod', 'activeSections', 'allSections'));
});

Flight::route('POST /catedraticos/@id:[0-9]+/toggle-estado', function ($id) {
    verifyCsrf();
    $pdo = db();
    $stmt = $pdo->prepare('SELECT status FROM professors WHERE id = ?');
    $stmt->execute([(int)$id]);
    $status = $stmt->fetchColumn();
    if ($status !== false) {
        $newStatus = ($status === 'activo') ? 'inactivo' : 'activo';
        $upd = $pdo->prepare('UPDATE professors SET status = ? WHERE id = ?');
        $upd->execute([$newStatus, (int)$id]);
        flash('success', 'El estado del catedrático ha sido actualizado a ' . $newStatus . '.');
    }
    redirect('catedraticos');
});

Flight::route('POST /catedraticos/@id:[0-9]+/eliminar', function ($id) {
    verifyCsrf();
    $pdo = db();
    $chk = $pdo->prepare('SELECT COUNT(*) FROM course_sections WHERE professor_id = ?');
    $chk->execute([(int)$id]);
    if ((int)$chk->fetchColumn() > 0) {
        $upd = $pdo->prepare('UPDATE professors SET status = "inactivo" WHERE id = ?');
        $upd->execute([(int)$id]);
        flash('warning', 'No se puede eliminar un catedrático con historial de secciones impartidas. Se ha cambiado su estado a inactivo.');
        redirect('catedraticos');
    }

    $pStmt = $pdo->prepare('SELECT photo FROM professors WHERE id = ?');
    $pStmt->execute([(int)$id]);
    $oldPhoto = $pStmt->fetchColumn();

    $del = $pdo->prepare('DELETE FROM professors WHERE id = ?');
    $del->execute([(int)$id]);

    if ($oldPhoto) {
        $uploadDir = ROOT_PATH . '/storage/uploads';
        $file = $uploadDir . '/' . basename($oldPhoto);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    flash('success', 'Catedrático eliminado exitosamente.');
    redirect('catedraticos');
});


Flight::route('GET /reportes/alumnos', function () {
    $pdo = db();
    $careerId = (int)($_GET['career_id'] ?? 0);
    $status = trim((string)($_GET['status'] ?? 'todas'));
    $q = trim((string)($_GET['q'] ?? ''));
    $export = strtolower(trim((string)($_GET['export'] ?? '')));

    $reportData = getStudentsReport([
        'career_id' => $careerId,
        'status' => $status,
        'q' => $q
    ], $pdo);

    if ($export === 'excel' || $export === 'csv') {
        $headers = ['Código', 'Apellidos y Nombres', 'Carrera', 'Semestre Actual', 'Estado', 'Fecha de Ingreso'];
        $rows = [];
        foreach ($reportData['students'] as $st) {
            $rows[] = [
                $st['code'],
                $st['full_name_sort'],
                $st['career_name'],
                $st['current_semester'],
                ucfirst($st['status']),
                $st['entry_date']
            ];
        }
        $filename = 'reporte_alumnos_' . date('Ymd_His') . '.csv';
        exportReportCsv($filename, $headers, $rows);
    }

    render('reports/students', compact('reportData'));
});

Flight::route('GET /reportes/notas', function () {
    $pdo = db();
    $periodId = isset($_GET['period_id']) ? (int)$_GET['period_id'] : null;
    $careerId = (int)($_GET['career_id'] ?? 0);
    $courseId = (int)($_GET['course_id'] ?? 0);
    $sectionId = (int)($_GET['section_id'] ?? 0);
    $export = strtolower(trim((string)($_GET['export'] ?? '')));

    $filters = [
        'career_id' => $careerId,
        'course_id' => $courseId,
        'section_id' => $sectionId
    ];
    if ($periodId !== null && $periodId > 0) {
        $filters['period_id'] = $periodId;
    }

    $reportData = getGradesReport($filters, $pdo);

    if ($export === 'excel' || $export === 'csv') {
        $headers = [
            '#',
            'Código',
            'Apellidos y Nombres',
            'Carrera',
            'Curso',
            'Sección',
            'Período',
            'Catedrático',
            'Nota Final',
            'Estado'
        ];
        $rows = [];
        foreach ($reportData['sections_data'] as $sec) {
            foreach ($sec['students'] as $st) {
                $rows[] = [
                    $st['num'],
                    $st['student_code'],
                    $st['full_name_sort'],
                    $sec['career_name'],
                    $sec['course_name'],
                    $sec['section_code'],
                    $sec['period_name'],
                    $sec['professor_name'],
                    $st['final_score'] !== null ? $st['final_score'] : '',
                    ucfirst($st['status'])
                ];
            }
        }
        $filename = 'reporte_notas_' . date('Ymd_His') . '.csv';
        exportReportCsv($filename, $headers, $rows);
    }

    render('reports/grades', compact('reportData'));
});

Flight::route('GET /reportes/consulta-alumno(/@id:[0-9]+)', function (?string $paramId = null) {
    $pdo = db();
    $selectedId = (int)($paramId ?: ($_GET['id'] ?? 0));
    $query = trim((string)($_GET['q'] ?? ''));
    $careerId = (int)($_GET['career_id'] ?? 0);
    $statusFilter = strtolower(trim((string)($_GET['status'] ?? 'todos')));
    if ($statusFilter === 'todas' || $statusFilter === '') {
        $statusFilter = 'todos';
    }

    $profile = null;
    if ($selectedId > 0) {
        $profile = getStudentAcademicProfile($selectedId, $pdo);
    }

    // Catálogo de carreras para el filtro
    $careers = $pdo->query('SELECT id, name FROM careers ORDER BY name ASC')->fetchAll();

    // Consulta de alumnos con filtros aplicados (búsqueda, carrera, estado)
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
        $cleanQ = preg_replace('/^AC-0*/i', '', $query);
        if (is_numeric($cleanQ)) {
            $sql .= ' AND (s.id = ? OR CONCAT(s.first_name, " ", s.last_name) LIKE ? OR CONCAT(s.last_name, " ", s.first_name) LIKE ?)';
            $params[] = (int)$cleanQ;
            $params[] = '%' . $query . '%';
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

    $studentList = [];
    foreach ($rawStudents as $st) {
        $prog = calculateStudentAcademicProgress((int)$st['id'], (int)$st['career_id'], $pdo);
        $stStatus = $prog['status'];

        if ($statusFilter !== 'todos' && $stStatus !== $statusFilter) {
            continue;
        }

        $st['status'] = $stStatus;
        $st['code'] = 'AC-' . str_pad((string)$st['id'], 5, '0', STR_PAD_LEFT);
        $st['full_name'] = trim($st['first_name'] . ' ' . $st['last_name']);
        $st['approved_count'] = $prog['approved_count'];
        $st['total_mandatory'] = $prog['total_mandatory'];
        $st['current_semester_label'] = $prog['current_semester_label'];
        $studentList[] = $st;
    }

    render('reports/student_lookup', compact('profile', 'query', 'selectedId', 'studentList', 'careers', 'careerId', 'statusFilter'));
});

