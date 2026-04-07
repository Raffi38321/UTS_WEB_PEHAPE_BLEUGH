<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        $script = $_SERVER['PHP_SELF'];
        $base = strpos($script, '/admin/') !== false ? '../' : '';
        header("Location: {$base}login.php");
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        $script = $_SERVER['PHP_SELF'];
        $base = strpos($script, '/admin/') !== false ? '../' : '';
        header("Location: {$base}index.php");
        exit;
    }
}

function kirimNotifikasi($conn, $user_id, $booking_id, $pesan) {
    $stmt = $conn->prepare("INSERT INTO notifikasi (user_id, booking_id, pesan) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $booking_id, $pesan);
    $stmt->execute();
}

function countNotifikasi($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM notifikasi WHERE user_id = ? AND dibaca = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['total'];
}
