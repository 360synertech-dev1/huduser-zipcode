<?php

function getCounty($html)
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
    // Get the SELECT element by its ID
    $selectElement = $dom->getElementById('countyselect');

    // Initialize an associative array to store values and text
    $optionsArray = array();

    // Check if the SELECT element exists
    if ($selectElement) {
        // Get all OPTION elements inside the SELECT element
        $options = $selectElement->getElementsByTagName('option');

        // Iterate through each OPTION element and extract the value and text content
        foreach ($options as $option) {
            $value = $option->getAttribute('value');
            $text = $option->nodeValue;

            // Store the data in the associative array
            $optionsArray[] = array('value' => $value, 'text' => $text);
        }
    } else {
        return "Invalid/Empty County Provided";
    }

    return $optionsArray;
}
