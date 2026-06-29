<?php
session_start();

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// CSRF токен
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!empty($_SESSION['uLogin'])) {
        header('Location: ./redakt.php');
        exit();
    }
    header('Location: ./autorizationForm.php');
    exit();
}

$login = $_POST['login'] ?? '';
$password = $_POST['password'] ?? '';

// CSRF проверка
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    if ($isAjax) {
        echo 'Ошибка безопасности';
        exit();
    }
    die('Ошибка безопасности');
}

$user = 'u82467';
$pass = '5630801';

try {
    $db = new PDO('mysql:host=localhost;dbname=u82467', $user, $pass,
        [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $stmt = $db->prepare("SELECT id, userPass, role FROM users WHERE userLogin = :login");
    $stmt->execute([':login' => $login]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userData && password_verify($password, $userData['userPass'])) {
        $_SESSION['uLogin'] = $login;
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['role'] = $userData['role'];
        
        if ($isAjax) {
            echo 'success';
            exit();
        }
        
        header('Location: ./redakt.php');
        exit();
    }
    
    if ($isAjax) {
        echo 'Неверный логин или пароль';
        exit();
    }
    
    header('Location: ./autorizationForm.php?error=1');
    exit();
    
} catch(PDOException $e) {
    error_log($e->getMessage());
    
    if ($isAjax) {
        echo 'Ошибка базы данных';
        exit();
    }
    
    print('Произошла ошибка. Пожалуйста, попробуйте позже.');
    exit();
}
?>