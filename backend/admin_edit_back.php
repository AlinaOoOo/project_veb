<?php
session_start();

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if (empty($_SESSION['uLogin'])) {
    header('Location: autorization.php');
    exit();
}

$db = new PDO('mysql:host=localhost;dbname=u82467', 'u82467', '5630801');

$stmt = $db->prepare("SELECT role FROM users WHERE userLogin = ?");
$stmt->execute([$_SESSION['uLogin']]);
$row = $stmt->fetch();

if (!$row || $row['role'] != 'admin') {
    header('Location: redakt.php');
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

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

if ($user_id == 0) {
    if ($isAjax) {
        echo 'Не указан ID пользователя';
        exit();
    }
    header('Location: admin_edit_form.php');
    exit();
}

// Запрещаем админу редактировать самого себя
$stmt = $db->prepare("SELECT id FROM users WHERE userLogin = ?");
$stmt->execute([$_SESSION['uLogin']]);
$adminId = $stmt->fetchColumn();

if ($user_id == $adminId) {
    if ($isAjax) {
        echo 'Нельзя редактировать самого себя';
        exit();
    }
    header('Location: admin_edit_form.php?error=' . urlencode('Нельзя редактировать самого себя'));
    exit();
}

try {
    $db->beginTransaction();
    
    $sql = "UPDATE users SET 
                userLogin = :userLogin,
                fio = :fio,
                phone = :phone,
                email = :email,
                brithDate = :brithDate,
                gender = :gender,
                role = :role,
                bio = :bio,
                contract = :contract";
    
    $params = [
        ':userLogin' => $_POST['userLogin'],
        ':fio' => $_POST['fio'],
        ':phone' => $_POST['phone'],
        ':email' => $_POST['email'],
        ':brithDate' => $_POST['brithDate'],
        ':gender' => $_POST['gender'],
        ':role' => $_POST['role'],
        ':bio' => $_POST['bio'] ?? '',
        ':contract' => isset($_POST['contract']) ? 1 : 0,
        ':id' => $user_id
    ];
    
    if (!empty($_POST['userPass'])) {
        $sql .= ", userPass = :userPass";
        $params[':userPass'] = password_hash($_POST['userPass'], PASSWORD_DEFAULT);
    }
    
    $sql .= " WHERE id = :id";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    // Обновляем языки
    $stmt = $db->prepare("DELETE FROM user_languages WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    $stmt = $db->prepare("INSERT INTO user_languages (user_id, lang_id) VALUES (?, ?)");
    foreach ($_POST['lang_id'] as $lang_id) {
        $stmt->execute([$user_id, $lang_id]);
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
        echo 'Ошибка при сохранении данных';
        exit();
    }
    
    header("Location: admin_edit_form.php?error=" . urlencode('Ошибка при сохранении данных'));
    exit();
}

header('Location: redakt.php?save=1');
exit();
?>