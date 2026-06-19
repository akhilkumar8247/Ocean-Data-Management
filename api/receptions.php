<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // 1. GET (Read all receptions with filters, search, sorting, pagination)
        $search       = isset($_GET['search'])       ? trim($_GET['search'])       : '';
        $categoryCode = isset($_GET['categoryCode']) && $_GET['categoryCode'] !== '' ? trim($_GET['categoryCode']) : null;
        $statusCode   = isset($_GET['statusCode'])   && $_GET['statusCode']   !== '' ? trim($_GET['statusCode'])   : null;
        $dateStart    = isset($_GET['dateStart'])    ? trim($_GET['dateStart'])    : '';
        $dateEnd      = isset($_GET['dateEnd'])      ? trim($_GET['dateEnd'])      : '';
        
        $sortBy    = isset($_GET['sortBy'])    ? trim($_GET['sortBy'])    : 'receptionId';
        $sortOrder = isset($_GET['sortOrder']) && strtolower($_GET['sortOrder']) === 'desc' ? 'DESC' : 'ASC';
        
        $page   = isset($_GET['page'])  ? max(1, intval($_GET['page']))  : 1;
        $limit  = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
        $offset = ($page - 1) * $limit;
        
        // Allowed sort columns map (frontend key => SQL column)
        $allowedSorts = [
            'receptionId'  => 'r.reception_id',
            'cName'        => 'c.contact_name',
            'emailId'      => 'c.email_id',
            'orgName'      => 'o.org_name',
            'countryName'  => 'co.country_name',
            'categoryName' => 'cat.category_name',
            'dateReceived' => 'r.date_received',
            'dateProvided' => 'r.date_provided',
            'statusName'   => 's.status_name',
            'remarks'      => 'r.remarks',
            'size'         => 'r.size',
            'cost'         => 'r.cost'
        ];
        $orderByCol = isset($allowedSorts[$sortBy]) ? $allowedSorts[$sortBy] : 'r.reception_id';
        
        // Build query conditions
        $where  = ["1 = 1"];
        $params = [];
        
        if (!empty($search)) {
            $where[] = "(
                c.contact_name LIKE :search1 
                OR c.email_id LIKE :search2 
                OR o.org_name LIKE :search3 
                OR co.country_name LIKE :search4 
                OR r.remarks LIKE :search5 
                OR r.reception_id LIKE :search6
            )";
            $searchVal = '%' . $search . '%';
            $params['search1'] = $searchVal;
            $params['search2'] = $searchVal;
            $params['search3'] = $searchVal;
            $params['search4'] = $searchVal;
            $params['search5'] = $searchVal;
            $params['search6'] = $searchVal;
        }
        
        if ($categoryCode !== null) {
            $where[] = "r.category_code = :categoryCode";
            $params['categoryCode'] = $categoryCode;
        }
        
        if ($statusCode !== null) {
            $where[] = "r.status_code = :statusCode";
            $params['statusCode'] = $statusCode;
        }
        
        if (!empty($dateStart)) {
            $where[] = "r.date_received >= :dateStart";
            $params['dateStart'] = $dateStart;
        }
        
        if (!empty($dateEnd)) {
            $where[] = "r.date_received <= :dateEnd";
            $params['dateEnd'] = $dateEnd;
        }
        
        $whereClause = implode(" AND ", $where);
        
        // Count total
        $countQuery = "
            SELECT COUNT(*) 
            FROM `t_data_reception` r
            JOIN `t_contact` c ON r.cid = c.cid
            JOIN `t_organization` o ON c.org_code = o.org_code
            JOIN `t_country` co ON o.country_code = co.country_code
            JOIN `t_category` cat ON r.category_code = cat.category_code
            JOIN `t_reception_status` s ON r.status_code = s.status_code
            WHERE $whereClause
        ";
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute($params);
        $totalItems = (int)$countStmt->fetchColumn();
        
        // Fetch rows - use AS aliases to keep camelCase for frontend
        $query = "
            SELECT 
                r.reception_id   AS receptionId,
                r.cid            AS cId,
                c.contact_name   AS cName,
                c.email_id       AS emailId,
                o.org_name       AS orgName,
                co.country_name  AS countryName,
                r.date_received  AS dateReceived,
                r.date_provided  AS dateProvided,
                r.category_code  AS categoryCode,
                cat.category_name AS categoryName,
                r.details,
                r.size,
                r.cost,
                r.status_code    AS statusCode,
                s.status_name    AS statusName,
                r.remarks
            FROM `t_data_reception` r
            JOIN `t_contact` c ON r.cid = c.cid
            JOIN `t_organization` o ON c.org_code = o.org_code
            JOIN `t_country` co ON o.country_code = co.country_code
            JOIN `t_category` cat ON r.category_code = cat.category_code
            JOIN `t_reception_status` s ON r.status_code = s.status_code
            WHERE $whereClause
            ORDER BY $orderByCol $sortOrder
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        $stmt->execute();
        $receptions = $stmt->fetchAll();
        
        // Fetch programs for all fetched receptions
        if (count($receptions) > 0) {
            $receptionIds = array_column($receptions, 'receptionId');
            $placeholders = implode(',', array_fill(0, count($receptionIds), '?'));
            
            $progQuery = "
                SELECT dr.reception_id AS receptionId, dr.prg_code AS prgmCode, p.prg_name AS prgmName
                FROM `t_datasets_received` dr
                JOIN `t_programme` p ON dr.prg_code = p.prg_code
                WHERE dr.reception_id IN ($placeholders)
            ";
            $progStmt = $pdo->prepare($progQuery);
            $progStmt->execute($receptionIds);
            $allPrograms = $progStmt->fetchAll();
            
            // Map programs to receptions
            $progMap = [];
            foreach ($allPrograms as $prog) {
                $progMap[$prog['receptionId']][] = [
                    'prgmCode' => $prog['prgmCode'],
                    'prgmName' => $prog['prgmName']
                ];
            }
            
            foreach ($receptions as &$rec) {
                $recId = $rec['receptionId'];
                $rec['programs']     = isset($progMap[$recId]) ? $progMap[$recId] : [];
                $rec['programCodes'] = array_column($rec['programs'], 'prgmCode');
            }
        }
        
        sendResponse([
            "data"       => $receptions,
            "total"      => $totalItems,
            "page"       => $page,
            "limit"      => $limit,
            "totalPages" => ceil($totalItems / $limit)
        ]);
        
    } elseif ($method === 'POST') {
        // 2. POST (Create)
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            sendError("Invalid input format.");
        }
        
        $cid           = isset($input['cId'])          ? intval($input['cId'])          : null;
        $date_received = isset($input['dateReceived']) ? trim($input['dateReceived'])   : '';
        $date_provided = isset($input['dateProvided']) && trim($input['dateProvided']) !== '' ? trim($input['dateProvided']) : null;
        $category_code = isset($input['categoryCode']) ? trim($input['categoryCode'])  : null;
        $details       = isset($input['details'])      ? trim($input['details'])        : null;
        $size          = isset($input['size']) && $input['size'] !== '' ? floatval($input['size']) : null;
        $cost          = isset($input['cost']) && $input['cost'] !== '' ? floatval($input['cost']) : null;
        $status_code   = isset($input['statusCode'])   ? trim($input['statusCode'])    : null;
        $remarks       = isset($input['remarks'])      ? trim($input['remarks'])        : '';
        $programCodes  = isset($input['programs'])     && is_array($input['programs']) ? $input['programs'] : [];
        
        // Validations
        if (!$cid)              sendError("Contact is required.");
        if (empty($date_received))  sendError("Date Received is required.");
        if (empty($category_code))  sendError("Category is required.");
        if (empty($status_code))    sendError("Status is required.");
        if (empty($remarks))        sendError("Remarks are required.");
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                INSERT INTO `t_data_reception` (cid, date_received, date_provided, category_code, details, size, cost, status_code, remarks) 
                VALUES (:cid, :date_received, :date_provided, :category_code, :details, :size, :cost, :status_code, :remarks)
            ");
            $stmt->execute([
                'cid'           => $cid,
                'date_received' => $date_received,
                'date_provided' => $date_provided,
                'category_code' => $category_code,
                'details'       => $details,
                'size'          => $size,
                'cost'          => $cost,
                'status_code'   => $status_code,
                'remarks'       => $remarks
            ]);
            
            $receptionId = (int)$pdo->lastInsertId();
            
            // Link programs
            if (!empty($programCodes)) {
                $progStmt = $pdo->prepare("INSERT INTO `t_datasets_received` (reception_id, prg_code) VALUES (:recId, :prg_code)");
                foreach ($programCodes as $pCode) {
                    $progStmt->execute([
                        'recId'    => $receptionId,
                        'prg_code' => trim($pCode)
                    ]);
                }
            }
            
            $pdo->commit();
            sendResponse(["message" => "Reception created successfully", "receptionId" => $receptionId], 201);
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            sendError("Failed to create reception: " . $e->getMessage(), 500);
        }
        
    } elseif ($method === 'PUT') {
        // 3. PUT (Update)
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            sendError("Invalid input format.");
        }
        
        $reception_id  = isset($input['receptionId'])  ? intval($input['receptionId'])  : null;
        $cid           = isset($input['cId'])          ? intval($input['cId'])          : null;
        $date_received = isset($input['dateReceived']) ? trim($input['dateReceived'])   : '';
        $date_provided = isset($input['dateProvided']) && trim($input['dateProvided']) !== '' ? trim($input['dateProvided']) : null;
        $category_code = isset($input['categoryCode']) ? trim($input['categoryCode'])  : null;
        $details       = isset($input['details'])      ? trim($input['details'])        : null;
        $size          = isset($input['size']) && $input['size'] !== '' ? floatval($input['size']) : null;
        $cost          = isset($input['cost']) && $input['cost'] !== '' ? floatval($input['cost']) : null;
        $status_code   = isset($input['statusCode'])   ? trim($input['statusCode'])    : null;
        $remarks       = isset($input['remarks'])      ? trim($input['remarks'])        : '';
        $programCodes  = isset($input['programs'])     && is_array($input['programs']) ? $input['programs'] : [];
        
        if (!$reception_id)     sendError("Reception ID is required for updating.");
        if (!$cid)              sendError("Contact is required.");
        if (empty($date_received))  sendError("Date Received is required.");
        if (empty($category_code))  sendError("Category is required.");
        if (empty($status_code))    sendError("Status is required.");
        if (empty($remarks))        sendError("Remarks are required.");
        
        try {
            $pdo->beginTransaction();
            
            // Check reception exists
            $check = $pdo->prepare("SELECT COUNT(*) FROM `t_data_reception` WHERE reception_id = :id");
            $check->execute(['id' => $reception_id]);
            if ($check->fetchColumn() == 0) {
                sendError("Reception not found.", 404);
            }
            
            // Update reception
            $stmt = $pdo->prepare("
                UPDATE `t_data_reception` 
                SET cid = :cid, date_received = :date_received, date_provided = :date_provided, 
                    category_code = :category_code, details = :details, size = :size, cost = :cost, status_code = :status_code, remarks = :remarks 
                WHERE reception_id = :id
            ");
            $stmt->execute([
                'id'            => $reception_id,
                'cid'           => $cid,
                'date_received' => $date_received,
                'date_provided' => $date_provided,
                'category_code' => $category_code,
                'details'       => $details,
                'size'          => $size,
                'cost'          => $cost,
                'status_code'   => $status_code,
                'remarks'       => $remarks
            ]);
            
            // Delete existing programs
            $delStmt = $pdo->prepare("DELETE FROM `t_datasets_received` WHERE reception_id = :id");
            $delStmt->execute(['id' => $reception_id]);
            
            // Link new programs
            if (!empty($programCodes)) {
                $progStmt = $pdo->prepare("INSERT INTO `t_datasets_received` (reception_id, prg_code) VALUES (:recId, :prg_code)");
                foreach ($programCodes as $pCode) {
                    $progStmt->execute([
                        'recId'    => $reception_id,
                        'prg_code' => trim($pCode)
                    ]);
                }
            }
            
            $pdo->commit();
            sendResponse(["message" => "Reception updated successfully"]);
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            sendError("Failed to update reception: " . $e->getMessage(), 500);
        }
        
    } elseif ($method === 'DELETE') {
        // 4. DELETE
        $reception_id = isset($_GET['receptionId']) ? intval($_GET['receptionId']) : (isset($_GET['reception_id']) ? intval($_GET['reception_id']) : null);
        
        if (!$reception_id) {
            sendError("Reception ID is required for deletion.");
        }
        
        try {
            $pdo->beginTransaction();
            
            // Delete program linkages first
            $delStmt = $pdo->prepare("DELETE FROM `t_datasets_received` WHERE reception_id = :id");
            $delStmt->execute(['id' => $reception_id]);
            
            // Delete reception
            $stmt = $pdo->prepare("DELETE FROM `t_data_reception` WHERE reception_id = :id");
            $stmt->execute(['id' => $reception_id]);
            
            if ($stmt->rowCount() == 0) {
                sendError("Reception not found.", 404);
            }
            
            $pdo->commit();
            sendResponse(["message" => "Reception deleted successfully"]);
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            sendError("Failed to delete reception: " . $e->getMessage(), 500);
        }
    } else {
        sendError("Method not allowed", 405);
    }
} catch (Exception $ex) {
    sendError("System error: " . $ex->getMessage(), 500);
}
