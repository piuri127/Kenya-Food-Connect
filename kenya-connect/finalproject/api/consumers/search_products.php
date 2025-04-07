<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Include database connection
require_once 'C:/Users/Purity/Desktop/php/htdocs/kenya-connect/finalproject/php/config.php';

// Check if user is logged in and is a consumer
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'consumer') {
    http_response_code(401);
    echo json_encode([
        'error' => 'Unauthorized',
        'message' => 'You must be logged in as a consumer'
    ]);
    exit();
}

// Get consumer's city
$consumer_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT city FROM consumers WHERE id = ?");
$stmt->bind_param("i", $consumer_id);
$stmt->execute();
$result = $stmt->get_result();
$consumer = $result->fetch_assoc();

// Pagination and filtering
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$results_per_page = 12;
$offset = ($page - 1) * $results_per_page;

// Prepare base query
$base_query = "
    SELECT p.*, v.business_name, v.area, v.city 
    FROM products p
    JOIN vendors v ON p.vendor_id = v.id
    WHERE p.status = 'available' 
    AND p.expiration_date >= CURDATE()
    AND v.city = ?
";
$params = [&$consumer['city']];
$param_types = "s";

// Add search query filter
if (!empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $base_query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = &$search;
    $params[] = &$search;
    $param_types .= "ss";
}

// Add category filter
if (!empty($_GET['category'])) {
    $category = $_GET['category'];
    $base_query .= " AND p.category = ?";
    $params[] = &$category;
    $param_types .= "s";
}

// Add listing type filter
if (!empty($_GET['listing_type'])) {
    $listing_type = $_GET['listing_type'];
    $base_query .= " AND p.listing_type = ?";
    $params[] = &$listing_type;
    $param_types .= "s";
}

// Count total results
$count_query = "SELECT COUNT(*) as total FROM ($base_query) as sub";
$count_stmt = $conn->prepare($count_query);
call_user_func_array([$count_stmt, 'bind_param'], array_merge([$param_types], $params));
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_count = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_count / $results_per_page);

// Add pagination to query
$paginated_query = $base_query . " LIMIT ? OFFSET ?";
$params[] = &$results_per_page;
$params[] = &$offset;
$param_types .= "ii";

// Prepare and execute final query
$stmt = $conn->prepare($paginated_query);
call_user_func_array([$stmt, 'bind_param'], array_merge([$param_types], $params));
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);

// Prepare response
$response = [
    'products' => $products,
    'total_pages' => $total_pages,
    'current_page' => $page,
    'total_products' => $total_count
];

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit();
?>