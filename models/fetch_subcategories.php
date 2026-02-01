<?php
/**
 * models/fetch_subcategories.php
 *
 * This file handles AJAX requests to fetch subcategories based on a given category ID.
 * It is used by the assign_task.php (and potentially other) pages to dynamically
 * populate subcategory dropdowns and associated fare information.
 *
 * It returns data in JSON format.
 */

// Include the configuration file for database connection.
// Adjust path as necessary: Assuming this file is in 'models/' and config.php is in the project root.
require_once __DIR__ . '/../config.php';
require_once MODELS_PATH . 'db.php';     // Database connection functions

// Set header to indicate that the response will be JSON.
header('Content-Type: application/json');

// Establish database connection.
$pdo = connectDB();

// Get the category ID from the GET request. Default to 0 if not set or invalid.
$categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

$subcategories = []; // Initialize an empty array for subcategories

// Only proceed if a valid category ID is provided.
if ($categoryId > 0) {
    try {
        // Prepare a SQL statement to select id, name, fare, and description from subcategories
        // where the category_id matches the provided ID. Order by name for consistency.
        $stmt = $pdo->prepare("SELECT id, name, fare, description FROM subcategories WHERE category_id = ? ORDER BY name ASC");
        // Execute the statement with the category ID.
        $stmt->execute([$categoryId]);
        // Fetch all matching subcategories as an associative array.
        $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Encode the fetched subcategories array as a JSON string and output it.
        echo json_encode($subcategories);
    } catch (PDOException $e) {
        // Log any database errors for debugging.
        error_log("Error fetching subcategories via AJAX: " . $e->getMessage());
        // Return a JSON error message to the client.
        echo json_encode(['error' => 'Database error fetching subcategories. Please check server logs.']);
    }
} else {
    // If no valid category ID is provided, return an empty array as JSON.
    // This allows the frontend to clear the subcategory dropdown.
    echo json_encode([]);
}
?>