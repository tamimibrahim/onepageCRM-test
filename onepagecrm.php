<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>OnePage API</title>


	
</head>

<body>
<?php
ini_set('display_errors', true);
error_reporting(E_ALL);
// Sample code showing OnePage CRM API usage. To be used with contact form.

$api_login = 'login@email.com';
$api_password = 'password';

// Make OnePage CRM API call
function make_api_call($url, $http_method, $post_data = array(), $uid = null, $key = null)
{
   $full_url = 'https://app.onepagecrm.com/api/'.$url;
   $ch = curl_init($full_url);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
   curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $http_method);

   $timestamp = time();
   $auth_data = array($uid, $timestamp, $http_method, sha1($full_url));

   // For POST and PUT methods we have to calculate request body hash
   if($http_method == 'POST' || $http_method == 'PUT'){
      $post_query = http_build_query($post_data);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_query);
      $auth_data[] = sha1($post_query);
   }

   // Auth headers
   if($uid != null){ // We are logged in
      $hash = hash_hmac('sha256', implode('.', $auth_data), $key);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	 "X-OnePageCRM-UID: $uid",
	 "X-OnePageCRM-TS: $timestamp",
	 "X-OnePageCRM-Auth: $hash"
      ));
   }

   $result = json_decode(curl_exec($ch));
   curl_close($ch);

   if($result->status > 99){
      echo "API call error: {$result->message}\n";
      return null;
   }

   return $result;
}

if(php_sapi_name() != 'cli'){
   header('Content-Type: text/plain; charset=utf-8');
}

// Login
echo "Login action...\n";
$data = make_api_call('auth/login.json', 'POST', array('login' => $api_login, 'password' => $api_password));
if($data == null){
   exit;
}

// Get UID and API key from result
$uid = $data->data->uid;
$key = base64_decode($data->data->key);
echo "Logged in, our UID is $uid\n";

// Get contacts list
echo "Getting contacts list...\n";
$contacts = make_api_call('contacts.json', 'GET', array(), $uid, $key);
if($data == null){
   exit;
}

echo "We have {$contacts->data->count} contacts.\n";

// Create sample contact and delete it just after
echo "Creating new contact...\n";
$contact_data = array(
   'firstname' => $_POST['firstName'],
   'lastname' => $_POST['lastName'],
   'company' => $_POST['company'],
   'address' => $_POST['street'],
   'city' => $_POST['city'],
   'state' => $_POST['state'],
   'zip_code' => $_POST['zip'],
   'phones' => 'work|'.$_POST['phone'],
   'emails' => 'work|'.$_POST['email'],
   'description' => $_POST['comment'],
   'tags' => 'contact_form_lead'
);

$new_contact = make_api_call('contacts.json', 'POST', $contact_data, $uid, $key);
if($new_contact == null){
   exit;
}
$cid = $new_contact->data->id;
echo "Contact created. ID=$cid\n";

//echo "Deleting this contact...";
//make_api_call("contacts/$cid.json", 'DELETE', array(), $uid, $key);

echo "OK.";
?>
</body>
</html>
