<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // GET (Read contacts with search, sorting, and pagination)
        $search    = isset($_GET['search'])    ? trim($_GET['search'])    : '';
        $sortBy    = isset($_GET['sortBy'])    ? trim($_GET['sortBy'])    : 'cId';
        $sortOrder = isset($_GET['sortOrder']) && strtolower($_GET['sortOrder']) === 'desc' ? 'DESC' : 'ASC';
        
        $page   = isset($_GET['page'])  ? max(1, intval($_GET['page']))  : 1;
        $limit  = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
        $offset = ($page - 1) * $limit;
        
        $allowedSorts = [
            'cId'         => 'c.cid',
            'cName'       => 'c.contact_name',
            'emailId'     => 'c.email_id',
            'orgCode'     => 'c.org_code',
            'orgName'     => 'o.org_name',
            'countryName' => 'co.country_name'
        ];
        $orderByCol = isset($allowedSorts[$sortBy]) ? $allowedSorts[$sortBy] : 'c.cid';
        
        $where = ["1 = 1"];
        $params = [];
        
        if (!empty($search)) {
            $where[] = "(
                c.contact_name LIKE :search1 
                OR c.email_id LIKE :search2 
                OR o.org_name LIKE :search3 
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
            FROM `t_contact` c
            JOIN `t_organization` o ON c.org_code = o.org_code
            JOIN `t_country` co ON o.country_code = co.country_code
            WHERE $whereClause
        ";
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute($params);
        $totalItems = (int)$countStmt->fetchColumn();
        
        // Fetch contacts
        $query = "
            SELECT 
                c.cid          AS cId,
                c.contact_name AS cName,
                c.email_id     AS emailId,
                c.org_code     AS orgCode,
                o.org_name     AS orgName,
                co.country_name AS countryName
            FROM `t_contact` c
            JOIN `t_organization` o ON c.org_code = o.org_code
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
        $contacts = $stmt->fetchAll();
        
        sendResponse([
            "data"       => $contacts,
            "total"      => $totalItems,
            "page"       => $page,
            "limit"      => $limit,
            "totalPages" => ceil($totalItems / $limit)
        ]);
        
    } elseif ($method === 'POST') {
        // POST (Create Contact)
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            sendError("Invalid input format.");
        }
        
        $contact_name = isset($input['cName'])   ? trim($input['cName'])   : '';
        $email_id     = isset($input['emailId']) ? trim($input['emailId']) : '';
        $org_code     = isset($input['orgCode']) ? trim($input['orgCode']) : null;
        
        // Validations
        if (empty($contact_name)) {
            sendError("Contact Name is required.");
        }
        if (!preg_match('/^[A-Za-z. ]+$/', $contact_name)) {
            sendError("Contact Name can only contain letters, spaces, and dots.");
        }
        if (empty($email_id)) {
            sendError("Email is required.");
        }
        if (!preg_match('/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.(com|in|org|net)$/', $email_id)) {
            sendError("Email must be valid and end with .com, .in, .org, or .net.");
        }
        if (empty($org_code)) {
            sendError("Organization is required.");
        }
        
        // Verify organization exists
        $stmt = $pdo->prepare("
            SELECT o.org_name, c.country_name 
            FROM `t_organization` o
            JOIN `t_country` c ON o.country_code = c.country_code
            WHERE o.org_code = :oc
        ");
        $stmt->execute(['oc' => $org_code]);
        $org = $stmt->fetch();
        if (!$org) {
            sendError("Selected organization does not exist.");
        }
        
        // Insert
        $stmt = $pdo->prepare("INSERT INTO `t_contact` (contact_name, email_id, org_code) VALUES (:name, :email, :org_code)");
        $stmt->execute([
            'name'     => $contact_name,
            'email'    => $email_id,
            'org_code' => $org_code
        ]);
        
        $cid = (int)$pdo->lastInsertId();
        
        sendResponse([
            "cId"         => $cid,
            "cName"       => $contact_name,
            "emailId"     => $email_id,
            "orgCode"     => $org_code,
            "orgName"     => $org['org_name'],
            "countryName" => $org['country_name']
        ], 201);
        
    } elseif ($method === 'PUT') {
        // PUT (Update Contact details including name, email, and organization)
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            sendError("Invalid input format.");
        }
        
        $cid          = isset($input['cId'])         ? intval($input['cId'])   : null;
        $contact_name = isset($input['cName'])       ? trim($input['cName'])   : '';
        $email_id     = isset($input['emailId'])     ? trim($input['emailId']) : '';
        $org_code     = isset($input['orgCode'])     ? trim($input['orgCode']) : '';
        
        if (!$cid) {
            sendError("Contact ID is required.");
        }
        
        // Fetch current values
        $stmt = $pdo->prepare("SELECT contact_name, email_id, org_code FROM `t_contact` WHERE cid = :cid");
        $stmt->execute(['cid' => $cid]);
        $existingContact = $stmt->fetch();
        if (!$existingContact) {
            sendError("Contact not found.", 404);
        }
        
        // Use existing values if not specified
        if ($contact_name === '') {
            $contact_name = $existingContact['contact_name'];
        } else {
            if (!preg_match('/^[A-Za-z. ]+$/', $contact_name)) {
                sendError("Contact Name can only contain letters, spaces, and dots.");
            }
        }
        
        if ($email_id === '') {
            $email_id = $existingContact['email_id'];
        } else {
            if (!preg_match('/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.(com|in|org|net)$/', $email_id)) {
                sendError("Email must be valid and end with .com, .in, .org, or .net.");
            }
        }
        
        if ($org_code === '') {
            $org_code = $existingContact['org_code'];
        } else {
            // Verify organization exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `t_organization` WHERE org_code = :oc");
            $stmt->execute(['oc' => $org_code]);
            if ($stmt->fetchColumn() == 0) {
                sendError("Selected organization does not exist.");
            }
        }
        
        // Update contact
        $stmt = $pdo->prepare("UPDATE `t_contact` SET contact_name = :name, email_id = :email, org_code = :org_code WHERE cid = :cid");
        $stmt->execute([
            'name'     => $contact_name,
            'email'    => $email_id,
            'org_code' => $org_code,
            'cid'      => $cid
        ]);
        
        sendResponse(["message" => "Contact details updated successfully."]);
        
    } elseif ($method === 'DELETE') {
        // DELETE (Delete Contact)
        $cid = isset($_GET['cId']) ? intval($_GET['cId']) : null;
        if (!$cid) {
            sendError("Contact ID is required for deletion.");
        }
        
        // Check references in t_data_request
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `t_data_request` WHERE cid = :cid");
        $stmt->execute(['cid' => $cid]);
        if ($stmt->fetchColumn() > 0) {
            sendError("Cannot delete contact because they are referenced in existing data requests.");
        }
        
        // Check references in t_data_reception
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `t_data_reception` WHERE cid = :cid");
        $stmt->execute(['cid' => $cid]);
        if ($stmt->fetchColumn() > 0) {
            sendError("Cannot delete contact because they are referenced in existing data receptions.");
        }
        
        $stmt = $pdo->prepare("DELETE FROM `t_contact` WHERE cid = :cid");
        $stmt->execute(['cid' => $cid]);
        
        sendResponse(["message" => "Contact deleted successfully."]);
        
    } else {
        sendError("Method not allowed", 405);
    }
} catch (PDOException $e) {
    sendError("Database error: " . $e->getMessage(), 500);
}
