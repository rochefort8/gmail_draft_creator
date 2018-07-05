<?php
require __DIR__ . '/vendor/autoload.php';


/* =====================================================================================
 * Codes from Gmail API php quickstart
 *   https://developers.google.com/gmail/api/quickstart/php
 *   https://github.com/gsuitedevs/php-samples/blob/master/gmail/quickstart/quickstart.php
 *
 *=================================================================================== */

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

/* =====================================================================================
 * Code from User.drafts: create sample by php
 *   https://developers.google.com/gmail/api/v1/reference/users/drafts/create
 *
 * ===================================================================================*/

/**
 * Create Draft email.
 *
 * @param  Google_Service_Gmail $service Authorized Gmail API instance.
 * @param  string $userId User's email address. The special value 'me'
 * can be used to indicate the authenticated user.
 * @param  Google_Service_Gmail_Message $message Message of the created Draft.
 * @return Google_Service_Gmail_Draft Created Draft.
 */
function createDraft($service, $user, $message) {
  $draft = new Google_Service_Gmail_Draft();
  $draft->setMessage($message);
  try {
    $draft = $service->users_drafts->create($user, $draft);
    print 'Draft ID: ' . $draft->getId();
  } catch (Exception $e) {
    print 'An error occurred: ' . $e->getMessage();
  }
  return $draft;
}

/**
 * Create a Message from an email formatted string.
 *
 * @param  string $email Email formatted string.
 * @return Google_Service_Gmail_Message Message containing email.
 */
function createMessage($email) {
  $message = new Google_Service_Gmail_Message();
  // base64url encode the string
  //   see http://en.wikipedia.org/wiki/Base64#Implementations_and_history
  $email = strtr(base64_encode($email), array('+' => '-', '/' => '_'));
  $message->setRaw($email);
  return $message;
}


/* =====================================================================================
 * Additional code for creating draft from member data and base email message
 * ===================================================================================*/
 
/**
 * Create a MimeMessage using the parameters provided.
 *
 * @param to email address of the receiver
 * @param from email address of the sender, the mailbox account
 * @param cc email address of the reciever of carbon copy
 * @param subject subject of the email
 * @param body body text of the email
 * @return the MimeMessage to be used to send email
 */

function createEmail($to,$cc,$subject,$body) {

    $email = "";
    $email.= "To: "   . $to . "\n";
    $email.= "Cc: "   . $cc . "\n";
    $email.= "Subject: " . mb_encode_mimeheader($subject, 'utf-8') . "\n";
    $email.= "\n"; //
    $bodyText = <<<EOF
$body
EOF;
    $email.= $bodyText;
    echo $email;
    
    return $email;
}

/**
 * Create a email address fron provided csv formatted data.
 *
 * @param $data  base sentense
 * @param data csv formatted data in which personal data is contained
 * @return the email address of the reciever 
 */

function getEmailAddress($data)
{
    $email  = $data[9];
    return $email;
}

/**
 * Create a email body text using the parameters provided.
 *
 * @param template  base sentense 
 * @param data csv formatted data in which personal data is contained
 * @return the body text 
 */

function createBody($template,$data){

// Load each data from csv field
      $name = $data[1] . " " . $data[2];
      $address = $data[6] . " " . $data[7];
      $phone1  = $data[8];
      $phone2  = "-";      
      $email   = getEmailAddress($data);

      // Replace string in template
      $body = $template ;
      $body = str_replace( "name"   ,$name   , $body );
      $body = str_replace( "address",$address, $body );
      $body = str_replace( "phone1" ,$phone1 , $body );
      $body = str_replace( "phone2" ,$phone2 , $body );
      $body = str_replace( "email"  ,$email  , $body );
      return $body;
}

/* ===========================
 * Main
 * ==========================*/

$cc="yuji.ogihara.85@gmail.com";
$subject="連絡先確認願";

// Any additional argument means "dry-run" mode 
// without real draft writing, will be used for testing

$dry_run=false ;
if ($argc > 1) {
   $dry_run=true;
}

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Gmail($client);

// Get base message
$template = file_get_contents('message.txt',true);

// Get csv formatted text in which personal data is contained
$fp = fopen('member_list.csv', 'r');

while (($data = fgetcsv($fp)) !== FALSE) {

    $to = getEmailAddress($data);
    $content = createBody($template,$data);
    $email   = createEmail($to,
			   $cc,
			   $subject,
			   $content) ;
    $message = createMessage($email);

    if ($dry_run != true) {
        $draft = createDraft($service,"me", $message);
        print 'Draft ID: ' . $draft->getId();
    }
}
fclose($fp);


