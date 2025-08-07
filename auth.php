<?php
session_start();

// Verifica se l'utente è autenticato
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

// Verifica se l'utente è admin
function requireAdmin() {
    requireAuth();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: login.php');
        exit();
    }
}

// Ottieni informazioni utente corrente
function getCurrentUser() {
    if (isset($_SESSION['user_id'])) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role']
        ];
    }
    return null;
}

// Controlla se l'utente è loggato
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
?>