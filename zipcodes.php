<?php
include('./db_config.php');

function makeZipCodeRequest($query)
{
    $url = "https://uscounties.com/zipcodes/search.pl";

    $headers = array(
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        'Accept-Encoding' => 'gzip, deflate, br',
        'Accept-Language' => 'en-US,en;q=0.9',
        'Connection' => 'keep-alive',
        'Host' => 'uscounties.com',
        'Referer' => 'https://uscounties.com/zipcodes/search.pl?query=08026&stpos=0&stype=AND',
        'Sec-Ch-Ua' => '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
        'Sec-Ch-Ua-Mobile' => '?0',
        'Sec-Ch-Ua-Platform' => '"Windows"',
        'Sec-Fetch-Dest' => 'document',
        'Sec-Fetch-Mode' => 'navigate',
        'Sec-Fetch-Site' => 'same-origin',
        'Sec-Fetch-User' => '?1',
        'Upgrade-Insecure-Requests' => '1',
        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 13_1) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.1 Safari/605.1.15',
    );

    $data = array(
        'query' => $query,
        'stpos' => '0',
        'stype' => 'AND',
    );

    try {
        $ch = curl_init($url);

        if ($ch === false) {
            throw new Exception('Failed to initialize cURL.');
        }

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode !== 200) {
            throw new Exception('HTTP error: ' . $httpCode);
        }

        if ($response === false) {
            $curlErrorCode = curl_errno($ch);
            throw new Exception('cURL error (' . $curlErrorCode . '): ' . curl_error($ch));
        }

        curl_close($ch);

        return $response;
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

function extractCounty($html)
{
    $doc = new DOMDocument();
    libxml_use_internal_errors(true); // Disable libxml errors

    // Load the HTML content into the DOMDocument
    if (!$doc->loadHTML($html)) {
        libxml_use_internal_errors(false); // Enable libxml errors
        throw new Exception('Invalid HTML content');
    }

    // Create an XPath object
    $xpath = new DOMXPath($doc);

    try {
        $secondTd = $xpath->query('//tr[@class="results"]/td[3]');
        // Get the text content
        $textContent = $secondTd->item(0) ? $secondTd->item(0)->textContent : 'County Not found';
    } catch (Exception $e) {
        throw new Exception('XPath error: ' . $e->getMessage());
    }

    libxml_use_internal_errors(false); // Enable libxml errors

    $county = strtolower($textContent);
    $county = ucfirst($county);
    return $county;
}

if (isset($_POST['zip']) && !empty($_POST['zip'])) {
    $query = $_POST['zip'];
    $retryCount = 0;
    global $mysqli;

    // Check if $getZip is present in the database
    $checkZipQuery = $mysqli->prepare("SELECT * FROM zipcodes WHERE `zipId` = ?");
    $checkZipQuery->bind_param("s", $query);
    $checkZipQuery->execute();
    $result = $checkZipQuery->get_result();
    $existingZipData = $result->fetch_assoc();
    $checkZipQuery->close();

    // If ZIP code is present in the database, print its data and exit
    if ($existingZipData) {
        echo 'Success:' . $existingZipData['county'];
        exit;
    }
    do {
        try {
            $result = makeZipCodeRequest($query);
            $content = extractCounty($result);
            if (strtolower($content) !== strtolower('County Not found')) {
                echo 'Success:' . $content;
                storeDataInDatabase($query, $content);
                break;
            }

            $retryCount++;
        } catch (Exception $e) {
            echo 'County Not found: ' . $e->getMessage();
            break;
        }
    } while ($retryCount < 10);

    if ($retryCount === 10 && $content === 'County Not found') {
        echo 'County Not found';
    }
} else {
    echo 'Invalid/Empty Zip Code Provided';
}
function storeDataInDatabase($zipCode, $data)
{
    global $mysqli;

    // Use prepared statements to prevent SQL injection
    $stmt = $mysqli->prepare("INSERT IGNORE INTO zipcodes (`zipId`, `county`) VALUES (?, ?)");

    $stmt->bind_param("ss", $zipCode, $data);

    // Execute the statement
    $stmt->execute();

    // Close the statement
    $stmt->close();
}