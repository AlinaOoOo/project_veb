<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// CSRF токен
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (empty($_SESSION['uLogin'])) {
        header('Location: ./autorization.php');
        exit();
    }
    include('redaktForm.php');
    exit();
}

// Проверка CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    if ($isAjax) {
        echo 'Ошибка безопасности';
        exit();
    }
    die('Ошибка безопасности');
}

$errors_array = [];

if (!empty($_POST['fio']) && strlen($_POST['fio']) > 150) {
    $errors_array['fio'] = "Слишком много символов в поле ФИО.";
}
if (!empty($_POST['fio']) && !preg_match('/^[a-zA-Zа-яА-ЯёЁ\s-]+$/u', $_POST['fio'])) {
    $errors_array['fio'] = "ФИО должно содержать только буквы, пробелы и дефисы";
}
if (!empty($_POST['phone']) && !preg_match('/^[\+\(\)\d\s-]+$/', $_POST['phone'])) {
    $errors_array['phone'] = "Номер телефона может содержать цифры, +, пробелы, скобки и дефисы.";
}
if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors_array['email'] = "Введите корректный email адрес.";
}

if (!empty($errors_array)) {
    if ($isAjax) {
        foreach ($errors_array as $value) {
            echo htmlspecialchars($value) . "<br>";
        }
        exit();
    }
    foreach ($errors_array as $value) {
        print(htmlspecialchars($value) . "<br>");
    }
    exit();
}

$user = 'u82467';
$pass = '5630801';

try {
    $db = new PDO('mysql:host=localhost;dbname=u82467', $user, $pass,
        [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    $db->beginTransaction();
    
    $stmt = $db->prepare("SELECT id FROM users WHERE userLogin = :login");
    $stmt->execute([':login' => $_SESSION['uLogin']]);
    $userId = $stmt->fetchColumn();
    
    if (!empty($_POST['fio'])) {
        $stmt = $db->prepare("UPDATE users SET fio = :fio WHERE userLogin = :login");
        $stmt->execute([
            ':fio' => $_POST['fio'],
            ':login' => $_SESSION['uLogin']
        ]);
    }
    
    if (!empty($_POST['phone'])) {
        $stmt = $db->prepare("UPDATE users SET phone = :phone WHERE userLogin = :login");
        $stmt->execute([
            ':phone' => $_POST['phone'],
            ':login' => $_SESSION['uLogin']
        ]);
    }
    
    if (!empty($_POST['email'])) {
        $stmt = $db->prepare("UPDATE users SET email = :email WHERE userLogin = :login");
        $stmt->execute([
            ':email' => $_POST['email'],
            ':login' => $_SESSION['uLogin']
        ]);
    }
    
    if (!empty($_POST['brithDate'])) {
        $stmt = $db->prepare("UPDATE users SET brithDate = :brithDate WHERE userLogin = :login");
        $stmt->execute([
            ':brithDate' => $_POST['brithDate'],
            ':login' => $_SESSION['uLogin']
        ]);
    }
    
    if (!empty($_POST['gender'])) {
        $stmt = $db->prepare("UPDATE users SET gender = :gender WHERE userLogin = :login");
        $stmt->execute([
            ':gender' => $_POST['gender'],
            ':login' => $_SESSION['uLogin']
        ]);
    }
    
    if (!empty($_POST['lang_id'])) {
        $stmt = $db->prepare("DELETE FROM user_languages WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        
        $stmt = $db->prepare("INSERT INTO user_languages (user_id, lang_id) VALUES (:user_id, :lang_id)");
        foreach ($_POST['lang_id'] as $lang_id) {
            $stmt->execute([
                ':user_id' => $userId,
                ':lang_id' => $lang_id
            ]);
        }
    }
    
    if (!empty($_POST['bio'])) {
        $stmt = $db->prepare("UPDATE users SET bio = :bio WHERE userLogin = :login");
        $stmt->execute([
            ':bio' => $_POST['bio'],
            ':login' => $_SESSION['uLogin']
        ]);
    }
    
    $db->commit();
    
    if ($isAjax) {
        echo 'success';
        exit();
    }
    
} catch (PDOException $e) {
    $db->rollBack();
    error_log($e->getMessage());
    
    if ($isAjax) {
        echo 'Произошла ошибка при сохранении. Попробуйте позже.';
        exit();
    }
    
    print('Произошла ошибка при обновлении данных. Пожалуйста, попробуйте позже.');
    exit();
}

header('Location: ?save=1');
exit();
?>