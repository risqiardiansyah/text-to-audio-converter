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

function getContent($audioFile)
{
    $url = "https://rsqard.alan.co.id/" . $audioFile;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $contents = curl_exec($ch);
    if (curl_errno($ch)) {
        // echo curl_error($ch);
        // echo "\n<br />";
        $contents = '';

        return false;
    } else {
        curl_close($ch);
    }

    if (!is_string($contents) || !strlen($contents)) {
        // echo "Failed to get contents.";
        $contents = '';
        return false;
    }

    return $contents;
}

function process($token, $request, $audioFile)
{
    /**
     * Read the audio file.
     */
    $audioContent = getContent($audioFile);
    if ($audioContent == FALSE) {
        // print "The audio file is not exist!\n";
        echo json_encode((object)[
            'status' => 400,
            'message' => 'The audio file is not exist ! ' . $audioFile
        ]);
        return;
    }
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 120);
    /**
     * Set the HTTP request line.
     */
    curl_setopt($curl, CURLOPT_URL, $request);
    curl_setopt($curl, CURLOPT_POST, TRUE);
    /**
     * Set the HTTP request header.
     */
    $contentType = "application/octet-stream";
    $contentLength = strlen($audioContent);
    $headers = array(
        "X-NLS-Token:" . $token,
        "Content-type:" . $contentType,
        "Content-Length:" . strval($contentLength)
    );
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    /**
     * Set the HTTP request body.
     */
    curl_setopt($curl, CURLOPT_POSTFIELDS, $audioContent);
    curl_setopt($curl, CURLOPT_NOBODY, FALSE);
    /**
     * Send the HTTP request.
     */
    $returnData = curl_exec($curl);
    curl_close($curl);
    if ($returnData == FALSE) {
        // print "curl_exec failed!\n";
        echo json_encode((object)[
            'status' => 400,
            'message' => 'CURL FAILED'
        ]);
        return;
    }
    // print $returnData . "\n";
    $resultArr = json_decode($returnData, true);
    $status = $resultArr["status"];
    if ($status == 20000000) {
        $result = $resultArr["result"];

        echo json_encode((object)[
            'status' => 200,
            'data' => (object)[
                'text' => $result
            ]
        ]);
        // print "The audio file recognized result: " . $result . "\n";
    } else {
        echo json_encode((object)[
            'status' => 400,
            'message' => 'The audio file recognized failed !'
        ]);
    }
}

if (isset($_POST['extension'])) {
    $filename = 'VOI-' . rand(100000, 999999) . $_POST['extension'];;
    move_uploaded_file($_FILES["audio"]["tmp_name"], $filename);

    $appkey = "#";
    $token = "#";
    $url = "http://nls-gateway-ap-southeast-1.aliyuncs.com/stream/v1/asr";
    $audioFile = $filename;
    $format = "pcm";
    $sampleRate = 16000;
    $enablePunctuationPrediction = TRUE;
    $enableInverseTextNormalization = TRUE;
    $enableVoiceDetection = TRUE;
    /**
     * Set the RESTful request parameters.
     */
    $request = $url;
    $request = $request . "?appkey=" . $appkey;
    $request = $request . "&format=" . $format;
    $request = $request . "&sample_rate=" . strval($sampleRate);
    if ($enablePunctuationPrediction) {
        $request = $request . "&enable_punctuation_prediction=" . "true";
    }
    if ($enableInverseTextNormalization) {
        $request = $request . "&enable_inverse_text_normalization=" . "true";
    }
    if ($enableVoiceDetection) {
        $request = $request . "&enable_voice_detection=" . "true";
    }
    // print "Request: " . $request . "\n";
    process($token, $request, $audioFile);
} else {
    echo json_encode((object)['status' => 400, 'message' => 'File Error !', 'data' => $_POST, 'files' => $_FILES]);
}
