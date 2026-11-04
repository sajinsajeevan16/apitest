
<?php
header('Content-Type: application/json; charset=utf-8');

// API Authentication
$valid_username = "admin";
$valid_password = "admin";

if (
    !isset($_SERVER['PHP_AUTH_USER']) ||
    !isset($_SERVER['PHP_AUTH_PW']) ||
    !hash_equals($valid_username, $_SERVER['PHP_AUTH_USER']) ||
    !hash_equals($valid_password, $_SERVER['PHP_AUTH_PW'])
) {
    header('WWW-Authenticate: Basic realm="API Authentication"');
    http_response_code(401);

    echo json_encode([
        "status" => "error",
        "message" => "Unauthorized access"
    ], JSON_PRETTY_PRINT);
    exit;
}

// Database connection
$con = mysqli_connect(
    "localhost",
    "root",
    "root",
    "testapi"
);

if (!$con) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed"
    ]);
    exit;
}

// Only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Only POST method is allowed"
    ]);
    exit;
}

// Read JSON request
$data = json_decode(file_get_contents("php://input"), true);

if (
    !is_array($data) ||
    empty($data['name']) ||
    !isset($data['age']) ||
    empty($data['email'])
) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Name, age and email are required"
    ]);
    exit;
}

$name = trim($data['name']);
$age = filter_var($data['age'], FILTER_VALIDATE_INT);
$email = trim($data['email']);

if ($name === "" || $age === false || $age < 0 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Invalid name, age or email"
    ]);
    exit;
}

// Insert into database
$stmt = mysqli_prepare(
    $con,
    "INSERT INTO person (name, age, email) VALUES (?, ?, ?)"
);

mysqli_stmt_bind_param($stmt, "sis", $name, $age, $email);

if (mysqli_stmt_execute($stmt)) {
    http_response_code(201);

    echo json_encode([
        "status" => "success",
        "message" => "Person inserted successfully",
        "data" => [
            "id" => mysqli_insert_id($con),
            "name" => $name,
            "age" => $age,
            "email" => $email
        ]
    ], JSON_PRETTY_PRINT);
} else {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database insert failed"
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($con);
?>
