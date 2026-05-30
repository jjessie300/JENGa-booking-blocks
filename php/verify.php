<?php
// Nikola
// Logic for account verification
require_once __DIR__ . '/../db.php';

//Grab the token from the URL
$token = $_GET['token'];

// Fail, redirect to create account page
if (empty($token)) {
    echo "Invalid request. No token provided.<br>";
    echo "<a href='createaccount.php'>Create account</a>";
    exit();
}

// Search for a user that has this specific token
$statement = $conn->prepare("SELECT user_id FROM Users WHERE verification_token = ? LIMIT 1");
$statement->bind_param("s", $token);
$statement->execute();
$result = $statement->get_result();
$user = $result->fetch_assoc();

// Attempting to verify user
if ($user) {
    $id_to_update = $user['user_id'];
    // Update is_verified and clear token for security so it can't be used again
    $update_statement = $conn->prepare("UPDATE Users SET is_verified = 1, verification_token = NULL WHERE user_id = ?");
    $update_statement->bind_param("i", $id_to_update);
    
    // Confirm account has been verified
    // TODO: Use transaction for rollback
    if ($update_statement->execute()) {
        header("Location: accountverified.php");
        exit();
;
    } else {
        echo "Error updating account.";
        // Rollback change
        $update_statement = $conn->prepare("UPDATE Users SET is_verified = 0, verification_token = $token WHERE user_id = ?");
        $update_statement->bind_param("i", $id_to_update);
        $update_statement->execute();
    }

} 


// No user found with that token
else {
    echo "<h1>Verification Failed</h1>";
    echo "<p>This link is invalid or has already been used.</p>";
}
?>


