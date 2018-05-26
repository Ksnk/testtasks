<?php

include 'func.php';

// данные для ввода в форму
$data = [
    'formname' => [
        'name' => ['require' => true, 'title' => 'Имя'],
        'email' => ['require' => true, 'type' => 'email'],
        'message' => ['require' => true, 'type' => "textarea", 'title' => 'текст собщения'],
    ]
];

$template = 'showform';

if (val($_SERVER, 'REQUEST_METHOD') == 'POST') {
    sessionstart();
    if (!isset($_SESSION['errors']))
        $_SESSION['errors'] = [];
    if (!isset($_SESSION['values']))
        $_SESSION['values'] = [];

    // проверяем данные
    $fault = false;
    foreach ($data['formname'] as $k => &$v) {
        if (!empty($v['require'])) {
            if (!isset($_POST[$k]) || '' == trim($_POST[$k])) {
                $v['error'] = 'Обязательное поле не заполнено';
                $fault = true;
                //continue;
            }
        }
        if (isset($_POST[$k])) {
            // сохраняем введенное поле в сессии
            $_SESSION['values'][$k] = $_POST[$k];
            if (!empty($v['type']) && ('' != trim($_POST[$k]))) {
                if ($v['type'] == 'email') {
                    if (!filter_var($_POST[$k], FILTER_VALIDATE_EMAIL)) {
                        $v['error'] = 'E-mail адрес "' . htmlspecialchars($_POST[$k]) . '" указан неверно' . PHP_EOL;
                        $fault = true;
                        // continue;
                    }
                }
            }

        }
        // сохраняем ошибки
        if (!empty($v['error']))
            $_SESSION['errors'][$k] = $v['error'];
    }
    if (!$fault) { // все в порядке - пишем в базу
        unset($_SESSION['errors']);
        $dbconfig = include 'config.php';
        $db = new PDO("mysql:host=" . $dbconfig['dbhost'] . ";dbname=" . $dbconfig['db'] . ";charset=utf8", $dbconfig['dbuser'], $dbconfig['dbpassword']);
        $prep = $db->prepare('INSERT INTO smlt2_messages(user, email, message) VALUES(:user, :email, :message)');
        $prep->execute([
            ':user' => $_POST['name'],
            ':email' => $_POST['email'],
            ':message' => $_POST['message']
        ]);
        $_SESSION['template'] = 'messagesent';
        // чистим текст сообщения, но не адресные поля
        unset($_SESSION['values']['message']);
    }

    $host = 'http' . ($_SERVER['HTTPS'] ? 's' : '') . '://' . $_SERVER['HTTP_HOST'];
    $path = $_SERVER['SCRIPT_NAME'];
    header('location: ' . $host . $path);
    exit;
}
// старт сессии, если это кому нибудь нужно
$session_name = session_name();
if (array_key_exists($session_name, $_GET)
    || array_key_exists($session_name, $_COOKIE)
) {
    sessionstart();
}

// дополняем поля формы значениями из сессии
foreach ($data['formname'] as $k => &$v) {
    if ('' != ($x = val($_SESSION, 'errors|' . $k))) {
        $v['error'] = $x;
    }
    if ('' != ($x = val($_SESSION, 'values|' . $k))) {
        $v['value'] = $x;
    }
}
if (isset($_SESSION['errors']))
    unset($_SESSION['errors']);
if(isset($_SESSION['template'])) {
    $template = $_SESSION['template'];
    unset($_SESSION['template']);
}

// выводим шаблончик с формой, или не с формой
switch ($template) {
    case 'messagesent': // шаблон- сообщение отправлено
        echo <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Сообщение успешно отправлено</title>
</head>
<body>
<p>Ваше сообщение успешно отправлено. Спасибо!</p>
<p>Обновите страницу для отправки еще одного сообщения</p>
</body>
HTML;
        break;
    default: // шаблон- заполните форму
        echo '<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отправьте сообщение</title>
    <link rel="stylesheet" href="main.css">
</head>
<body>
<p>Заполните форму обратной связи (Все поля, отмеченные звездочкой, обязательны для заполнения)</p>
<form method="POST" name="formname" action="">
'
            . createField($data['formname'], 'name')
            . createField($data['formname'], 'email')
            . createField($data['formname'], 'message')
            . '<input type="submit" value="Отправить">
</form>
</body>
';
        break;
}
