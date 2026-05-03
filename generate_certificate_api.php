<?php
/**
 * Certificate Generation Endpoint
 * Generates certificate using Python backend when form data is submitted
 */

declare(strict_types=1);

session_start();
require_once __DIR__ . '/app/config.php';

// Only process POST requests or ID requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

// If ID is provided via GET, just return certificate info
if (isset($_GET['id'])) {
    $internId = (int) $_GET['id'];
    
    try {
        $stmt = db()->prepare('SELECT * FROM interns WHERE id = ?');
        $stmt->execute([$internId]);
        $intern = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$intern) {
            http_response_code(404);
            echo json_encode(['error' => 'Intern not found']);
            exit;
        }
        
        // Generate certificate using Python
        $result = generateCertificateFromDB($internId, $intern['intern_level']);
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Certificate generated',
                'file' => basename($result['file']),
                'download_url' => '/Certificates/' . basename($result['file']),
                'intern' => $intern
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to generate certificate'
            ]);
        }
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// POST request - would be handled by existing form submission
http_response_code(400);
echo json_encode(['error' => 'Use ID parameter to generate certificate']);


/**
 * Generate certificate using Python backend
 * 
 * @param int $internId
 * @param string $level college|shs
 * @return array
 */
function generateCertificateFromDB(int $internId, string $level): array
{
    $pythonDir = __DIR__ . '/python';
    $pythonExe = getPythonExecutable();
    
    if (!$pythonExe) {
        return [
            'success' => false,
            'error' => 'Python not found on system'
        ];
    }
    
    $scriptPath = $pythonDir . '/api_handler.py';
    
    if (!file_exists($scriptPath)) {
        return [
            'success' => false,
            'error' => 'Certificate generator not found'
        ];
    }
    
    try {
        $cmd = escapeshellcmd("{$pythonExe} {$scriptPath} generate {$internId} {$level}");
        $output = shell_exec($cmd);
        
        if ($output === null) {
            return [
                'success' => false,
                'error' => 'Failed to execute certificate generator'
            ];
        }
        
        $result = json_decode($output, true);
        
        if ($result && $result['success']) {
            return [
                'success' => true,
                'file' => $result['path'] ?? ''
            ];
        }
        
        return [
            'success' => false,
            'error' => $result['message'] ?? 'Unknown error'
        ];
        
    } catch (Throwable $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Find Python executable
 */
function getPythonExecutable(): ?string
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
