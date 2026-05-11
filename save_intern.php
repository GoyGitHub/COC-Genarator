<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/app/config.php';

/**
 * Generate certificate ID in format YY-XXXX
 * YY = current year (26 for 2026)
 * XXXX = sequential number padded with zeros
 */
function generateCertificateId(): string
{
    $year = date('y');
    
    // Get count of certificates created this year
    try {
        $stmt = db()->prepare(
            'SELECT COUNT(*) as count FROM interns WHERE strftime("%Y", created_at) = ?'
        );
        $stmt->execute([date('Y')]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = ($result['count'] ?? 0) + 1;
    } catch (Throwable $e) {
        // Fallback if query fails
        $count = 1;
    }
    
    // Format as YY-XXXX
    return sprintf('%s-%04d', $year, $count);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$data = [
    'intern_level' => trim((string) ($_POST['intern_level'] ?? '')),
    'full_name' => trim((string) ($_POST['full_name'] ?? '')),
    'gender' => trim((string) ($_POST['gender'] ?? '')),
    'school' => trim((string) ($_POST['school_select'] ?? '')),
    'school_custom' => trim((string) ($_POST['school_custom'] ?? '')),
    'course' => trim((string) ($_POST['course_select'] ?? '')),
    'course_custom' => trim((string) ($_POST['course_custom'] ?? '')),
    'hours_rendered' => trim((string) ($_POST['hours_rendered_select'] ?? '')),
    'hours_rendered_custom' => trim((string) ($_POST['hours_rendered_custom'] ?? '')),
    'department' => trim((string) ($_POST['department_select'] ?? '')),
    'department_custom' => trim((string) ($_POST['department_custom'] ?? '')),
    'start_date' => trim((string) ($_POST['start_date'] ?? '')),
    'end_date' => trim((string) ($_POST['end_date'] ?? '')),
];

if ($data['school'] === '__custom__') {
    $data['school'] = $data['school_custom'];
}
if ($data['department'] === '__custom__') {
    $data['department'] = $data['department_custom'];
}
if ($data['hours_rendered'] === '__custom__') {
    $data['hours_rendered'] = $data['hours_rendered_custom'];
}
if ($data['course'] === '__custom__') {
    $data['course'] = $data['course_custom'];
}

$errors = [];

if (!in_array($data['intern_level'], ['college', 'shs'], true)) {
    $errors['intern_level'] = 'Required';
}
if ($data['full_name'] === '') {
    $errors['full_name'] = 'Required';
}
if (!in_array($data['gender'], ['male', 'female'], true)) {
    $errors['gender'] = 'Required';
}
if ($data['school'] === '') {
    $errors['school'] = 'Required';
}
if ($data['intern_level'] === 'college' && $data['course'] === '') {
    $errors['course'] = 'Course is required for college interns';
}
if (!ctype_digit($data['hours_rendered']) || (int) $data['hours_rendered'] <= 0) {
    $errors['hours_rendered'] = 'Invalid hours';
}
if ($data['department'] === '') {
    $errors['department'] = 'Required';
}
if ($data['start_date'] === '' || $data['end_date'] === '') {
    $errors['start_date'] = 'Start and end dates are required';
}
if ($data['start_date'] !== '' && $data['end_date'] !== '' && strtotime($data['start_date']) > strtotime($data['end_date'])) {
    $errors['end_date'] = 'End date must not be before start date';
}

if ($errors !== []) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old'] = $data;
    header('Location: index.php');
    exit;
}

if ($data['intern_level'] === 'shs') {
    $data['course'] = null;
}

try {
    // Generate certificate ID
    $certificateId = generateCertificateId();
    
    $stmt = db()->prepare(
        'INSERT INTO interns
        (certificate_id, intern_level, full_name, gender, school, course, hours_rendered, department, start_date, end_date)
        VALUES
        (:certificate_id, :intern_level, :full_name, :gender, :school, :course, :hours_rendered, :department, :start_date, :end_date)'
    );

    $stmt->execute([
        ':certificate_id' => $certificateId,
        ':intern_level' => $data['intern_level'],
        ':full_name' => $data['full_name'],
        ':gender' => $data['gender'],
        ':school' => $data['school'],
        ':course' => $data['course'],
        ':hours_rendered' => (int) $data['hours_rendered'],
        ':department' => $data['department'],
        ':start_date' => $data['start_date'],
        ':end_date' => $data['end_date'],
    ]);

    $id = (int) db()->lastInsertId();
    
    // Generate certificate using Python (synchronously - wait for it)
    try {
        generateCertificateSynchronous($id, $data['intern_level']);
        $_SESSION['flash'] = 'Intern record saved successfully. Certificate generated!';
    } catch (Exception $e) {
        $_SESSION['flash'] = 'Intern record saved. Certificate will be generated on first visit.';
        // Don't fail - record was saved even if cert generation has issues
    }
    
    header('Location: certificate.php?id=' . $id);
    exit;
} catch (Throwable $e) {
    $_SESSION['old'] = $data;
    $_SESSION['errors'] = ['db' => 'Could not save record. Please check your database setup in config.php.'];
    header('Location: index.php');
    exit;
}

/**
 * Generate certificate synchronously (wait for completion)
 * 
 * @param int $internId
 * @throws Exception
 */
function generateCertificateSynchronous(int $internId, string $level): void
{
    $pythonDir = __DIR__ . '/python';
    $pythonExe = getPythonExecutable();
    
    if (!$pythonExe) {
        throw new Exception('Python executable not found');
    }
    
    if (!file_exists($pythonDir . '/generate.py')) {
        throw new Exception('Certificate generator not found');
    }
    
    try {
        // Run Python generator synchronously and wait for result
        $scriptPath = $pythonDir . '/generate.py';
        $cmd = escapeshellcmd("{$pythonExe} {$scriptPath} {$internId}");
        
        // Capture output
        $output = shell_exec($cmd . ' 2>&1');
        
        // Check for errors in output
        if (strpos($output, 'Error') !== false || strpos($output, 'error') !== false) {
            // Log the error but don't throw - allow page to continue
            error_log("Certificate generation error for intern {$internId}: {$output}");
        }
    } catch (Throwable $e) {
        // Log error but don't throw
        error_log("Certificate generation exception: " . $e->getMessage());
    }
}

/**
 * Find Python executable
 */
function getPythonExecutable(): ?string
{
    $possiblePaths = [
        'py.exe -3.12',  // Python launcher with version 3.12
        'py.exe -3',     // Python launcher with Python 3
        'py.exe',        // Python launcher
        'python.exe',
        'python3.exe',
        'C:\\Python312\\python.exe',
        'C:\\Python311\\python.exe',
        'C:\\Python310\\python.exe',
    ];
    
    foreach ($possiblePaths as $path) {
        // Special handling for py.exe variants (they include arguments)
        if (strpos($path, 'py.exe') === 0) {
            $testCmd = escapeshellcmd("{$path} --version");
            $output = shell_exec("{$testCmd} 2>&1");
            if ($output !== null && strpos($output, 'Python') !== false) {
                return $path;
            }
        } else {
            if (file_exists($path)) {
                return $path;
            }
            $testCmd = escapeshellcmd("where {$path}");
            $output = shell_exec("{$testCmd} 2>nul");
            if ($output !== null && trim($output) !== '') {
                return $path;
            }
        }
    }
    
    return null;
}