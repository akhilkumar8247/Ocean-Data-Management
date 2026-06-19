<?php
header('Content-Type: application/json');
require 'db.php';

try {
    // 1. Get request counts by status
    $reqStatusStmt = $pdo->query("
        SELECT 
            s.status_code AS statusCode,
            s.status_name AS statusName,
            COUNT(r.request_id) AS count
        FROM t_request_status s
        LEFT JOIN t_data_request r ON s.status_code = r.status_code
        GROUP BY s.status_code, s.status_name
        ORDER BY s.status_code
    ");
    $requestsByStatus = $reqStatusStmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Get reception counts by status
    $recStatusStmt = $pdo->query("
        SELECT 
            s.status_code AS statusCode,
            s.status_name AS statusName,
            COUNT(r.reception_id) AS count
        FROM t_reception_status s
        LEFT JOIN t_data_reception r ON s.status_code = r.status_code
        GROUP BY s.status_code, s.status_name
        ORDER BY s.status_code
    ");
    $receptionsByStatus = $recStatusStmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Get counts by category
    $catStmt = $pdo->query("
        SELECT 
            c.category_code AS categoryCode,
            c.category_name AS categoryName,
            (SELECT COUNT(*) FROM t_data_request WHERE category_code = c.category_code) +
            (SELECT COUNT(*) FROM t_data_reception WHERE category_code = c.category_code) AS count
        FROM t_category c
        ORDER BY c.category_name
    ");
    $countsByCategory = $catStmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Get program counts
    $progStmt = $pdo->query("
        SELECT 
            p.prg_code AS prgmCode,
            p.prg_name AS prgmName,
            (SELECT COUNT(*) FROM t_datasets_requested WHERE prg_code = p.prg_code) +
            (SELECT COUNT(*) FROM t_datasets_received WHERE prg_code = p.prg_code) AS count
        FROM t_programme p
        ORDER BY p.prg_name
    ");
    $programCounts = $progStmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Get monthly requests trend
    $reqMonthStmt = $pdo->query("
        SELECT 
            DATE_FORMAT(date_received, '%Y-%m') AS month,
            COUNT(request_id) AS count
        FROM t_data_request
        WHERE date_received IS NOT NULL
        GROUP BY DATE_FORMAT(date_received, '%Y-%m')
        ORDER BY month ASC
    ");
    $requestsByMonth = $reqMonthStmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Get monthly receptions trend
    $recMonthStmt = $pdo->query("
        SELECT 
            DATE_FORMAT(date_received, '%Y-%m') AS month,
            COUNT(reception_id) AS count
        FROM t_data_reception
        WHERE date_received IS NOT NULL
        GROUP BY DATE_FORMAT(date_received, '%Y-%m')
        ORDER BY month ASC
    ");
    $receptionsByMonth = $recMonthStmt->fetchAll(PDO::FETCH_ASSOC);

    // Send response
    echo json_encode([
        'requestsByStatus' => $requestsByStatus,
        'receptionsByStatus' => $receptionsByStatus,
        'countsByCategory' => $countsByCategory,
        'programCounts' => $programCounts,
        'requestsByMonth' => $requestsByMonth,
        'receptionsByMonth' => $receptionsByMonth
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>