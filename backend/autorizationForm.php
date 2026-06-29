<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход в аккаунт</title>
    <link rel="stylesheet" href="style.css">
    <script src="../frontend/js/auth.js" defer></script>
</head>
<body>

<div class="auth-container">
    <h1>Вход</h1>
    
    <form method="POST" action="./autorization.php">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        
        <div class="field-group">
            <label>Логин</label>
            <input type="text" name="login" required>
        </div>
        
        <div class="field-group">
            <label>Пароль</label>
            <input type="password" name="password" required>
        </div>
        
        <button type="submit">Войти</button>
    </form>
    
    <div class="links">
        <a href="./index.php">Нет аккаунта? Зарегистрироваться</a>
    </div>
</div>

</body>
</html>