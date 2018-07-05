<?php
require __DIR__ . '/vendor/autoload.php';

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName('Gmail API PHP Quickstart');

// Remove confidential.json
//$client->setScopes(Google_Service_Gmail::GMAIL_READONLY);
    $client->setScopes(Google_Service_Gmail::GMAIL_MODIFY);
    $client->setAuthConfig('client_secret.json');
    $client->setAccessType('offline');

    // Load previously authorized credentials from a file.
    $credentialsPath = expandHomeDirectory('credentials.json');
    if (file_exists($credentialsPath)) {
        $accessToken = json_decode(file_get_contents($credentialsPath), true);
    } else {
        // Request authorization from the user.
        $authUrl = $client->createAuthUrl();
        printf("Open the following link in your browser:\n%s\n", $authUrl);
        print 'Enter verification code: ';
        $authCode = trim(fgets(STDIN));

        // Exchange authorization code for an access token.
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

        // Store the credentials to disk.
        if (!file_exists(dirname($credentialsPath))) {
            mkdir(dirname($credentialsPath), 0700, true);
        }
        file_put_contents($credentialsPath, json_encode($accessToken));
        printf("Credentials saved to %s\n", $credentialsPath);
    }
    $client->setAccessToken($accessToken);

    // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
    }
    return $client;
}

/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path)
{
    $homeDirectory = getenv('HOME');
    if (empty($homeDirectory)) {
        $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
    }
    return str_replace('~', realpath($homeDirectory), $path);
}

function createGmailDraft($service, $user, $message) {
  $draft = new Google_Service_Gmail_Draft();
  $draft->setMessage($message);
   try {
      $draft = $service->users_drafts->create($user, $draft);

   } catch (Exception $e) {
       print 'An error occurred: ' . $e->getMessage();
   }
   return $draft;
}

function createGmailMessage($to,$cc,$subject,$content) {
    $msg = new Google_Service_Gmail_Message();
    $data = "";
    $data.= "To: " . $to . "\n";
    $data.= "Cc: " . $cc . "\n";
    $data.= "Subject: " . mb_encode_mimeheader($subject, 'utf-8') . "\n";
    $data.= "\n"; //
    $body = <<<EOF
$content
EOF;
    $data.= $body;

    echo "===========================\n";
    echo $data;

    $data = base64_encode($data); //base64エンコードする
    $data = strtr($data, '+/', '-_'); //サニタイジング
    $data = rtrim($data, '='); //最後の'='を除去
    $msg->setRaw($data); //データをセット
    return $msg;  //返却
}

// Create real mail body from template
function getEmailAddressInData($data) {
	 return $data[11] ;	 
}

// Create real mail body from template
function createContent($template,$data){

	  // Load each data from csv field
      $name = $data[2] . " " . $data[3];
      $address = $data[8];
      $phone1  = $data[9];
      $phone2  = $data[10];
      $email   = getEmailAddressInData($data);

      // Replace string in template
      $content = $template ;
      $content = str_replace( "name"   ,$name   ,$content );
      $content = str_replace( "address",$address, $content );
      $content = str_replace( "phone1" ,$phone1 , $content );
      $content = str_replace( "phone2" ,$phone2 , $content );
      $content = str_replace( "email"  ,$email  , $content );
      return $content;
}

// Any additional argument means "dry-run" mode 
// without real draft writing, will be used for testing
$dry_run=false ;
if ($argc > 1) {
   $dry_run=true;
}

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Gmail($client);

$template = file_get_contents('./message.txt',true);
$fp = fopen('member_list.csv', 'r');

while (($data = fgetcsv($fp)) !== FALSE) {

    $content = createContent($template,$data);
    $email = getEmailAddressInData($data);

    $message = createGmailMessage($email,
			"yuji.ogihara.85@gmail.com",
			"[東京東筑会][85期] 名簿登録情報の確認願",
			$content) ;
    if ($dry_run != true) {
        $draft = createGmailDraft($service,"me", $message);
        print 'Draft ID: ' . $draft->getId();
    }
}
fclose($fp);


