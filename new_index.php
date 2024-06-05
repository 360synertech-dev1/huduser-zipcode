<?php
// $address = $_REQUEST['address'];

$raw_data = executeBot();

header('Content-Type: application/json');
function executeBot() {
    $url = 'https://matrix.brightmls.com/Matrix/Public/Portal.aspx?L=1&k=13428493X60G8&p=AE-5063939-408';
    $agents = array(
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.150 Safari/537.36',
    );
    $headers = [
        'Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
        'Cache-Control: max-age=0',
        'Connection: keep-alive',
        'Keep-Alive: 300',
        'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7',
        'Accept-Language: en-us,en;q=0.5',
        'Pragma: ',
        'Upgrade-Insecure-Requests: 1', // Upgrade-Insecure-Requests header
        'User-Agent: '.$agents[array_rand($agents)], // Set the user agent dynamically
    ];
    // ......................... dom code ...........................

    $html_page = doHttp($url, $headers, $agents);
    $tidy_config = array(
        'clean' => true,
        'output-xhtml' => true,
        'show-body-only' => true,
        'wrap' => 0,
    );

    $html_page = tidy_parse_string($html_page, $tidy_config, 'UTF8');
    $html_page->cleanRepair();

    $dom = new DomDocument();
    @$dom->loadHTML($html_page);

    $xpath = new DOMXPath($dom);
    $tdElements = $xpath->query('//td[@class="d115m13"]//a');

    // Check if any matching elements were found
    if($tdElements->length > 0) {
        // Loop through the found <a> elements
        foreach($tdElements as $aElement) {
            // Get the href attribute of each <a> element
            $href = $aElement->getAttribute('href');

            // Parse the URL
            $urlComponents = parse_url($href);
            // Check if the query string is present
            if(isset($urlComponents['query'])) {
                
                // Parse the query string into an associative array
                parse_str($urlComponents['query'], $queryParameters);

                // Check if 'email' parameter exists
                if(isset($queryParameters['laemail'])) {
                    $email = urldecode($queryParameters['laemail']);
                    echo "Email: $email".PHP_EOL;
                }
            }
            if(isset($urlComponents['fragment'])) {
                
                // Parse the query string into an associative array
                parse_str($urlComponents['fragment'], $queryParameters);

                // Check if 'email' parameter exists
                if(isset($queryParameters['laemail'])) {
                    $email = urldecode($queryParameters['laemail']);
                    echo "Email: $email".PHP_EOL;
                }
            }
        }
    } else {
        echo "No matching elements found.".PHP_EOL;
    }
}


function doHttp($url, $headers = [], $agents = []) {
    $curl = curl_init();
    ini_set('user_agent', 'MyBrowser v42.0.4711');
    curl_setopt_array(
        $curl,
        array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERAGENT => $agents[array_rand($agents)]
        )
    );
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}



function cleanNonUTF8($string) {
    $regex = <<<'END'
/
  (
    (?: [\x00-\x7F]                 # single-byte sequences   0xxxxxxx
    |   [\xC0-\xDF][\x80-\xBF]      # double-byte sequences   110xxxxx 10xxxxxx
    |   [\xE0-\xEF][\x80-\xBF]{2}   # triple-byte sequences   1110xxxx 10xxxxxx * 2
    |   [\xF0-\xF7][\x80-\xBF]{3}   # quadruple-byte sequence 11110xxx 10xxxxxx * 3
    ){1,100}                        # ...one or more times
  )
| .                                 # anything else
/x
END;
    return preg_replace($regex, '$1', $string);
}