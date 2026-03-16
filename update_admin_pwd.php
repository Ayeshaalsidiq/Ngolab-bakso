<?php
require_once 'config/config.php';

try {
    // Generate new password hash
    $new_password = '12345678';
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update the database where username is admin
    $stmt = mysqli_prepare($koneksi, "UPDATE users SET password = ? WHERE username = 'admin'");
    mysqli_stmt_bind_param($stmt, "s", $hashed_password);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        echo "Password for admin updated successfully to 12345678.\n";
    } else {
        echo "Admin user not found, or password is already set to the same hash.\n";
    }

} catch(Exception $e) {
    echo "Error updating password: " . $e->getMessage() . "\n";
}
?>
