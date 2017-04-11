<?php

# https://github.com/rzaripov1990

# http://blog.rzaripov.kz/2017/02/firebase-android-ios.html
# http://blog.rzaripov.kz/2017/02/firebase-android-ios-2.html


# настройка подключения к базе данных
$config['db']['host'] = "расположение БД"; // localhost
$config['db']['name'] = "название БД";
$config['db']['user'] = "пользователь";
$config['db']['pass'] = "пароль к БД"; 

$config['push']['server_key'] = "тут серверный ключ из консоли firebase";
$config['app']['name'] = "название пакета приложения для IOS"; //com.embarcadero.PUSHTest

# подключаем наш файлик с функциями
include('common.php');

# подключаем файл с push функционалом
include('push.php');

if ($method == "saveToken") {
	# http://rzaripov.kz/pushTest/api.php?method=saveToken&deviceID=a0123456987z&deviceToken=abcdef1234567890&platform=ANDROID
	
	# если параметры не переданы, то отдаем ошибку
	if (empty($deviceID)) msgErr("Параметр `deviceID` не передан");
	if (empty($deviceToken)) msgErr("Параметр `deviceToken` не передан");
	if (empty($platform)) msgErr("Параметр `platform` не передан") ;
		else $platform = strtoupper($platform);
	
	# составляем запрос из параметров
	$sql =  "INSERT INTO PushTokens (`deviceToken`, `deviceID`, `platform`) VALUE ('$deviceToken', '$deviceID', '$platform') ON DUPLICATE KEY UPDATE `deviceToken` = '$deviceToken'";
	
	# выполняем INSERT/UPDATE в БД
	if (queryResult($sql))
		msgOK(); # все успешно
	else 
		msgErr("Не удалось сохранить токен в БД"); # ошибка при добавлении
	
} elseif ($method == "sendPush") {
	# http://rzaripov.kz/pushTest/api.php?method=sendPush&title=Заголовок&text=Текст
	
	# если параметры не переданы, то отдаем ошибку
	if (empty($title)) msgErr("Параметр `title` не передан");
	if (empty($text)) msgErr("Параметр `text` не передан");
	
	# выборка с БД для платформы Android
	$rows_A = selectQuery("SELECT DISTINCT deviceToken FROM PushTokens WHERE platform='ANDROID'");
	# собираем правильный массив для передачи в функцию pushSend
	$tokens_A = array();
	foreach ($rows_A as $item) {
		$tokens_A[] = $item['deviceToken'];
	}	
	
	# выборка с БД для платформы iOS
	$rows_I = selectQuery("SELECT DISTINCT deviceToken FROM PushTokens WHERE platform='IOS'");
	# собираем правильный массив для передачи в функцию pushSend
	$tokens_I = array();
	foreach ($rows_I as $item) {
		$tokens_I[] = $item['deviceToken'];
	}	
	
	# для IOS нужно сделать преобразование токенов
	$tokens = array();
	if (count($tokens_I) > 0) {
		 $tokens =  apns2fcmToken($config['app']['name'], $tokens_I, $config['push']['server_key']);
	}
	
	# склеиваем массивы в единый для передачи в функцию pushSend
	$fcm_tokens = array_merge($tokens_A, $tokens);
	#print_r($fcm_tokens); //- проверить можно так, что содержит массив
	
	# ВАЖНО! 
	# За один раз можно отправить только на 1000 токенов, 
	# поэтому если зарегистрированных девайсов у вас много, 
	# нужно будет разделить на несколько этапов
	
	# отправляем пуши в FCM
    if (pushSend($title, $text, $fcm_tokens, $config['push']['server_key'] ) === false)
		msgErr("Пуши не отправлены");
	else
		msgOK();
	
} else
	msgErr("Такой метод не найден");

?>