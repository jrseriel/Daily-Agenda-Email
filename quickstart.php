
<?php
require_once __DIR__ . '/vendor/autoload.php';


define('APPLICATION_NAME', 'Google Calendar API PHP Quickstart');
define('CREDENTIALS_PATH', '~/.credentials/calendar-php-quickstart.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/calendar-php-quickstart.json
define('SCOPES', implode(' ', array(
  Google_Service_Calendar::CALENDAR_READONLY)
));

if (php_sapi_name() != 'cli') {
  throw new Exception('This application must be run on the command line.');
}

/**
* Returns an authorized API client.
* @return Google_Client the authorized client object
*/
function getClient() {
  $client = new Google_Client();
  $client->setApplicationName(APPLICATION_NAME);
  $client->setScopes(SCOPES);
  $client->setAuthConfig(CLIENT_SECRET_PATH);
  $client->setAccessType('offline');

  // Load previously authorized credentials from a file.
  $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
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
    if(!file_exists(dirname($credentialsPath))) {
      mkdir(dirname($credentialsPath), 0700, true);
    }
    file_put_contents($credentialsPath, json_encode($accessToken));
    printf("Credentials saved to %s\n", $credentialsPath);
  }
  $client->setAccessToken($accessToken);

  //Added
  $client->setAccessType('offline');
  $client->setApprovalPrompt('force');

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
function expandHomeDirectory($path) {
  $homeDirectory = getenv('HOME');
  if (empty($homeDirectory)) {
    $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
  }
  return str_replace('~', realpath($homeDirectory), $path);
}

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Calendar($client);

//Make Next Day
$tomorrow = mktime(0, 0, 0, date('n'), date('j') + 1);
$tomorrow = date ('c',$tomorrow);

//Todays Date in Y M D
$today = date('Y-m-d');

// Print the next 10 events on the user's calendar.
$calendarId = 'primary';
$optParams = array(
  'maxResults' => 10,
  'orderBy' => 'startTime',
  'singleEvents' => TRUE,
  'timeMin' => date('c'),
  'timeMax'=> $tomorrow,
);
$results = $service->events->listEvents($calendarId, $optParams);

//Declare event array
$eventlist = array();

//Set timezone
date_default_timezone_set('America/New_York');

if (count($results->getItems()) == 0) {
  print "No upcoming events found.\n";
} else {
  print "Upcoming events:\n";
  foreach ($results->getItems() as $event) {
    $start = $event->start->dateTime;
    $start = date('H:i:s', strtotime($start.'UTC'));
    if (empty($start)) {
      $start = $event->start->date;
      $start = date('H:i:s', strtotime($start.'UTC'));
    }
    //Get events and add list html tags
    array_push($eventlist, "<li>(".$start.") ".$event->getSummary()."</li>");
    //echo $eventlist;
    //printf("%s (%s)\n", $event->getSummary(), $start);
  }
}

//Check is events is 0
if (empty($eventlist)) {
    array_push($eventlist, "<center><b>You do not have any events scheduled for today</b></center>");
}

//Test and format event array
$eventlist =  implode("",$eventlist);
print_r ($eventlist);

//Archive for horizontal rule pics
//https://www.smashingmagazine.com/2008/09/the-hr-contest-results-download-your-fresh-hr-line-now/

//Write Events to Webpage
$myfile = fopen("newfile.html", "w") or die("Unable to open file!");
$html = "
<html>
<body>
    <br>
    <center><img src='https://upload.wikimedia.org/wikipedia/commons/thumb/4/43/POL_COA_Ostoja_%C5%9Bredniowieczna.svg/745px-POL_COA_Ostoja_%C5%9Bredniowieczna.svg.png' height='90' width=auto></center>
    <br>
    <center><p style='display:inline;font-family:courier;font-size:125%;'><name>'s</p></center>
    <center><p style='display:inline;font-family:courier;font-size:125%;'>Schedule for <b>".$today."</b></p></center>
    <br>
    <hr style='width:75%;'>
    <br>
    <ul style='font-family:courier;font-size:125%;margin-left: 5%;margin-right: 5%;'>
    ".$eventlist."
    </ul>

</body>
</html>
";

print $html;

//PHP Mailer
/**
 * This example shows settings to use when sending via Google's Gmail servers.
 */
//SMTP needs accurate times, and the PHP time zone MUST be set
//This should be done in your php.ini, but this is how to do it if you don't have access to that
date_default_timezone_set('Etc/UTC');
require '...\vendor\phpmailer\phpmailer\PHPMailerAutoload.php';
//Create a new PHPMailer instance
$mail = new PHPMailer;
//Tell PHPMailer to use SMTP
$mail->isSMTP();
//Enable SMTP debugging
// 0 = off (for production use)
// 1 = client messages
// 2 = client and server messages
$mail->SMTPDebug = 2;
//Ask for HTML-friendly debug output
$mail->Debugoutput = 'html';
//Set the hostname of the mail server
$mail->Host = 'smtp.gmail.com';
// use
// $mail->Host = gethostbyname('smtp.gmail.com');
// if your network does not support SMTP over IPv6
//Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
$mail->Port = 587;
//Set the encryption system to use - ssl (deprecated) or tls
$mail->SMTPSecure = 'tls';
//Whether to use SMTP authentication
$mail->SMTPAuth = true;
//Username to use for SMTP authentication - use full email address for gmail
$mail->Username = "<username>";
//Password to use for SMTP authentication
$mail->Password = "<password>";
//Set who the message is to be sent from
$mail->setFrom('<email>', '<name>');
//Set an alternative reply-to address
$mail->addReplyTo('<email>', '<name>');
//Set who the message is to be sent to
$mail->addAddress('<email>', '<name>');
//Set the subject line
$mail->Subject = "<name>'s Daily Schedule";
//Read an HTML message body from an external file, convert referenced images to embedded,
//convert HTML into a basic plain-text alternative body
//$mail->msgHTML(file_get_contents('contents.html'), dirname(__FILE__));
$mail->msgHTML($html);
//Replace the plain text body with one created manually
//$mail->AltBody = 'This is a plain-text message body';
//Attach an image file
//$mail->addAttachment('images/phpmailer_mini.png');
//send the message, check for errors
if (!$mail->send()) {
    echo "Mailer Error: " . $mail->ErrorInfo;
} else {
    echo "Message sent!";
}
