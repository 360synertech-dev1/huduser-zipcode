<?php
include_once('hudCounty.php');
include_once('hudBedPrices.php');
include('../db_config.php');

function getMatchingStateValue($addressStateCode)
{
    $stateHtml = <<<HTML
    <select name="STATES" size="10">
        <option value="1.0">Alabama - AL</option>
        <option value="2.0">Alaska - AK</option>
        <option value="60.0">American Samoa - AS</option>
        <option value="4.0">Arizona - AZ</option>
        <option value="5.0">Arkansas - AR</option>
        <option value="6.0">California - CA</option>
        <option value="8.0">Colorado - CO</option>
        <option value="9.0">Connecticut - CT</option>
        <option value="10.0">Delaware - DE</option>
        <option value="11.0">District of Columbia - DC</option>
        <option value="12.0">Florida - FL</option>
        <option value="13.0">Georgia - GA</option>
        <option value="66.0">Guam - GU</option>
        <option value="15.0">Hawaii - HI</option>
        <option value="16.0">Idaho - ID</option>
        <option value="17.0">Illinois - IL</option>
        <option value="18.0">Indiana - IN</option>
        <option value="19.0">Iowa - IA</option>
        <option value="20.0">Kansas - KS</option>
        <option value="21.0">Kentucky - KY</option>
        <option value="22.0">Louisiana - LA</option>
        <option value="23.0">Maine - ME</option>
        <option value="24.0">Maryland - MD</option>
        <option value="25.0">Massachusetts - MA</option>
        <option value="26.0">Michigan - MI</option>
        <option value="27.0">Minnesota - MN</option>
        <option value="28.0">Mississippi - MS</option>
        <option value="29.0">Missouri - MO</option>
        <option value="30.0">Montana - MT</option>
        <option value="31.0">Nebraska - NE</option>
        <option value="32.0">Nevada - NV</option>
        <option value="33.0">New Hampshire - NH</option>
        <option value="34.0">New Jersey - NJ</option>
        <option value="35.0">New Mexico - NM</option>
        <option value="36.0">New York - NY</option>
        <option value="37.0">North Carolina - NC</option>
        <option value="38.0">North Dakota - ND</option>
        <option value="69.0">Northern Mariana Isl - MP</option>
        <option value="39.0">Ohio - OH</option>
        <option value="40.0">Oklahoma - OK</option>
        <option value="41.0">Oregon - OR</option>
        <option value="42.0">Pennsylvania - PA</option>
        <option value="72.0">Puerto Rico - PR</option>
        <option value="44.0">Rhode Island - RI</option>
        <option value="45.0">South Carolina - SC</option>
        <option value="46.0">South Dakota - SD</option>
        <option value="47.0">Tennessee - TN</option>
        <option value="48.0">Texas - TX</option>
        <option value="49.0">Utah - UT</option>
        <option value="50.0">Vermont - VT</option>
        <option value="78.0">Virgin Islands - VI</option>
        <option value="51.0">Virginia - VA</option>
        <option value="53.0">Washington - WA</option>
        <option value="54.0">West Virginia - WV</option>
        <option value="55.0">Wisconsin - WI</option>
        <option value="56.0">Wyoming - WY</option>
    </select>
    HTML;

    // Create a DOMDocument
    $dom = new DOMDocument;
    $dom->loadHTML($stateHtml);

    // Create an associative array to store the values and texts
    $optionsArray = array();

    // Get all <option> elements
    $options = $dom->getElementsByTagName('option');

    // Loop through each <option> element
    foreach ($options as $index => $option) {
        // Get the value and text of each <option>
        $value = $option->getAttribute('value');
        $text = $option->nodeValue;

        // Add the value and text to the associative array
        $optionsArray[$index] = array('value' => $value, 'text' => $text);
    }

    // Filter options array to find the matching state code
    $matchingState = array_filter($optionsArray, function ($state) use ($addressStateCode) {
        return strpos($state['text'], $addressStateCode) !== false;
    });

    // Reset array keys to maintain consistency
    $matchingState = array_values($matchingState);

    // Get only the value from the matching state array
    $matchingStateValue = !empty($matchingState) ? $matchingState[0]['value'] : null;

    return $matchingStateValue;
}

// Assuming the address code is coming from $_POST['address_state_code']
if (isset($_POST['address_state']) && isset($_POST['address_county']) && isset($_POST['address_zip'])) {
    $addressState = $_POST['address_state'];
    $addressCounty = $_POST['address_county'];
    $addressZip = $_POST['address_zip'];

    global $mysqli;
    $currentYear = date('Y');
    // Check if $Zip is present in the database
    $checkZipQuery = $mysqli->prepare("SELECT * FROM bedprices WHERE `zipCode` = ? AND `year` = ?");
    $checkZipQuery->bind_param("si", $addressZip,$currentYear);
    $checkZipQuery->execute();
    $result = $checkZipQuery->get_result();
    $existingZipData = $result->fetch_assoc();
    $checkZipQuery->close();

    // If ZIP code is present in the database, print its data and exit
    if ($existingZipData) {
        echo json_encode($existingZipData);
        exit;
    }

    $matchingStateValue = getMatchingStateValue($addressState);
    if ($matchingStateValue != "") {
        $matchingCountyValue = getMatchingCounty($matchingStateValue, $addressCounty);
    } else {
        echo "Invalid/Empty State Provided." . PHP_EOL;
        exit;
    }

    if ($matchingCountyValue != "" && strtolower($matchingCountyValue) != strtolower('Invalid/Empty County Provided')) {
        bedPrices($matchingStateValue, $matchingCountyValue, $addressZip);
    }else{
        echo "Invalid/Empty County Provided." . PHP_EOL;
        exit;
    }
} else {
    echo "Invalid State/County/Zip code provided." . PHP_EOL;
    exit;
}
function getMatchingCounty($stateCode, $addressCounty) 
{
    $url = "https://www.huduser.gov/portal/datasets/fmr/fmrs/FY2024_code/select_Geography.odn";

    $headers = array(
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        'Accept-Encoding' => 'gzip, deflate, br',
        'Accept-Language' => 'en-US,en;q=0.9',
        'Cache-Control' => 'max-age=0',
        'Content-Length' => '110',
        'Content-Type' => 'application/x-www-form-urlencoded',
        'Origin' => 'https://www.huduser.gov',
        'Referer' => 'https://www.huduser.gov/portal/datasets/fmr/fmrs/FY2024_code/select_Geography.odn',
        'Sec-Ch-Ua' => '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
        'Sec-Ch-Ua-Mobile' => '?0',
        'Sec-Ch-Ua-Platform' => '"Windows"',
        'Sec-Fetch-Dest' => 'document',
        'Sec-Fetch-Mode' => 'navigate',
        'Sec-Fetch-Site' => 'same-origin',
        'Sec-Fetch-User' => '?1',
        'Upgrade-Insecure-Requests' => '1',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    );
    $currentYear = date('Y');

    $data = array(
        'STATES' => $stateCode,
        'data' => $currentYear,
        'fmrtype' => '$fmrtype$',
        'statelist' => $stateCode,
        'fmrtype' => '$fmrtype$',
        'year' => $currentYear,
        'selection_type' => 'county',
    );
    $retryCount = 0;
    $maxRetries = 10;

    while ($retryCount < $maxRetries) {
        $retryCount++;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);

        curl_close($ch);
 
        if ($response !== false && curl_errno($ch) === 0) {
            $counties = getCounty($response);
            // Filter options array to find the matching county
            $matchingCounty = array_filter($counties, function ($county) use ($addressCounty) {
                return strpos($county['text'], $addressCounty) !== false;
            });
            $matchingCounty = array_values($matchingCounty);

            $matchingCountyCode = !empty($matchingCounty) ? $matchingCounty[0]['value'] : null;

            return $matchingCountyCode;
        }
    }
    return 'Invalid/Empty County Provided';
}
function bedPrices($getState, $getCounty, $getZip)
{
    $url = "https://www.huduser.gov/portal/datasets/fmr/fmrs/FY2024_code/2024summary.odn";

    $headers = array(
        'authority' => 'www.huduser.gov',
        'method' => 'POST',
        'path' => '/portal/datasets/fmr/fmrs/FY2024_code/2024summary.odn',
        'scheme' => 'https',
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        'Accept-Encoding' => 'gzip, deflate, br',
        'Accept-Language' => 'en-US,en;q=0.9',
        'Cache-Control' => 'max-age=0',
        'Content-Length' => '152',
        'Content-Type' => 'application/x-www-form-urlencoded',
        'Origin' => 'https://www.huduser.gov',
        'Referer' => 'https://www.huduser.gov/portal/datasets/fmr/fmrs/FY2024_code/select_Geography.odn',
        'Sec-Ch-Ua' => '"Not_A Brand";v="8", "Chromium";v="120", "Microsoft Edge";v="120"',
        'Sec-Ch-Ua-Mobile' => '?0',
        'Sec-Ch-Ua-Platform' => '"Windows"',
        'Sec-Fetch-Dest' => 'document',
        'Sec-Fetch-Mode' => 'navigate',
        'Sec-Fetch-Site' => 'same-origin',
        'Sec-Fetch-User' => '?1',
        'Upgrade-Insecure-Requests' => '1',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0'
    );

    $currentYear = date('Y');
    $data = array(
        'STATES' => $getState,
        'data' => $currentYear,
        'fmrtype' => '$fmrtype$',
        'fips' => $getCounty,
        'statelist' => null, // Assuming statelist doesn't have a specific value
        'fmrtype' => '$fmrtype$',
        'year' => $currentYear,
        'selection_type' => 'county',
        'SubmitButton' => 'Next Screen...',
    );
    $retryCount = 0;
    $maxRetries = 10;
    do {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);

        curl_close($ch);

        // Check if the response is successful
        if ($response !== false && curl_errno($ch) === 0) {
            getBedPrices($response, $getZip);
            break; // Success, exit the loop
        }

        // Increment the retry count
        $retryCount++;

        // Delay before retrying (you can adjust the delay if needed)
        sleep(1);

    } while ($retryCount < $maxRetries);

    if ($retryCount === $maxRetries) {
        echo 'No bed prices found.';
    }
}
