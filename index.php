<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/app/config.php';

$old = $_SESSION['old'] ?? [];
$errors = $_SESSION['errors'] ?? [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['old'], $_SESSION['errors'], $_SESSION['flash']);

function old(string $key, string $default = ''): string
{
    global $old;
    return isset($old[$key]) ? (string) $old[$key] : $default;
}

function hasError(string $key): bool
{
    global $errors;
    return isset($errors[$key]);
}

function uniqueOptions(array $items): array
{
    $normalized = [];
    $result = [];
    foreach ($items as $item) {
        $value = trim((string) $item);
        if ($value === '') {
            continue;
        }
        $key = strtolower($value);
        if (isset($normalized[$key])) {
            continue;
        }
        $normalized[$key] = true;
        $result[] = $value;
    }
    return $result;
}

function valueInOptions(string $needle, array $options): bool
{
    foreach ($options as $option) {
        if (strtolower(trim($option)) === strtolower(trim($needle))) {
            return true;
        }
    }
    return false;
}

$defaultSchools = [
    'COLEGIO DE MONTALBAN',
    'SAN MATEO MUNICIPAL COLLEGE',
    'VALLEY HIGH ACADEMY',
    'EASTERN VALLEY SCHOOL',
    'WESTBRIDGE INSTITUTE OF TECHNOLOGY INC.',
    'OUR LADY OF FATIMA UNIVERSITY',
    'POLYTECHNIC UNIVERSITY OF THE PHILIPPINES',
    'NATIONAL UNIVERSITY',
    'COLLEGE OF ARTS & SCIENCES OF ASIA AND THE PACIFIC',
    'WORLD CITI COLLEGES',
    'ASIAN INSTITUTE OF COMPUTER SCIENCE – MAIN CAMPUS',
    'ASIAN INSTITUTE OF COMPUTER SCIENCE – RODRIGUEZ CAMPUS',
    'SAN JOSE LITEX SENIOR HIGHSCHOOL',
    'INFANT JESUS LEARNING ACADEMY OF RODRIGUEZ, RIZAL',
    'BESTLINK COLLEGE OF THE PHILIPPINES',
    'COLEGIO DE SAN JUAN',
    'SAN ISIDRO SENIOR HIGHSCHOOL',
    'FAR EASTERN UNIVERSITY',
    'KASIGLAHAN NATIONAL HIGHSCHOOL',
    'CENTER FOR POSITIVE FUTURES',
    'STI COLLEGES',
    'EULOGIO "AMANG" RODRIGUEZ INSTITUTE OF SCIENCE AND TECHNOLOGY - MANILA CAMPUS',
    'AFFORDABLE PRIVATE EDUCATION CENTER',
    'PHILIPPINE NORMAL UNIVERSITY',
    'AMA COMPUTER COLLEGE',
    'PATEROS TECHNOLOGICAL COLLEGE',
    'METRO BUSINESS COLLEGE',
];
$defaultDepartments = [
    'Accounting Office',
    'Agriculture Office',
    'Assessors Office',
    'Budget Office',
    'Building Maintenance Division',
    'Business Permit & Licensing Office',
    'Clean And Green Department',
    'Colegio De Montalban',
    'Commission On Audit',
    'Commission On Election',
    'Commission On Population & Development Office',
    'Complete Customer Hub',
    'Department Of Interior & Local Government Unit',
    'Engineering Office',
    'General Service Office',
    'Housing & People\'s Development Office',
    'Human Resource Management Office',
    'Local Civil Registry',
    'Local Youth Development Office',
    'Montalban Infirmary',
    'Montalban Public Market',
    'Montalban Security Division',
    'Mtc, Rtc / Pao',
    'Municipal Agrarian Reform (Maro/Dar)',
    'Municipal Anti-Drug Abuse Unit',
    'Municipal Disaster Risk Reduction & Management Office',
    'Municipal Education And Development Office',
    'Municipal Environment And Natural Resources Office',
    'Municipal Gender And Development',
    'Municipal Health Office',
    'Rodriguez Municipal Library-Main',
    'Rodriguez Municipal Library-Kasiglahan',
    'Rodriguez Municipal Library-San Isidro',
    'Rodriguez Municipal Library-Burgos',
    'Rodriguez Municipal Library-Macabud',
    'Municipal Information Technology Office',
    'Municipal Livelihood Technical Vocational Education And Skills Development Center',
    'Municipal Motorpool',
    'Municipal Planning & Development Office',
    'Municipal Public Information Office',
    'Municipal Solid Waste Management Office',
    'Municipal Checkers Office',
    'Municipal Sports And Development Office',
    'Municipal Stockroom Office',
    'Municipal Tourism And Cultural Affairs Department',
    'Municipal Transportation Management And Development Office',
    'Municipal Values Formation And Chaplaincy Service Unit',
    'Office Of Senior Citizens Affairs',
    'Office Of The Municipal Administrator / Legal Office',
    'Office Of The Municipal Mayor',
    'Office Of The Muslim Affairs',
    'Persons With Disabilities Affairs Office',
    'Office Of The Vice Mayor',
    'Sb - Hon. Analyn A. Cuerpo',
    'Sb - Hon. Richard Buizon',
    'Sb - Hon. Joanne Apao',
    'Sb - Hon. Judith G. Cruz',
    'Sb - Hon. Roderick Lazarte',
    'Sb - Hon. Mark David Acob',
    'Sb - Hon. Arnel De Vera',
    'Sb - Hon. Mark Anthony Marcelo',
    'Sb - Hon. Ralph Ivan Rodriguez',
    'Sb - Hon. Ronald Umali',
    'Sb - Hon. Dahlia C. Cabio',
    'Post Office',
    'Procurement Office',
    'Public Employment Service Office',
    'Rodriguez Municipal Jail Service Unit',
    'Sangguniang Bayan',
    'Treasury Office',
];
$defaultHours = ['486', '600'];
$defaultCourses = [
    'BS Information Technology',
    'BS Computer Science',
    'BS Information Systems',
    'BS Business Administration',
    'BS Accountancy',
    'BS Office Administration',
    'BS Hospitality Management',
    'BS Tourism Management',
    'BS Entrepreneurship',
    'BS Criminology',
    'BS Psychology',
    'BS Education',
    'BS Secondary Education',
    'BS Elementary Education',
    'BS Public Administration',
    'BS Civil Engineering',
    'BS Nursing',
    'BS Midwifery',
    'BS Social Work',
    'AB Communication',
];

$dbSchools = [];
$dbDepartments = [];
$dbHours = [];
$dbCourses = [];
try {
    $dbSchools = array_column(db()->query('SELECT DISTINCT school FROM interns ORDER BY school')->fetchAll(), 'school');
    $dbDepartments = array_column(db()->query('SELECT DISTINCT department FROM interns ORDER BY department')->fetchAll(), 'department');
    $dbHours = array_map(
        static fn($v): string => (string) $v,
        array_column(db()->query('SELECT DISTINCT hours_rendered FROM interns ORDER BY hours_rendered')->fetchAll(), 'hours_rendered')
    );
    $dbCourses = array_column(db()->query("SELECT DISTINCT course FROM interns WHERE course IS NOT NULL AND TRIM(course) != '' ORDER BY course")->fetchAll(), 'course');
} catch (Throwable $e) {
    // Ignore option loading errors so form still works.
}

$schoolOptions = uniqueOptions(array_merge($defaultSchools, [old('school')]));
$departmentOptions = uniqueOptions(array_merge($defaultDepartments, [old('department')]));
$hourOptions = uniqueOptions(array_merge($defaultHours, $dbHours, [old('hours_rendered')]));
$courseOptions = uniqueOptions(array_merge($defaultCourses, $dbCourses, [old('course')]));

$oldSchool = old('school');
$oldDepartment = old('department');
$oldHours = old('hours_rendered');
$oldCourse = old('course');

$schoolIsCustom = $oldSchool !== '' && !valueInOptions($oldSchool, $schoolOptions);
$departmentIsCustom = $oldDepartment !== '' && !valueInOptions($oldDepartment, $departmentOptions);
$hoursIsCustom = $oldHours !== '' && !valueInOptions($oldHours, $hourOptions);
$courseIsCustom = $oldCourse !== '' && !valueInOptions($oldCourse, $courseOptions);
?>
<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body class="bg-gradient-soft">
<?php if ($flash || hasError('db')): ?>
    <div class="top-toast-wrap">
        <?php if ($flash): ?>
            <div class="top-toast top-toast-success auto-dismiss">
                <i class="bi bi-check-circle-fill me-2"></i><?= h($flash) ?>
            </div>
        <?php endif; ?>
        <?php if (hasError('db')): ?>
            <div class="top-toast top-toast-danger auto-dismiss">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= h($errors['db']) ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<header class="site-header no-print animate-fade-down">
    <div class="container d-flex justify-content-between align-items-center py-3">
        <div>
            <h2 class="h5 mb-0 fw-bold">HRMO Certificate of Completion</h2>
            <small class="text-header-sub">Municipality of Rodriguez</small>
        </div>
        <button type="button" id="zoomToggleBtn" class="btn btn-sm btn-outline-light">
            <i class="bi bi-zoom-in me-1"></i>Zoom UI
        </button>
    </div>
</header>

<main class="container-fluid px-3 px-md-4 py-4 py-md-5">
    <div class="row justify-content-start">
        <div class="col-12">
            <div class="modern-card card border-0 shadow-lg">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
                        <div>
                            <p class="badge rounded-pill text-bg-primary-subtle text-primary-emphasis mb-2">HRMO COC System</p>
                            <h1 class="h3 fw-bold mb-2">Generate Internship Certificate</h1>
                            <p class="text-muted mb-0">Fill in intern details once, then print a ready certificate.</p>
                        </div>
                        <div class="text-end text-muted small">
                            <div><i class="bi bi-database"></i> Database: SQLite</div>
                            <div><i class="bi bi-magic"></i> Auto letter formatting</div>
                        </div>
                    </div>

                    <form action="save_intern.php" method="post" class="row g-3 g-md-4" novalidate>
                        <div class="col-md-6">
                            <label class="form-label">Intern Level</label>
                            <select name="intern_level" id="internLevel" class="form-select <?= hasError('intern_level') ? 'is-invalid' : '' ?>" required>
                                <option value="">Select</option>
                                <option value="college" <?= old('intern_level') === 'college' ? 'selected' : '' ?>>College</option>
                                <option value="shs" <?= old('intern_level') === 'shs' ? 'selected' : '' ?>>Senior High School (SHS)</option>
                            </select>
                            <?php if (hasError('intern_level')): ?><div class="invalid-feedback d-block">Please select level.</div><?php endif; ?>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control <?= hasError('full_name') ? 'is-invalid' : '' ?>" value="<?= h(old('full_name')) ?>" required>
                            <?php if (hasError('full_name')): ?><div class="invalid-feedback d-block">Full name is required.</div><?php endif; ?>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select <?= hasError('gender') ? 'is-invalid' : '' ?>" required>
                                <option value="">Select</option>
                                <option value="female" <?= old('gender') === 'female' ? 'selected' : '' ?>>Female</option>
                                <option value="male" <?= old('gender') === 'male' ? 'selected' : '' ?>>Male</option>
                            </select>
                            <?php if (hasError('gender')): ?><div class="invalid-feedback d-block">Please select gender.</div><?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label class="form-label">School</label>
                            <select name="school_select" id="schoolSelect" class="form-select <?= hasError('school') ? 'is-invalid' : '' ?>" required>
                                <option value="">Select</option>
                                <?php foreach ($schoolOptions as $school): ?>
                                    <option value="<?= h($school) ?>" <?= $oldSchool === $school ? 'selected' : '' ?>><?= h($school) ?></option>
                                <?php endforeach; ?>
                                <option value="__custom__" <?= $schoolIsCustom ? 'selected' : '' ?>>Other (Type manually)</option>
                            </select>
                            <input type="text" id="schoolCustom" name="school_custom" class="form-control mt-2 <?= hasError('school') ? 'is-invalid' : '' ?> <?= $schoolIsCustom ? '' : 'd-none' ?>" value="<?= $schoolIsCustom ? h($oldSchool) : '' ?>" placeholder="Enter school name">
                            <?php if (hasError('school')): ?><div class="invalid-feedback d-block">School is required.</div><?php endif; ?>
                        </div>

                        <div class="col-12 d-none" id="courseWrap">
                            <label class="form-label">Course (College only)</label>
                            <select name="course_select" id="courseSelect" class="form-select <?= hasError('course') ? 'is-invalid' : '' ?>">
                                <option value="">Select</option>
                                <?php foreach ($courseOptions as $course): ?>
                                    <option value="<?= h($course) ?>" <?= $oldCourse === $course ? 'selected' : '' ?>><?= h($course) ?></option>
                                <?php endforeach; ?>
                                <option value="__custom__" <?= $courseIsCustom ? 'selected' : '' ?>>Other (Type manually)</option>
                            </select>
                            <input type="text" id="courseCustom" name="course_custom" class="form-control mt-2 <?= hasError('course') ? 'is-invalid' : '' ?> <?= $courseIsCustom ? '' : 'd-none' ?>" value="<?= $courseIsCustom ? h($oldCourse) : '' ?>" placeholder="Enter course/program">
                            <?php if (hasError('course')): ?><div class="invalid-feedback d-block">Course is required for college interns.</div><?php endif; ?>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Completed Hours</label>
                            <select name="hours_rendered_select" id="hoursSelect" class="form-select <?= hasError('hours_rendered') ? 'is-invalid' : '' ?>" required>
                                <option value="">Select</option>
                                <?php foreach ($hourOptions as $hours): ?>
                                    <option value="<?= h($hours) ?>" <?= $oldHours === $hours ? 'selected' : '' ?>><?= h($hours) ?></option>
                                <?php endforeach; ?>
                                <option value="__custom__" <?= $hoursIsCustom ? 'selected' : '' ?>>Other (Type manually)</option>
                            </select>
                            <input type="number" id="hoursCustom" name="hours_rendered_custom" class="form-control mt-2 <?= hasError('hours_rendered') ? 'is-invalid' : '' ?> <?= $hoursIsCustom ? '' : 'd-none' ?>" value="<?= $hoursIsCustom ? h($oldHours) : '' ?>" min="1" placeholder="Enter hours">
                            <?php if (hasError('hours_rendered')): ?><div class="invalid-feedback d-block">Enter valid rendered hours.</div><?php endif; ?>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Assigned Department</label>
                            <select name="department_select" id="departmentSelect" class="form-select <?= hasError('department') ? 'is-invalid' : '' ?>" required>
                                <option value="">Select</option>
                                <?php foreach ($departmentOptions as $department): ?>
                                    <option value="<?= h($department) ?>" <?= $oldDepartment === $department ? 'selected' : '' ?>><?= h($department) ?></option>
                                <?php endforeach; ?>
                                <option value="__custom__" <?= $departmentIsCustom ? 'selected' : '' ?>>Other (Type manually)</option>
                            </select>
                            <input type="text" id="departmentCustom" name="department_custom" class="form-control mt-2 <?= hasError('department') ? 'is-invalid' : '' ?> <?= $departmentIsCustom ? '' : 'd-none' ?>" value="<?= $departmentIsCustom ? h($oldDepartment) : '' ?>" placeholder="Enter department name">
                            <?php if (hasError('department')): ?><div class="invalid-feedback d-block">Department is required.</div><?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control <?= hasError('start_date') ? 'is-invalid' : '' ?>" value="<?= h(old('start_date')) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control <?= hasError('end_date') ? 'is-invalid' : '' ?>" value="<?= h(old('end_date')) ?>" required>
                            <?php if (hasError('end_date')): ?><div class="invalid-feedback d-block">End date must be valid.</div><?php endif; ?>
                        </div>

                        <div class="col-12 d-flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-file-earmark-text me-2"></i>Generate Certificate</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
<footer class="site-footer no-print mt-4">
    <div class="container py-3 d-flex justify-content-between flex-wrap gap-2 small">
        <span>HRMO OJT Certification System</span>
        <span>Designed for fast intern certificate generation</span>
    </div>
</footer>

<script src="assets/app.js"></script>
</body>
</html>
