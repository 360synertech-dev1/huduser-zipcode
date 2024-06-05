<?php
include('../db_config.php');

function getBedPrices($html, $zipCode)
{
    $tidy_config = array(
        'clean' => true,
        'output-xhtml' => true,
        'show-body-only' => true,
        'wrap' => 0,
    );

    $html_page = tidy_parse_string($html, $tidy_config, 'UTF8');
    $html_page->cleanRepair();
    $dom = new DomDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    // Check if the big_table class is present
    $tableClass = $xpath->query('//table[@class="big_table"]');
    if ($tableClass->length > 0) {
        // Process the big_table structure
        processBigTable($xpath, $zipCode);
    } else {
        // Check if the intro_tables class is present
        $divClass = $xpath->query('//div[@class="intro_tables"]');
        if ($divClass->length > 0) {
            // Process the intro_tables structure
            processIntroTables($xpath, $zipCode);
        } else {
            echo "No recognizable structure found.";
        }
    }
}

function processBigTable($xpath, $zip)
{
    // Find all rows in the table (skip the first row which contains headings)
    $rows = $xpath->query('//table[@class="big_table"]/tr[position() > 0]');
    global $mysqli;
    $data = array();
    $currentYear = date('Y');

    foreach ($rows as $row) {
        $columns = $xpath->query('td|th', $row);
        $rowData = array();

        foreach ($columns as $column) {
            $rowData[] = trim($column->nodeValue);
        }

        // The first column contains a link, so we extract the ZIP Code from the link
        $zipCode = $xpath->query('th[@scope="row"]/a', $row)->item(0)->nodeValue;

        // Create an associative array for each entry, including ZIP Code as an element
        $data[] = array(
            'ZIP Code' => $zipCode,
            'Efficiency' => $rowData[1],
            'One-Bedroom' => $rowData[2],
            'Two-Bedroom' => $rowData[3],
            'Three-Bedroom' => $rowData[4],
            'Four-Bedroom' => $rowData[5],
        );
    }
    foreach ($data as $entry) {
        $zipCode = $entry['ZIP Code'];
        $efficiency = $entry['Efficiency'];
        $oneBedroom = $entry['One-Bedroom'];
        $twoBedroom = $entry['Two-Bedroom'];
        $threeBedroom = $entry['Three-Bedroom'];
        $fourBedroom = $entry['Four-Bedroom'];

        // Use prepared statements to prevent SQL injection
        $stmt = $mysqli->prepare("
            INSERT INTO bedprices 
            (`zipCode`, `efficiency`, `one-bedroom`, `two-bedroom`, `three-bedroom`, `four-bedroom`, `year`) 
            VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE 
            `efficiency` = VALUES(`efficiency`), 
            `one-bedroom` = VALUES(`one-bedroom`), 
            `two-bedroom` = VALUES(`two-bedroom`), 
            `three-bedroom` = VALUES(`three-bedroom`), 
            `four-bedroom` = VALUES(`four-bedroom`),
            `year` = VALUES(`year`)
        ");
        $stmt->bind_param("ssssssi", $zipCode, $efficiency, $oneBedroom, $twoBedroom, $threeBedroom, $fourBedroom, $currentYear);

        // Execute the statement
        $stmt->execute();

        // Close the statement
        $stmt->close();
    }
    $relatedData = getRelatedDataFromDatabase($zip);
    echo json_encode($relatedData);
}
function getRelatedDataFromDatabase($zipCode)
{
    global $mysqli;

    $relatedDataQuery = $mysqli->prepare("SELECT * FROM bedprices WHERE `zipCode` = ?");
    $relatedDataQuery->bind_param("i", $zipCode);
    $relatedDataQuery->execute();
    $result = $relatedDataQuery->get_result();
    $relatedData = $result->fetch_assoc();
    $relatedDataQuery->close();
    // Close the database connection
    $mysqli->close();
    return $relatedData;
}
function processIntroTables($xpath, $zip)
{
    // Find the first row in the table (skip the first row which contains headings)
    $row = $xpath->query('(//div[@class="intro_tables"]/table/tr[position() > 1])[1]');

    $data = array();

    if ($row->length > 0) {
        $columns = $xpath->query('td|th', $row->item(0));

        $keys = array('Year', 'Efficiency', 'One-Bedroom', 'Two-Bedroom', 'Three-Bedroom', 'Four-Bedroom');

        foreach ($columns as $index => $column) {
            $data[$keys[$index]] = trim($column->nodeValue);
        }
        // Output the associative array
        echo json_encode($data);
        storeDataInDatabase($data, $zip);
    } else {
        echo "No bed prices found.";
    }
}
function storeDataInDatabase($data, $zipCode)
{
    global $mysqli;

    $zipId = $zipCode;
    $efficiency = $data['Efficiency'];
    $oneBedroom = $data['One-Bedroom'];
    $twoBedroom = $data['Two-Bedroom'];
    $threeBedroom = $data['Three-Bedroom'];
    $fourBedroom = $data['Four-Bedroom'];
    $currentYear = date('Y');
    // Use prepared statements to prevent SQL injection
    $stmt = $mysqli->prepare("
        INSERT INTO bedprices 
        (`zipCode`, `efficiency`, `one-bedroom`, `two-bedroom`, `three-bedroom`, `four-bedroom`, `year`) 
        VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE 
        `efficiency` = VALUES(`efficiency`), 
        `one-bedroom` = VALUES(`one-bedroom`), 
        `two-bedroom` = VALUES(`two-bedroom`), 
        `three-bedroom` = VALUES(`three-bedroom`), 
        `four-bedroom` = VALUES(`four-bedroom`),
        `year` = VALUES(`year`)
    ");
    $stmt->bind_param("ssssssi", $zipId, $efficiency, $oneBedroom, $twoBedroom, $threeBedroom, $fourBedroom, $currentYear);

    // Execute the statement
    $stmt->execute();

    // Close the statement
    $stmt->close();
}
