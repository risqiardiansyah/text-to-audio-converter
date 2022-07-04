<?php
cors();
function cors()
{

    // Allow from any origin
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
        // you want to allow, and if so:
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    }

    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            // may also be using PUT, PATCH, HEAD etc
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

        exit(0);
    }
}

function processGETRequest($appkey, $token, $text, $audioSaveFile, $format, $sampleRate)
{
    $url = "https://nls-gateway-ap-southeast-1.aliyuncs.com/stream/v1/tts";
    $url = $url . "?appkey=" . $appkey;
    $url = $url . "&token=" . $token;
    $url = $url . "&text=" . $text;
    $url = $url . "&format=" . $format;
    $url = $url . "&sample_rate=" . strval($sampleRate);
    // voice Optional. The speaker. Default value: xiaoyun.
    // $url = $url . "&voice=" . "xiaoyun";
    // volume Optional. The volume. Value range: 0 to 100. Default value: 50.
    // $url = $url . "&volume=" . strval(50);
    // speech_rate Optional. The speed. Value range: -500 to 500. Default value: 0.
    // $url = $url . "&speech_rate=" . strval(0);
    // pitch_rate Optional. The intonation. Value range: -500 to 500. Default value: 0.
    // $url = $url . "&pitch_rate=" . strval(0);
    // print $url . "\n";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    /**
     * Set the HTTPS GET URL.
     */
    curl_setopt($curl, CURLOPT_URL, $url);
    /**
     * Set the HTTPS header in the response.
     */
    curl_setopt($curl, CURLOPT_HEADER, TRUE);
    /**
     * Send the HTTPS GET request.
     */
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    $response = curl_exec($curl);
    if ($response == FALSE) {
        print "curl_exec failed!\n";
        curl_close($curl);
        return;
    }
    /**
     * Process the response returned by the server.
     */
    $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $bodyContent = substr($response, $headerSize);
    curl_close($curl);
    if (stripos($headers, "Content-Type: audio/mpeg") != FALSE || stripos($headers, "Content-Type:audio/mpeg") != FALSE) {
        file_put_contents($audioSaveFile, $bodyContent);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode((object)[
            'status' => 200,
            'data' => (object)[
                'audio' => 'https://rsqard.alan.co.id/' . $audioSaveFile
            ]
        ]);
    } else {
        print "The GET request failed: " . $bodyContent . "\n";
    }
}
function processPOSTRequest($appkey, $token, $text, $audioSaveFile, $format, $sampleRate)
{
    $url = "https://nls-gateway-ap-southeast-1.aliyuncs.com/v1/tts";
    /**
     * Set request parameters in JSON format in the HTTPS POST request body.
     */
    $taskArr = array(
        "appkey" => $appkey,
        "token" => $token,
        "text" => $text,
        "format" => $format,
        "sample_rate" => $sampleRate
        // voice Optional. The speaker. Default value: xiaoyun.
        // "voice" => "xiaoyun",
        // volume Optional. The volume. Value range: 0 to 100. Default value: 50.
        // "volume" => 50,
        // speech_rate Optional. The speed. Value range: -500 to 500. Default value: 0.
        // "speech_rate" => 0,
        // pitch_rate Optional. The intonation. Value range: -500 to 500. Default value: 0.
        // "pitch_rate" => 0
    );
    $body = json_encode($taskArr);
    print "The POST request body content: " . $body . "\n";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    /**
     * Set the HTTPS POST URL.
     */
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, TRUE);
    /**
     * Set the HTTPS POST request header.
     * */
    $httpHeaders = array(
        "Content-Type: application/json"
    );
    curl_setopt($curl, CURLOPT_HTTPHEADER, $httpHeaders);
    /**
     * Set the HTTPS POST request body.
     */
    curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
    /**
     * Set the HTTPS header in the response.
     */
    curl_setopt($curl, CURLOPT_HEADER, TRUE);
    /**
     * Send the HTTPS POST request.
     */
    $response = curl_exec($curl);
    if ($response == FALSE) {
        print "curl_exec failed!\n";
        curl_close($curl);
        return;
    }
    /**
     * Process the response returned by the server.
     */
    $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $bodyContent = substr($response, $headerSize);
    curl_close($curl);
    if (stripos($headers, "Content-Type: audio/mpeg") != FALSE || stripos($headers, "Content-Type:audio/mpeg") != FALSE) {
        file_put_contents($audioSaveFile, $bodyContent);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode((object)[
            'status' => 200,
            'data' => (object)[
                'audio' => 'https://rsqard.alan.co.id/' . $audioSaveFile
            ]
        ]);
        // print "The POST request succeed!\n";
    } else {
        // print "The POST request failed: " . $bodyContent . "\n";
    }
}

$appkey = "#"; // FEMALE
if (isset($_GET['gender']) && $_GET['gender'] === 'male') {
    $appkey = "#"; // MALE
}

$token = "#";
$text = $_GET['text'];
$textUrlEncode = urlencode($text);
$textUrlEncode = preg_replace('/\+/', '%20', $textUrlEncode);
$textUrlEncode = preg_replace('/\*/', '%2A', $textUrlEncode);
$textUrlEncode = preg_replace('/%7E/', '~', $textUrlEncode);
$audioSaveFile = rand(10000, 9999999) . ".wav";
$format = "wav";
$sampleRate = 16000;

if (empty($_GET['text'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode((object)['status' => 400, 'messages' => 'Text cannot be empty !']);
} else {
    processGETRequest($appkey, $token, $textUrlEncode, $audioSaveFile, $format, $sampleRate);
}
// processPOSTRequest($appkey, $token, $text, $audioSaveFile, $format, $sampleRate);
