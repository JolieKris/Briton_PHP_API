<?php
header("Content-Type: application/json");

$host = 'localhost';
$db = 'mydb';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->query("SELECT users.userId, username, email, bio FROM users LEFT JOIN profiles ON users.userId = profiles.userId");
        $data = $stmt->fetchAll();
        echo json_encode($data);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (isset($input['username'], $input['pass'], $input['email'], $input['bio'])) {
            $pdo->beginTransaction();

            try {
                $sql = "INSERT INTO users (username, pass, email) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$input['username'], $input['pass'], $input['email']]);
                $userId = $pdo->lastInsertId();

                $sql = "INSERT INTO profiles (userId, bio) VALUES (?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$userId, $input['bio']]);

                $pdo->commit();
                echo json_encode(['message' => 'User and profile added successfully']);
            } catch (PDOException $e) {
                $pdo->rollBack();
                http_response_code(500);
                echo json_encode(['error' => 'Transaction failed: ' . $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input']);
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
