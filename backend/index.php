<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

// Определяем AJAX запрос
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Генерация CSRF токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

function Generator($size){
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for($i=0;$i<$size;$i++){
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomString;
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!empty($_GET['save'])) {
        setcookie('errors_array', '', time() - 3600, '/');
    }
    include('form.php');
    exit();
}

// Получаем данные из POST
$fio = $_POST['fio'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$brithDate = $_POST['brithDate'] ?? '';
$gender = $_POST['gender'] ?? '';
$bio = $_POST['bio'] ?? '';
$lang_id = $_POST['lang_id'] ?? [];
$contract = isset($_POST['contract']) ? 1 : 0;

// Проверка CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    if ($isAjax) {
        echo 'Ошибка безопасности';
        exit();
    }
    die('Ошибка безопасности');
}

$errors_array = [];

if (empty($fio)) {
    $errors_array['fio'] = "Заполните имя.";
} elseif (strlen($fio) > 150) {
    $errors_array['fio'] = "Слишком много символов в поле ФИО.";
} elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s-]+$/u', $fio)) {
    $errors_array['fio'] = "ФИО должно содержать только буквы, пробелы и дефисы";
}

if (empty($phone)) {
    $errors_array['phone'] = "Заполните номер телефона.";
} elseif (!preg_match('/^[\+\(\)\d\s-]+$/', $phone)) {
    $errors_array['phone'] = "Номер телефона может содержать цифры, +, пробелы, скобки и дефисы.";
}

if (empty($email)) {
    $errors_array['email'] = "Заполните почту.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors_array['email'] = "Введите корректный email адрес.";
}

if (empty($brithDate)) {
    $errors_array['brithDate'] = "Заполните Дату рождения.";
}

if (empty($gender)) {
    $errors_array['gender'] = "Выберите пол.";
}

if (empty($lang_id)) {
    $errors_array['lang_id'] = "Выберите язык программирования.";
}

if (empty($contract)) {
    $errors_array['contract'] = "Необходимо согласие с условиями.";
}

if (!empty($errors_array)) {
    setcookie('saved_fio', $fio, 0, '/');
    setcookie('saved_phone', $phone, 0, '/');
    setcookie('saved_email', $email, 0, '/');
    setcookie('saved_brithDate', $brithDate, 0, '/');
    setcookie('saved_gender', $gender, 0, '/');
    setcookie('saved_bio', $bio, 0, '/');
    
    if (!empty($lang_id)) {
        setcookie('saved_lang_id', json_encode($lang_id), 0, '/');
    } else {
        setcookie('saved_lang_id', '', time() - 3600, '/');
    }
    
    setcookie('errors_array', json_encode($errors_array), 0, '/');
    
    if ($isAjax) {
        $errorHtml = '<div class="error-messages">';
        foreach ($errors_array as $value) {
            $errorHtml .= '<p style="color: red; margin: 5px 0;">❌ ' . htmlspecialchars($value) . '</p>';
        }
        $errorHtml .= '</div>';
        echo $errorHtml;
        exit();
    }

    header('Location: ./');
    exit();
}

$one_year = time() + 31536000;
setcookie('saved_fio', $fio, $one_year, '/');
setcookie('saved_phone', $phone, $one_year, '/');
setcookie('saved_email', $email, $one_year, '/');
setcookie('saved_brithDate', $brithDate, $one_year, '/');
setcookie('saved_gender', $gender, $one_year, '/');
setcookie('saved_bio', $bio, $one_year, '/');

if (!empty($lang_id)) {
    setcookie('saved_lang_id', json_encode($lang_id), $one_year, '/');
}

setcookie('errors_array', '', time() - 3600, '/');

$userLogin = Generator(7);
$userPass = Generator(8);
$_SESSION['uLogin'] = $userLogin;
$_SESSION['uPass'] = $userPass;

$userPassHashed = password_hash($userPass, PASSWORD_DEFAULT);

$user = 'u82467';
$pass = '5630801';

try {
    $db = new PDO('mysql:host=localhost;dbname=u82467', $user, $pass,
        [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    $db->beginTransaction();
    
    $stmt = $db->prepare("INSERT INTO users (fio, phone, email, brithDate, gender, bio, contract, userLogin, userPass) 
                          VALUES (:fio, :phone, :email, :brithDate, :gender, :bio, :contract, :userLogin, :userPass)");
    $stmt->execute([
        ':fio' => $fio,
        ':phone' => $phone,
        ':email' => $email,
        ':brithDate' => $brithDate,
        ':gender' => $gender,  
        ':bio' => $bio,
        ':contract' => $contract,
        ':userLogin' => $userLogin,
        ':userPass' => $userPassHashed
    ]);
    
    $user_id = $db->lastInsertId();
    
    $stmt = $db->prepare("INSERT INTO user_languages (user_id, lang_id) VALUES (:user_id, :lang_id)");
    foreach ($lang_id as $lang) {
        $stmt->execute([
            ':user_id' => $user_id,
            ':lang_id' => $lang
        ]);
    }
    
    $db->commit();
    
    if ($isAjax) {
        echo '<div class="success-message" style="background: #d4edda; padding: 20px; margin: 10px 0; border-radius: 5px; border: 1px solid #c3e6cb;">';
        echo '<h2 style="color: #155724;">✅ Спасибо, результаты сохранены.</h2>';
        echo '<p><strong>Ваш логин:</strong> ' . htmlspecialchars($userLogin) . '</p>';
        echo '<p><strong>Ваш пароль:</strong> ' . htmlspecialchars($userPass) . '</p>';
        echo '<p style="margin-top: 15px;"><a href="./autorizationForm.php" style="display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">Перейти к входу</a></p>';
        echo '</div>';
        exit();
    }
    
} catch(PDOException $e) {
    $db->rollBack();
    error_log($e->getMessage());
    
    if ($isAjax) {
        echo '<div class="error-messages" style="background: #ffebee; padding: 15px; border-radius: 5px; border: 1px solid red;">';
        echo 'Произошла ошибка при сохранении. Попробуйте позже.';
        echo '</div>';
        exit();
    }
    
    print('Произошла ошибка при регистрации. Пожалуйста, попробуйте позже.');
    exit();
}

header('Location: ?save=1');
exit();
?>