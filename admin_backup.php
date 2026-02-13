<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

require_login();

if (!$_SESSION['is_admin']) {
    redirect('dashboard.php');
}

// Handle Export Action
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    // Disable time limit for large exports
    set_time_limit(0); 
    
    $filename = 'backup_listemoon_' . date('Y-m-d_H-i-s') . '.sql';
    
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $out = fopen('php://output', 'w');
    
    // Header
    fwrite($out, "-- List-e-Moon Database Backup\n");
    fwrite($out, "-- Date: " . date('Y-m-d H:i:s') . "\n\n");
    fwrite($out, "SET FOREIGN_KEY_CHECKS=0;\n\n");
    
    // Get Tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        fwrite($out, "-- Table structure for `$table`\n");
        $create_sql = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1];
        fwrite($out, "DROP TABLE IF EXISTS `$table`;\n");
        fwrite($out, $create_sql . ";\n\n");
        
        // Data
        fwrite($out, "-- Dumping data for `$table`\n");
        $rows = $pdo->query("SELECT * FROM `$table`");
        while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
            $cols = array_keys($row);
            $vals = array_map(function($val) use ($pdo) {
                if ($val === null) return "NULL";
                return $pdo->quote($val);
            }, array_values($row));
            
            $sql = "INSERT INTO `$table` (`" . implode("`, `", $cols) . "`) VALUES (" . implode(", ", $vals) . ");\n";
            fwrite($out, $sql);
        }
        fwrite($out, "\n");
    }
    
    fwrite($out, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($out);
    exit;
}

// Handle Import Action
$message = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sql_file'])) {
    if ($_FILES['sql_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['sql_file']['tmp_name'];
        // Simple parsing: Read file, split by semicolon (carefully?)
        // Better: Read line by line, accumulate until semicolon at end of line? 
        // Logic: Standard mysqldumps usually put one statement per line or use clear delimiters.
        // We act generally safe by reading whole file (memory limit risk?) -> No, stream it.
        
        $fp = fopen($file, 'r');
        $query = '';
        
        $pdo->beginTransaction();
        try {
            while (($line = fgets($fp)) !== false) {
                $line = trim($line);
                if (empty($line) || strpos($line, '--') === 0 || strpos($line, '/*') === 0) continue;
                
                $query .= $line;
                if (substr(rtrim($query), -1) === ';') {
                    $pdo->exec($query);
                    $query = '';
                }
            }
            $pdo->commit();
            $message = "Database restored successfully!";
            $msg_type = 'success';
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = "Error restoring database: " . $e->getMessage();
            $msg_type = 'error';
        }
        fclose($fp);
    } else {
        $message = "Upload failed.";
        $msg_type = 'error';
    }
}

$page_title = 'Backups';
require_once 'includes/header.php';
?>

<div class="container mx-auto p-6 max-w-4xl">
    <div class="bg-slate-800 p-6 rounded-lg border border-slate-700 shadow-md">
        <h2 class="text-2xl font-bold mb-6 text-purple-400 border-b border-slate-700 pb-2">Database Backup System</h2>
        
        <?php if ($message): ?>
             <div class="mb-6 p-4 rounded <?php echo $msg_type === 'error' ? 'bg-red-500/20 text-red-200 border border-red-500/50' : 'bg-green-500/20 text-green-200 border border-green-500/50'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Export -->
            <div class="bg-slate-900 p-6 rounded border border-slate-700">
                <h3 class="text-lg font-bold mb-4 text-white">Export Database</h3>
                <p class="text-slate-400 text-sm mb-6">Download a full SQL dump of the database (structure + data).</p>
                <a href="admin_backup.php?action=export" class="inline-block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded text-center transition-colors">
                    Download .SQL Backup
                </a>
            </div>

            <!-- Import -->
            <div class="bg-slate-900 p-6 rounded border border-slate-700">
                <h3 class="text-lg font-bold mb-4 text-white">Restore Database</h3>
                <p class="text-slate-400 text-sm mb-4 text-red-400">Warning: This will overwrite existing data!</p>
                
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="file" name="sql_file" accept=".sql" required class="block w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-slate-700 file:text-white hover:file:bg-slate-600">
                    <button type="submit" onclick="return confirm('WARNING: This will delete current data and restore from backup. Are you sure?')" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded transition-colors">
                        Restore from File
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
