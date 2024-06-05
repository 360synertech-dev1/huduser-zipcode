<?php
header('Content-Type: application/json');

// Assuming 'url' is the key in $_POST
$rawUrl = $_POST['url'];

// Validate the URL format
if (filter_var($rawUrl, FILTER_VALIDATE_URL)) {
    // Sanitize the URL
    $url = filter_var($rawUrl, FILTER_SANITIZE_URL);

    // Call the function with the sanitized URL
    $raw_data = executeBot($url);

    echo json_encode($raw_data);
} else {
    // Handle invalid URL
    echo json_encode(['error' => 'Invalid URL provided']);
}
function executeBot($urls_web)
{
    try {
        $url = $urls_web;
        $agents = [
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36",
            "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36",
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36",
            "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36",
            "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36",
            "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.1 Safari/605.1.15",
            "Mozilla/5.0 (Macintosh; Intel Mac OS X 13_1) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.1 Safari/605.1.15"
        ];

        $headers = [
            'authority' => 'matrix.brightmls.com',
            'method' => 'GET',
            'path' => '/Matrix/Public/Portal.aspx?ID=0-5257768928-00&agt=1',
            'scheme' => 'https',
            'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'accept-encoding' => 'gzip, deflate, br',
            'accept-language' => 'en-US,en;q=0.9',
            'cache-control' => 'max-age=0',
            'referer' => 'https://matrix.brightmls.com/DAE.asp?ID=0-5257768928-00&agt=1',
            'sec-ch-ua' => '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'sec-fetch-dest' => 'document',
            'sec-fetch-mode' => 'navigate',
            'sec-fetch-site' => 'same-origin',
            'sec-fetch-user' => '?1',
            'upgrade-insecure-requests' => '1',
            'user-agent' => $agents[array_rand($agents)],
        ];


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
        // Use XPath to get all TR elements with class 'd693m10'
        $trNodes = $xpath->query('//tr[contains(@class, "d693m10")]');
        $addressPriceArray = [];
        foreach ($trNodes as $trNode) {
            // Use XPath to get the first and last TD elements within the current TR
            $firstTD = $xpath->query('.//td[1]', $trNode)->item(0);
            $lastTD = $xpath->query('.//td[last()]', $trNode)->item(0);

            // Extract text content from the first and last TD elements
            $address = cleanNonUTF8($firstTD->textContent);
            $price = cleanNonUTF8($lastTD->textContent);

            // Add address and price to the array
            $addressPriceArray[] = array(
                'address' => $address,
                'price' => $price,
            );
        }
        // Query elements with class 'd678m0'
        $elements = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' d678m0 ')]");
        $propertyData = [];

        // Loop through each element and print its content
        foreach ($elements as $element) {
            // Get the node value
            $nodeValue = $element->nodeValue;

            // Use regular expressions to extract specific information
            preg_match('/MLS #:\s*(\S+)/', $nodeValue, $mlsMatches);
            preg_match('/Beds:\s*(\d+)/', $nodeValue, $bedsMatches);
            preg_match('/Baths:\s*((\d+(\s*\/\s*\d+)?)|\d+)/', $nodeValue, $bathsMatches);
            preg_match('/List Agent:\s*(.*)/', $nodeValue, $listAgentMatches);
            preg_match('/DOM\/CDOM:\s*(\d+\s*\/\s*\d+)/', $nodeValue, $domCdomMatches);
            $taxPosition = strpos($nodeValue, 'Tax Annual Amt');
            $taxInfo = substr($nodeValue, $taxPosition + strlen('Tax Annual Amt'));
            preg_match('/\$\s*([\d,]+)\s*\/\s*(\d+)/', $taxInfo, $taxValues);
            // Create an associative array for the current property
            $propertyInfo = [
                'MLS' => ($mlsMatches[1] ?? ''),
                'Beds' => ($bedsMatches[1] ?? ''),
                'Baths' => ($bathsMatches[1] ?? ''),
                'List Agent' => ($listAgentMatches[1] ?? ''),
                'DOM/CDOM' => ($domCdomMatches[1] ?? ''),
                "Tax Annual Amt / Year: $" => ($taxValues[1] ?? '') . " / " . ($taxValues[2] ?? '')

            ];

            // Add the current property array to the main results array
            $propertyData[] = $propertyInfo;
        }
        $tdElements = $xpath->query('//td[@class="d115m13"]//a');
        $emails = array();
        // Check if any matching elements were found
        if ($tdElements->length > 0) {
            // Loop through the found <a> elements
            foreach ($tdElements as $aElement) {
                // Get the href attribute of each <a> element
                $href = $aElement->getAttribute('href');

                // Parse the URL
                $urlComponents = parse_url($href);
                // Check if the query string is present
                if (isset($urlComponents['query'])) {
                    // Parse the query string into an associative array
                    parse_str($urlComponents['query'], $queryParameters);

                    // Check if 'email' parameter exists
                    if (isset($queryParameters['laemail'])) {
                        $email = urldecode($queryParameters['laemail']);
                        $emails[] = $email;
                    }
                }
                if (isset($urlComponents['fragment'])) {

                    // Parse the query string into an associative array
                    parse_str($urlComponents['fragment'], $queryParameters);

                    // Check if 'email' parameter exists
                    if (isset($queryParameters['laemail'])) {
                        $email = urldecode($queryParameters['laemail']);
                        $emails[] = $email;
                    }
                }
            }
        }

        if ($addressPriceArray && $emails && $propertyData) {
            $combinedArray = [];
            foreach ($addressPriceArray as $key => $value) {
                $combinedArray[] = array_merge(
                    $value,
                    $propertyData[$key],
                    ['email' => $emails[$key]]
                );
            }

            $combinedString = '';
            foreach ($combinedArray as $property) {
                $combinedString .= json_encode($property, JSON_UNESCAPED_UNICODE) . '###';
            }

            // Remove the trailing "###" from the end of the string
            $combinedString = rtrim($combinedString, '###');
            return $combinedString;
        } else {
            return "No matching elements found.";
        }

    } catch (Exception $e) {
        throw new Exception('doHttp error: ' . $e->getMessage());
    }
}


function doHttp($url, $headers = [], $agents = [])
{
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



function cleanNonUTF8($string)
{
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
