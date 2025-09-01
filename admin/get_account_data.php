<?php
include 'auth.php';
include '../db.php';

if (isset($_GET['id'])) {
    $account_id = (int)$_GET['id'];
    
    $sql = "SELECT * FROM chart_of_accounts WHERE id = $account_id";
    $result = mysqli_query($sql, "");
    
    if ($account = mysqli_fetch_assoc($result)) {
        header('Content-Type: application/json');
        echo json_encode($account);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Account not found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Account ID required']);
}
?>
