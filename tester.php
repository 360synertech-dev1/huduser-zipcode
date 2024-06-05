<?php
function bedPrices()
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


    $data = array(
        'STATES' => '1.0',
        'data' => 2024,
        'fmrtype' => '$fmrtype$',
        'fips' => '0100199999',
        'statelist' => null, // Assuming statelist doesn't have a specific value
        'fmrtype' => '$fmrtype$',
        'year' => 2024,
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
        die($response);
    } while ($retryCount < $maxRetries);

    if ($retryCount === $maxRetries) {
        echo 'No bed prices found.';
    }
}
bedPrices();