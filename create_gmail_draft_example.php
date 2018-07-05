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

/* ======== Main ===== */

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Gmail($client);

/*========= Creating MimeMessage ========*/

$email = "To: "   . "receiver@hogehoge.com"    . "\n";
$email.= "Cc: "   . "cc_receiver@hogehoge.com" . "\n";
$email.= "Subject: " . mb_encode_mimeheader("SUBJECT/タイトル", 'utf-8') . "\n";
$email.= "\n";
$bodyText = <<<EOF
"Messsage body / メール本文"
EOF;
$email.= $bodyText;

/*========= Creating Gmail Message ========*/

$message = new Google_Service_Gmail_Message();
// base64url encode the string
//   see http://en.wikipedia.org/wiki/Base64#Implementations_and_history
$email = strtr(base64_encode($email), array('+' => '-', '/' => '_'));
$message->setRaw($email);

/*========= Creating Draft ========*/

$draft = new Google_Service_Gmail_Draft();
$draft->setMessage($message);
try {
  $draft = $service->users_drafts->create('me', $draft);
  print 'Draft ID: ' . $draft->getId();
} catch (Exception $e) {
    print 'An error occurred: ' . $e->getMessage();
}


