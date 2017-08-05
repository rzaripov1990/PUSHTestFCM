# PUSHTestFCM
[FireMonkey] FCM Push test project

### Подготовка
прочтите внимательно статьи 
* http://blog.rzaripov.kz/2017/02/firebase-android-ios.html
* http://blog.rzaripov.kz/2017/02/firebase-android-ios-2.html

### Серверная часть
###### Поместите папку `pushTest` к себе на сервер, в корень сайта
###### настройте доступ к БД и параметры из консоли Firebase (файл `api.php`)

```
$config['db']['host'] = "расположение БД"; // localhost
$config['db']['name'] = "название БД";
$config['db']['user'] = "пользователь";
$config['db']['pass'] = "пароль к БД"; 
$config['push']['server_key'] = "тут серверный ключ из консоли firebase";
$config['app']['name'] = "название пакета приложения для IOS"; //com.embarcadero.PUSHTest
```

### Delphi/C++ Builder
Измените процедуру регистрации `RegisterDevice` а именно укажите свой сервер в строке
```
aHTTP.Get('http://ТУТ ВАШ СЕРВЕР/pushTest/api.php?method=saveToken&deviceID=' + FDeviceID + '&deviceToken=' +
          FDeviceToken + '&platform='{$IFDEF ANDROID} + 'ANDROID' {$ELSEIF defined(IOS)} + 'IOS' {$ENDIF});
```
и также нужно указать идентификатор отправителя из консоли Firebase
```
const
  FAndroidServerKey = 'идентификатор отправителя';
```


Google Play - https://play.google.com/store/apps/details?id=kz.rzaripov.PushTest&hl=ru
