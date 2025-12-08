<?php
/**
 * SHOES STORE - Database Export for Ezyro Hosting
 * This removes problematic VIEWs that Ezyro doesn't support
 * 
 * Run this in your local phpMyAdmin to export a compatible SQL file
 */

// After exporting your database as SQL, use this to clean it:
// 1. Download shoestore.sql from phpMyAdmin
// 2. Open shoestore.sql in a text editor
// 3. Remove these lines:

// Find and DELETE everything between these markers:
// =====================
// /*!50001 CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `rider_avg_rating`
// ... (entire VIEW definition)
// ... until you see /*!50001 DROP TABLE IF EXISTS ... */

// Then SAVE the file and re-import to Ezyro

// OR use this PHP script to do it automatically:

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sql_file'])) {
    $file = file_get_contents($_FILES['sql_file']['tmp_name']);
    
    // Remove all CREATE VIEW statements
    $file = preg_replace('/\/\*!50001.*?CREATE ALGORITHM=UNDEFINED.*?DEFINER=.*?VIEW.*?\*\/;/s', '', $file);
    $file = preg_replace('/CREATE ALGORITHM=UNDEFINED.*?DEFINER=.*?VIEW.*?;/s', '', $file);
    $file = preg_replace('/\/\*!50001 DROP TABLE IF EXISTS `rider_avg_rating`\*\/;/s', '', $file);
    
    // Clean up extra blank lines
    $file = preg_replace('/\n\n+/', "\n\n", $file);
    
    // Output cleaned file
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="shoestore_clean.sql"');
    echo $file;
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Clean SQL for Ezyro</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .box { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="box">
        <h2>🔧 Clean SQL File for Ezyro Import</h2>
        <p>This tool removes VIEWs that Ezyro doesn't support.</p>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="sql_file" required accept=".sql">
            <button type="submit" style="padding: 10px 20px; background: #ff6600; color: white; border: none; cursor: pointer;">
                Clean & Download
            </button>
        </form>
        
        <hr>
        
        <h3>Manual Method:</h3>
        <ol>
            <li>Open your <code>shoestore.sql</code> file in Notepad/VSCode</li>
            <li>Search for: <code>CREATE ALGORITHM=UNDEFINED</code></li>
            <li>Delete all lines from that line until you find the next <code>/*!50001</code> section</li>
            <li>Delete the entire VIEW definition block</li>
            <li>Save the file</li>
            <li>Re-import to Ezyro phpMyAdmin</li>
        </ol>
    </div>
</body>
</html>
