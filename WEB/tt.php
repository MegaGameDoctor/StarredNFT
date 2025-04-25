<?php

// Токен вашего бота (получить у @BotFather)
$bot_token = '7538906203:AAGHKxPQlC4LFZwQSSrindk0kV4mkgdXRpg';

// ID пользователя, чьё имя нужно получить
$user_id = '878149597';

// URL для запроса информации о пользователе
$url = "https://api.telegram.org/bot$bot_token/getChat?chat_id=$user_id";

// Выполнение запроса
$response = file_get_contents($url);
$data = json_decode($response, true);

// Проверяем успешность запроса
if ($data['ok']) {
    // Получаем имя пользователя
    $first_name = $data['result']['first_name'];
    $last_name = isset($data['result']['last_name']) ? $data['result']['last_name'] : '';
    $username = isset($data['result']['username']) ? $data['result']['username'] : 'Не указано';

    // Выводим информацию
    echo "Имя пользователя: $first_name\n";

} else {
    echo "Не удалось получить данные о пользователе.";
}

?>
