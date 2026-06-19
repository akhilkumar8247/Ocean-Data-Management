<?php
require_once 'db.php';

try {
    // Fetch Countries
    $countries = $pdo->query("SELECT country_code AS countryCode, country_name AS countryName FROM `t_country` ORDER BY country_name ASC")->fetchAll();
    
    // Fetch Organizations
    $organizations = $pdo->query("
        SELECT o.org_code AS orgCode, o.org_name AS orgName, o.address, o.country_code AS countryCode, c.country_name AS countryName 
        FROM `t_organization` o
        JOIN `t_country` c ON o.country_code = c.country_code
        ORDER BY o.org_name ASC
    ")->fetchAll();
    
    // Fetch Contacts
    $contacts = $pdo->query("
        SELECT c.cid AS cId, c.contact_name AS cName, c.email_id AS emailId, c.org_code AS orgCode, o.org_name AS orgName, co.country_name AS countryName 
        FROM `t_contact` c
        JOIN `t_organization` o ON c.org_code = o.org_code
        JOIN `t_country` co ON o.country_code = co.country_code
        ORDER BY c.contact_name ASC
    ")->fetchAll();
    
    // Fetch Categories
    $categories = $pdo->query("SELECT category_code AS categoryCode, category_name AS categoryName FROM `t_category` ORDER BY category_name ASC")->fetchAll();
    
    // Fetch Programs
    $programs = $pdo->query("SELECT prg_code AS prgmCode, prg_name AS prgmName FROM `t_programme` ORDER BY prg_name ASC")->fetchAll();
    
    // Fetch Status Request options
    $statusReq = $pdo->query("SELECT status_code AS statusCode, status_name AS statusName FROM `t_request_status` ORDER BY status_code ASC")->fetchAll();
    
    // Fetch Status Reception options
    $statusRes = $pdo->query("SELECT status_code AS statusCode, status_name AS statusName FROM `t_reception_status` ORDER BY status_code ASC")->fetchAll();
    
    sendResponse([
        "countries"     => $countries,
        "organizations" => $organizations,
        "contacts"      => $contacts,
        "categories"    => $categories,
        "programs"      => $programs,
        "statusReq"     => $statusReq,
        "statusRes"     => $statusRes
    ]);
} catch (PDOException $e) {
    sendError("Failed to fetch reference data: " . $e->getMessage(), 500);
}
