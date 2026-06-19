<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // GET (Read organizations with search, sorting, and pagination)
        $search    = isset($_GET['search'])    ? trim($_GET['search'])    : '';
        $sortBy    = isset($_GET['sortBy'])    ? trim($_GET['sortBy'])    : 'orgName';
        $sortOrder = isset($_GET['sortOrder']) && strtolower($_GET['sortOrder']) === 'desc' ? 'DESC' : 'ASC';
        
        $page   = isset($_GET['page'])  ? max(1, intval($_GET['page']))  : 1;
        $limit  = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
        $offset = ($page - 1) * $limit;
        
        $allowedSorts = [
            'orgCode'     => 'o.org_code',
            'orgName'     => 'o.org_name',
            'address'     => 'o.address',
            'countryName' => 'co.country_name'
        ];
        $orderByCol = isset($allowedSorts[$sortBy]) ? $allowedSorts[$sortBy] : 'o.org_name';
        
        $where = ["1 = 1"];
        $params = [];
        
        if (!empty($search)) {
            $where[] = "(
                o.org_code LIKE :search1 
                OR o.org_name LIKE :search2 
                OR o.address LIKE :search3 
                OR co.country_name LIKE :search4
            )";
            $searchVal = '%' . $search . '%';
            $params['search1'] = $searchVal;
            $params['search2'] = $searchVal;
            $params['search3'] = $searchVal;
            $params['search4'] = $searchVal;
        }
        
        $whereClause = implode(" AND ", $where);
        
        // Count total
        $countQuery = "
            SELECT COUNT(*) 
            FROM `t_organization` o
            JOIN `t_country` co ON o.country_code = co.country_code
            WHERE $whereClause
        ";
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute($params);
        $totalItems = (int)$countStmt->fetchColumn();
        
        // Fetch rows
        $query = "
            SELECT 
                o.org_code     AS orgCode,
                o.org_name     AS orgName,
                o.address      AS address,
                o.country_code AS countryCode,
                co.country_name AS countryName
            FROM `t_organization` o
            JOIN `t_country` co ON o.country_code = co.country_code
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
        $organizations = $stmt->fetchAll();
        
        sendResponse([
            "data"       => $organizations,
            "total"      => $totalItems,
            "page"       => $page,
            "limit"      => $limit,
            "totalPages" => ceil($totalItems / $limit)
        ]);
        
    } elseif ($method === 'POST') {
        // POST (Create Organization)
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            sendError("Invalid input format.");
        }
        
        $org_code     = isset($input['orgCode'])     ? trim($input['orgCode'])        : null;
        $org_name     = isset($input['orgName'])     ? trim($input['orgName'])        : '';
        $address      = isset($input['address'])     ? trim($input['address'])        : '';
        $country_code = isset($input['countryCode']) ? intval($input['countryCode']) : null;
        
        // Validations
        if (empty($org_code)) {
            sendError("Organization Code is required.");
        }
        if (empty($org_name)) {
            sendError("Organization Name is required.");
        }
        if (!preg_match('/^[A-Za-z0-9 _-]+$/', $org_name)) {
            sendError("Organization Name can only contain alphanumeric characters, spaces, underscores, and hyphens.");
        }
        if (empty($address)) {
            sendError("Address is required.");
        }
        if ($country_code === null) {
            sendError("Country is required.");
        }
        
        // Verify country exists
        $stmt = $pdo->prepare("SELECT country_name FROM `t_country` WHERE country_code = :cc");
        $stmt->execute(['cc' => $country_code]);
        $country_name = $stmt->fetchColumn();
        if (!$country_name) {
            sendError("Selected country does not exist.");
        }
        
        // Check if organization code exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `t_organization` WHERE org_code = :code");
        $stmt->execute(['code' => $org_code]);
        if ($stmt->fetchColumn() > 0) {
            sendError("Organization Code $org_code already exists.");
        }
        
        // Insert
        $stmt = $pdo->prepare("INSERT INTO `t_organization` (org_code, org_name, address, country_code) VALUES (:code, :name, :address, :country_code)");
        $stmt->execute([
            'code'         => $org_code,
            'name'         => $org_name,
            'address'      => $address,
            'country_code' => $country_code
        ]);
        
        sendResponse([
            "orgCode"     => $org_code,
            "orgName"     => $org_name,
            "address"     => $address,
            "countryCode" => $country_code,
            "countryName" => $country_name
        ], 201);
        
    } elseif ($method === 'PUT') {
        // PUT (Update Organization)
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            sendError("Invalid input format.");
        }
        
        $old_org_code = isset($input['oldOrgCode']) ? trim($input['oldOrgCode'])     : '';
        $org_code     = isset($input['orgCode'])     ? trim($input['orgCode'])        : '';
        $org_name     = isset($input['orgName'])     ? trim($input['orgName'])        : '';
        $address      = isset($input['address'])     ? trim($input['address'])        : '';
        $country_code = isset($input['countryCode']) ? intval($input['countryCode']) : null;
        
        if (empty($old_org_code)) {
            sendError("Original Organization Code is required.");
        }
        if (empty($org_code)) {
            sendError("Organization Code is required.");
        }
        if (empty($org_name)) {
            sendError("Organization Name is required.");
        }
        if (!preg_match('/^[A-Za-z0-9 _-]+$/', $org_name)) {
            sendError("Organization Name can only contain alphanumeric characters, spaces, underscores, and hyphens.");
        }
        if (empty($address)) {
            sendError("Address is required.");
        }
        if ($country_code === null) {
            sendError("Country is required.");
        }
        
        // Verify original organization exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `t_organization` WHERE org_code = :code");
        $stmt->execute(['code' => $old_org_code]);
        if ($stmt->fetchColumn() == 0) {
            sendError("Original organization not found.", 404);
        }
        
        // If org_code is changing, check if the new code already exists
        if ($old_org_code !== $org_code) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `t_organization` WHERE org_code = :code");
            $stmt->execute(['code' => $org_code]);
            if ($stmt->fetchColumn() > 0) {
                sendError("New Organization Code $org_code already exists.");
            }
        }
        
        // Verify country exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `t_country` WHERE country_code = :cc");
        $stmt->execute(['cc' => $country_code]);
        if ($stmt->fetchColumn() == 0) {
            sendError("Selected country does not exist.");
        }
        
        // Update
        $stmt = $pdo->prepare("UPDATE `t_organization` SET org_code = :new_code, org_name = :name, address = :address, country_code = :country_code WHERE org_code = :old_code");
        $stmt->execute([
            'new_code'     => $org_code,
            'name'         => $org_name,
            'address'      => $address,
            'country_code' => $country_code,
            'old_code'     => $old_org_code
        ]);
        
        sendResponse(["message" => "Organization updated successfully."]);
        
    } elseif ($method === 'DELETE') {
        // DELETE (Delete Organization)
        $org_code = isset($_GET['orgCode']) ? trim($_GET['orgCode']) : '';
        if (empty($org_code)) {
            sendError("Organization Code is required for deletion.");
        }
        
        // Check if there are associated contacts
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `t_contact` WHERE org_code = :code");
        $stmt->execute(['code' => $org_code]);
        if ($stmt->fetchColumn() > 0) {
            sendError("Cannot delete organization because it has associated contacts.");
        }
        
        $stmt = $pdo->prepare("DELETE FROM `t_organization` WHERE org_code = :code");
        $stmt->execute(['code' => $org_code]);
        
        sendResponse(["message" => "Organization deleted successfully."]);
        
    } else {
        sendError("Method not allowed", 405);
    }
} catch (PDOException $e) {
    sendError("Database error: " . $e->getMessage(), 500);
}
