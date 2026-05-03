<?php
declare(strict_types=1);

require_once __DIR__ . '/app/config.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo 'Invalid certificate ID.';
    exit;
}

// Get intern data from database
$stmt = db()->prepare('SELECT * FROM interns WHERE id = ?');
$stmt->execute([$id]);
$intern = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$intern) {
    http_response_code(404);
    echo 'Intern record not found.';
    exit;
}

// Generate certificate file name
$name_cleaned = preg_replace('/\s+/', '-', $intern['full_name']);
$date_str = date('Ymd');
$certFileName = "COC-{$name_cleaned}-{$date_str}.docx";
$certPath = __DIR__ . "/Certificates/{$certFileName}";

// Check if certificate file exists
$certExists = file_exists($certPath);

if (!$certExists) {
    // Try to generate it now if it doesn't exist
    try {
        generateCertificateNow($id, $intern['intern_level']);
        // Wait a moment for file to be written
        usleep(500000); // 0.5 seconds
    } catch (Exception $e) {
        // Log error but don't fail completely
    }
}

// Check again after generation attempt
$certExists = file_exists($certPath);

// If still not there, set a refresh flag
$needsRefresh = !$certExists;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - <?= h((string) $intern['full_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
    <?php if (!$certExists): ?>
    <meta http-equiv="refresh" content="2">
    <?php endif; ?>
</head>
<body class="bg-light py-4">

<header class="bg-white border-bottom mb-4">
    <div class="container py-3">
        <h2 class="h5 mb-0">
            <i class="bi bi-file-earmark-word"></i> Certificate of Completion
        </h2>
        <small class="text-muted">Municipality of Rodriguez - HRMO</small>
    </div>
</header>

<div class="container">
    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            <!-- Actions -->
            <div class="mb-3 d-flex gap-2 flex-wrap">
                <?php if ($certExists): ?>
                <a href="/Certificates/<?= h($certFileName) ?>" class="btn btn-success btn-sm" download>
                    <i class="bi bi-download me-1"></i>Download Certificate (DOCX)
                </a>
                <?php else: ?>
                <button class="btn btn-warning btn-sm" disabled>
                    <i class="bi bi-hourglass me-1"></i>Generating...
                </button>
                <?php endif; ?>
                <a href="index.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i>New Entry
                </a>
                <button onclick="window.print()" class="btn btn-primary btn-sm">
                    <i class="bi bi-printer me-1"></i>Print
                </button>
            </div>

            <?php if ($certExists): ?>
            <!-- Success message -->
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <strong>Certificate Generated!</strong> Your certificate is ready. Download or print using the buttons above.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>

            <!-- Certificate File Info -->
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <p class="mb-2"><strong>Certificate File:</strong> <code><?= h($certFileName) ?></code></p>
                    <p class="mb-0 text-muted small">Populated from template with your information</p>
                </div>
            </div>

            <?php else: ?>
            <!-- Error message -->
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Generating Certificate...</strong> The certificate is being generated. Please refresh the page in a moment.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Intern Details -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Intern Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <th style="width: 35%">Full Name:</th>
                            <td><strong><?= h((string) $intern['full_name']) ?></strong></td>
                        </tr>
                        <tr>
                            <th>Level:</th>
                            <td><?= ucfirst(h((string) $intern['intern_level'])) ?></td>
                        </tr>
                        <?php if ($intern['course']): ?>
                        <tr>
                            <th>Course:</th>
                            <td><?= h((string) $intern['course']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>School:</th>
                            <td><?= h((string) $intern['school']) ?></td>
                        </tr>
                        <tr>
                            <th>Department:</th>
                            <td><?= h((string) $intern['department']) ?></td>
                        </tr>
                        <tr>
                            <th>Hours Rendered:</th>
                            <td><?= h((string) $intern['hours_rendered']) ?> hours</td>
                        </tr>
                        <tr>
                            <th>Training Period:</th>
                            <td>
                                <?= h((string) $intern['start_date']) ?> to 
                                <?= h((string) $intern['end_date']) ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="mt-4 mb-5 d-flex gap-2 justify-content-center">
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Form
                </a>
            </div>
        </div>
    </div>
</div>

<footer class="bg-white border-top mt-5">
    <div class="container py-3 text-center small text-muted">
        <p class="mb-0">HRMO Certificate of Completion System</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
/**
 * Generate certificate for an intern
 * Calls Python backend to populate the template
 */
function generateCertificateNow(int $internId, string $level): void
{
    $pythonDir = __DIR__ . '/python';
    $pythonExe = getPythonExecutablePath();
    
    if (!$pythonExe) {
        throw new Exception('Python not found');
    }
    
    $scriptPath = $pythonDir . '/generate.py';
    if (!file_exists($scriptPath)) {
        throw new Exception('Certificate generator not found');
    }
    
    // Run synchronously
    $cmd = escapeshellcmd("{$pythonExe} {$scriptPath} {$internId}");
    $output = shell_exec($cmd . ' 2>&1');
    
    if (strpos($output, 'Error') !== false || strpos($output, 'error') !== false) {
        throw new Exception("Certificate generation failed: {$output}");
    }
}

/**
 * Find Python executable path
 */
function getPythonExecutablePath(): ?string
{
    $possiblePaths = [
        'python.exe',
        'python3.exe',
        'C:\\Python312\\python.exe',
        'C:\\Python311\\python.exe',
        'C:\\Python310\\python.exe',
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path) || shell_exec("where {$path} 2>nul")) {
            return $path;
        }
    }
    
    return null;
}
