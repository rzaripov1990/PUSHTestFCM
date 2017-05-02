<?php

# https://github.com/rzaripov1990

# http://blog.rzaripov.kz/2017/02/firebase-android-ios.html
# http://blog.rzaripov.kz/2017/02/firebase-android-ios-2.html

function apns2fcmToken($appName, $tokens, $server_key) {
    $url = 'https://iid.googleapis.com/iid/v1:batchImport';
    $headers = array('Authorization: key=' . $server_key,
      'Content-Type: application/json');
 
    if (is_array($tokens))
      $token_arr = $tokens;
    else
      $token_arr = array($tokens);    
  
    $fields = array('application' => $appName, 'sandbox' => false, 
      'apns_tokens' => $token_arr);
  
    $ch = curl_init();
    curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS => json_encode($fields)
    ));
    $result = curl_exec($ch);
    curl_close($ch);
    if ($result === false) return false;
	
    $json = json_decode($result, true);
    $rows = [];
    for ($n = 0; $n < count($json['results']); $n++){
      if ($json['results'][$n]['status'] == "OK")
        $rows[$n] = $json['results'][$n]['registration_token'];
    }
    return $rows;
}
 
function pushSend($title, $text, $tokens, $server_key) {
    $url = 'https://fcm.googleapis.com/fcm/send';
    $headers = array('Authorization: key=' . $server_key, 
     'Content-Type: application/json');
  
    if (is_array($tokens))
      $fields['registration_ids'] = $tokens;
    else
      $fields['registration_ids'] = array($tokens);
  
    $fields['priority'] = 'high';
    $fields['notification'] = array('body' => $text, 'title' => $title, 'sound' => 'default');
    $fields['data'] = array('message' => $text, 'title' => $title);
  
    $ch = curl_init();
    curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS => json_encode($fields)
    ));
    $result = curl_exec($ch);
    curl_close($ch);
	
	if ($result === false) return false;
	$json = json_decode($result, true);
	
	if ($json['success'] == 0)
		return false;
	else
		return true;
}

function pushSendOver1000($title, $text, $tokens, $server_key) {
	$Count_Success = 0;
	$DeviceCountMax = 1000;
	$DeviceCountIndex = 0; 
	$DevicesTokenPacketArray = array();	
	while ($DeviceCountIndex<=count($tokens)) {
		$DevicesTokenPacketArray = array_slice($tokens, $DeviceCountIndex, $DeviceCountMax);
		pushSend($title, $text, $DevicesTokenPacketArray, $server_key);
		$DeviceCountIndex = $DeviceCountIndex + $DeviceCountMax;			
	}	
}

?>
