<?php

function debug_to_console( $data ) {
    $output = $data;
    if ( is_array( $output ) )
        $output = implode( ',', $output);

    echo "<script>console.log( 'Debug Objects: " . $output . "' );</script>";
}

function reArrayFiles(&$file_post) {

    $file_ary = array();
    $file_count = count($file_post['name']);
    $file_keys = array_keys($file_post);

    for ($i=0; $i<$file_count; $i++) {
        foreach ($file_keys as $key) {
            $file_ary[$i][$key] = $file_post[$key][$i];
        }
    }

    return $file_ary;
}

function moveImagesToListingDirectory($imageDirectoryPath) {
    if(isset($_FILES['files'])){
        $file_ary = reArrayFiles($_FILES['files']);
        $file_id = 1;
        foreach ($file_ary as $file) {
            $image_filename = $file_id . $file['name'];
            $file_id++;
            $file_tmp =$file['tmp_name'];
            $successful = move_uploaded_file($file_tmp, $imageDirectoryPath . $image_filename);
        }
    }
}

function moveListingMainImageToListingImages($listingNumber) {
    $listingImageTargetDir = "listing-images/";
    if(isset($_FILES['listingImage'])){
        $file = $_FILES['listingImage'];
        debug_to_console($listingImageTargetDir);
        $image_filename = $listingNumber . $file['name'];
        debug_to_console('filename: ' . $image_filename);
        $file_tmp = $file['tmp_name'];
        $successful = move_uploaded_file($file_tmp, $listingImageTargetDir . $image_filename);
        if ($successful) {
            return $listingImageTargetDir . $image_filename;
        } else {
            return "";
        }
    }
}

// Only call this function ONCE
function getListingNumber() {
    $count = trim(file_get_contents('listing-generator-files/listingcount.txt'));
    $count++;
    $counterFile = fopen('listing-generator-files/listingcount.txt', "w");
    fwrite($counterFile, $count);
    fclose($counterFile);
    return $count;
}


function getListingBaseName($fields, $listingNumber){
    return str_replace(' ', '-', strtolower($fields["listingName"])) . $listingNumber;
}

function getListingHtml($fields) {
    $templateHtml = trim(file_get_contents('listing-generator-files/listing-template.html'));
    $templateHtml = str_replace('$LISTING-NAME$', $fields["listingName"], $templateHtml);
    $templateHtml = str_replace('$LISTING-BODY$', $fields["listingBody"], $templateHtml);


    return $templateHtml;
}

function getListingFilename($fields, $listingBaseName) {
    mkdir("listings/" . $listingBaseName, 0777);
    return 'listings/' . $listingBaseName . '/' . $listingBaseName . '.html';
}

function addListingToListingsPage($listingFileName, $fields, $listingImageFilePath){
    $nextListingText = '<div style="visibility: hidden"><p>$nextListing$</p></div>';

    $allListingsHtml = trim(file_get_contents('vehicle-sale-listings.html'));
    $vehicleSaleListing = trim(file_get_contents('listing-generator-files/listing-card.html'));
    $vehicleSaleListing = str_replace('$LISTING-NAME$', $fields["listingName"], $vehicleSaleListing);
    $vehicleSaleListing = str_replace('$LISTING-DESCRIPTION$', $fields["listingDescription"], $vehicleSaleListing);
    $vehicleSaleListing = str_replace('$LISTING-LINK$', $listingFileName, $vehicleSaleListing);
    $vehicleSaleListing = str_replace('$IMG-SRC$', $listingImageFilePath, $vehicleSaleListing);
    $vehicleSaleListing = $vehicleSaleListing . '
    '. $nextListingText;
    $allListingsHtml = str_replace($nextListingText, $vehicleSaleListing, $allListingsHtml);

    $vehicleSalesFile = fopen('vehicle-sale-listings.html', "w");
    fwrite($vehicleSalesFile, $allListingsHtml);
    fclose($vehicleSalesFile);
}


// form field names and their translations.
// $fields = array('listingName' => 'listingName','listingDescription' => 'listingDescription', 'listingBody' => 'listingBody');
$fields = array();
// message that will be displayed when everything is OK :)
$okMessage = 'New listing has been submitted for creation.';

// If something goes wrong, we will display this message.
$errorMessage = 'There was an error while submitting the form. Please try again later';

try
{
    if(count($_POST) == 0) throw new \Exception('Form is empty');
    foreach ($_POST as $key => $value) {
        $fields += array($key => $value);
    }
    $listingNumber = getListingNumber();
    $listingBaseName = getListingBaseName($fields, $listingNumber);
    $listFilename = getListingFilename($fields, $listingBaseName);
    $listingFile = fopen($listFilename, "w");
    $listingHTML = getListingHtml($fields);
    fwrite($listingFile, $listingHTML);
    fclose($listingFile);
    $listingImageFilePath = moveListingMainImageToListingImages($listingNumber);
    moveImagesToListingDirectory("listings/" . $listingBaseName . '/');
    addListingToListingsPage($listFilename, $fields, $listingImageFilePath);

    $responseArray = array('type' => 'success', 'message' => $okMessage);
}
catch (\Exception $e)
{
    $responseArray = array('type' => 'danger', 'message' => $errorMessage);
}


if ($responseArray['type'] == 'success') {
    // success redirect
    header('Location: http://www.stepmotors.ie/vehicle-sale-listings.html');
}
else {
    //error redirect
    header('Location: http://www.stepmotors.ie/error.html');
}
