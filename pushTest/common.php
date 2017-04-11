<?php

# https://github.com/rzaripov1990

# http://blog.rzaripov.kz/2017/02/firebase-android-ios.html
# http://blog.rzaripov.kz/2017/02/firebase-android-ios-2.html


# http://php.su
# https://jsonformatter.curiousconcept.com/
	
/********************************
	!! ПРОВЕРЬ КОДИРОВКУ ФАЙЛА !!
				UTF-8 без BOM

	!! 	  ВКЛЮЧИ ОШИБКИ В PHP 	   !!
********************************/

error_reporting (E_ALL);
ini_set('error_reporting', E_ALL);
if (!ini_get('display_errors')) 
	ini_set('display_errors', '1');

# установка ответа и кодировка страницы
header("Content-Type: application/json; charset=UTF8"); 

# пробигаем по всем параметрам и "защищаемся" от SQL инъекции
# теперь фильтровать нужно только цифры, строки уже прошли проверку
if (isset($_REQUEST)) {
	foreach ($_REQUEST as &$val) {
		$val = filter_str($val);
	}
}
# магическая функция параметры превращает в переменные
extract ($_REQUEST);

// ** БАЗА ДАННЫХ **************************************************************************
$db = mysqli_connect($config['db']['host'], $config['db']['user'], $config['db']['pass'], $config['db']['name'], "3306");
mysqli_query($db, "SET NAMES 'utf8';");
if (!$db) 
	exit('{"status":"ERROR","text":"Коннект к базе не выполнен"}');

// ** ОТВЕТЫ КЛИЕНТУ ***********************************************************************
function msgOK() {
# положительный ответ
	echo '{"status":"OK"}';
	exit;
}

function msgJSON($arr, $break = true) {
# положительный ответ c JSON
	echo '{"status":"OK","struct":'.json_result($arr).'}';
	if ($break == true) 
		exit;
}

function msgErr($msg, $break = true) {
# отрицательный ответ, с описанием ошибки
	echo '{"status":"ERROR","text":"'.$msg.'"}';
	if ($break == true) 
		exit;
}

// ** ФИЛЬТРЫ ПАРАМЕТРОВ *******************************************************************
function filter_str($value) {
# фильтруем строки, защита от SQL инъекции
	global $db; # так указываются глобальные переменные
	if (!$db)
		return addslashes($value);
	else
		return mysqli_real_escape_string($db, $value);
}

function filter_int($value) {
# фильтруем числа	
	return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
}

function filter_flt($value) {
# фильтруем дробные
	return filter_var ($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
}

// ** РАЗНОЕ *******************************************************************************
function json_result($arr, $pretty = false) {
# формируем JSON из массива данных 
	if ($pretty)
		return json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
	else
		return json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function parse($tag1, $tag2, $str, $search_pos, &$out_pos) {
# парсинг между двумя тегами
# search_pos - позиция откуда нужно начать поиск
# out_pos - позиция последнего найденного блока (амперанса означает что это не входящий, а выходящий параметр)
	$p = $search_pos;
	$p = strpos($str, $tag1, $p);
	$p2 = strpos($str, $tag2, $p);
	if ($p2 > $p) {
		$out_pos = $p2;
		return  substr($str, $p+strlen($tag1), $p2-$p-strlen($tag1));
	} else {
		$out_pos = 0;
		return "";
	}
}

function request($url) {
# получаем страницу по url
	$myCurl = curl_init();	
	curl_setopt_array($myCurl, 
		array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true, 
			CURLOPT_HEADER => false,
		)
	);
	
	$curl_resp = curl_exec($myCurl);
	$error = curl_errno($myCurl);
	
	if (!$error){
		curl_close($myCurl);
		return $curl_resp;
	} else {
		curl_close($myCurl);
		msgErr(curl_error($myCurl)); 
	}
}

function passGenerator($min = 6, $max = 9) {
# генератор пароля
	$pass = '';
	$chars = "1234567890qwertyuioplkjhgfdsazxcvbnmMNBVCXZASDFGHJKLPOIUYTREWQ";
	$count_char_pass = rand($min, $max);
	for ($i = 0; $i < $count_char_pass; $i++) {
		$numberChar = rand(0, iconv_strlen($chars, 'UTF-8')-1);
		$pass .= $chars[$numberChar];
	}
	return $pass;
}

function uniqueStr() {
# генерируется уникальная строка, в md5
# используется для сохранения имён картинок / url
	return md5(rand(1000,9999).time()); 
}
	
// ** РАБОТАЕМ С MYSQL ********************************************************************
function getLastID() {
# возвращает последний добавленный ID в базу
	global $db; # так указываются глобальные переменные
	if (!$db)
		return msgErr("Коннект к базе не выполнен");
	else
		return mysqli_insert_id($db);
}

function selectQuery($sql) {
# функция для SELECT запросов, возвращает массив данных
	global $db; # так указываются глобальные переменные
	if (!$db)
		return msgErr("Коннект к базе не выполнен");
	else {	
		$result = mysqli_query($db, $sql) or msgErr(mysqli_error($db));
		$arr = array();
		if (mysqli_num_rows($result) > 0) {
			while($r = mysqli_fetch_assoc($result)) {
				$arr[] = $r;
			}
		}
		return $arr;
	}
}

function queryComplete($sql) {
# функция для UPDATE/INSERT запросов
# используется когда нужно просто получить положительный ответ выполнения запроса
# отправляется сразу клиенту
	global $db; # так указываются глобальные переменные
	if (!$db)
		return msgErr("Коннект к базе не выполнен");
	else {	
		$result = mysqli_query($db, $sql) or msgErr(mysqli_error($db));
		if (mysqli_affected_rows($db) > 0)
			msgOK();
	}
}

function queryResult($sql) {
# функция для UPDATE/INSERT запросов
# используется когда после запроса идут еще действия с проверкой IF ()	
	global $db; # так указываются глобальные переменные
	if (!$db)
		return msgErr("Коннект к базе не выполнен");
	else {	
		$result = mysqli_query($db, $sql) or msgErr(mysqli_error($db), true);
		if (mysqli_affected_rows($db) > 0) 
			return true;
		else 
			return false;
	}
}