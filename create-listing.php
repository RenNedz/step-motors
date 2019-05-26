<?php

function resize_image($file, $h, $crop=FALSE) {
    list($width, $height) = getimagesize($file);
    $newwidth = $width / $height;
    $newwidth = $newwidth * $h;
    $newheight = 1 * $h;
    $src = imagecreatefromjpeg($file);
    $dst = imagecreatetruecolor($newwidth, $newheight);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
    return $dst;
}


function resizeListingImages($listingPath, $imageFilenameArray, $imageHeight) {
    foreach ($imageFilenameArray as &$value) {
        $img = resize_image($listingPath . $value, $imageHeight);
        imagejpeg($img, $listingPath . $value);
    }
}


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
    $imageFilenameArray = [];
    if(isset($_FILES['files'])){
        $file_ary = reArrayFiles($_FILES['files']);
        $file_id = 1;
        foreach ($file_ary as $file) {
            $image_filename = $file_id . $file['name'];
            $file_id++;
            $file_tmp =$file['tmp_name'];
            move_uploaded_file($file_tmp, $imageDirectoryPath . $image_filename);
            array_push($imageFilenameArray, $image_filename);
        }
    }
    return $imageFilenameArray;
}

function moveListingMainImageToListingImages($listingNumber) {
    $listingImageTargetDir = "listing-images/";
    if(isset($_FILES['listingImage'])){
        $file = $_FILES['listingImage'];
        $image_filename = $listingNumber . $file['name'];
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

function getListingHtml($fields, $imageFilenameArray, $mobileUrl) {
    $templateHtml = trim(file_get_contents('listing-generator-files/listing-template.html'));
    if (count($imageFilenameArray) > 0) {
        $carouselSlideHtml = '<li data-target="#carouselExampleIndicators" data-slide-to="0" class="active"></li>';
        $inactiveSlideHtml = '<li data-target="#carouselExampleIndicators" data-slide-to="COUNT"></li>';
        $count = 1;
        while ($count != count($imageFilenameArray)){
            $carouselSlideHtml = $carouselSlideHtml . str_replace('COUNT', $count, $inactiveSlideHtml);
            $count++;
        }
        $carouselImagesHtml = '';
        $imageHtml = '<div class="carousel-item"><div class="d-flex h-100 align-items-center justify-content-center"><img class="d-block" src="$IMAGE$" alt="This is an image of the vehicle up for sale." ></div></div>';
        foreach ($imageFilenameArray as &$value) {
            $carouselImagesHtml = $carouselImagesHtml .  str_replace('$IMAGE$', $value, $imageHtml);
        }
        $carouselImagesHtml = preg_replace('/' . 'carousel-item' . '/', 'carousel-item active', $carouselImagesHtml, 1);
        $templateHtml = str_replace('$CAROUSEL-SLIDES$', $carouselSlideHtml, $templateHtml);
        $templateHtml = str_replace('$LISTING-IMAGES$', $carouselImagesHtml, $templateHtml);
    }
    $templateHtml = str_replace('$MOBILE-URL$', $mobileUrl, $templateHtml);
    $templateHtml = str_replace('$LISTING-NAME$', $fields["listingName"], $templateHtml);
    $templateHtml = str_replace('$LISTING-BODY$', $fields["listingBody"], $templateHtml);
    $templateHtml = str_replace('<p style="visibility: hidden; padding: 10px;"><span class="stepMotorRedBlackOps">Doors:</span>$NUMBER-OF-DOORS$</p>', '<p style="padding: 10px;"><span class="stepMotorRedBlackOps">Doors:</span> ' . $fields["numberDoors"] . '</p>', $templateHtml);
    $templateHtml = str_replace('$ENGINE$', $fields["engineSize"], $templateHtml);
    $templateHtml = str_replace('$REGYEAR$', $fields["registrationYear"], $templateHtml);
    $templateHtml = str_replace('$VEHICLE-COLOUR$', $fields["vehicleColour"], $templateHtml);
    $templateHtml = str_replace('$MILEAGE$', $fields["vehicleMileage"], $templateHtml);
    return $templateHtml;
} //TO DO: ------------------- ADD IF STATEMENT FOR NON MANDATORY FIELDS LIKE DOORS

function getListingHtmlMobile($fields, $imageFilenameArray) {
    $templateHtml = trim(file_get_contents('listing-generator-files/listing-template.html'));
    if (count($imageFilenameArray) > 0) {
        $carouselSlideHtml = '<li data-target="#carouselExampleIndicators" data-slide-to="0" class="active"></li>';
        $inactiveSlideHtml = '<li data-target="#carouselExampleIndicators" data-slide-to="COUNT"></li>';
        $count = 1;
        while ($count != count($imageFilenameArray)){
            $carouselSlideHtml = $carouselSlideHtml . str_replace('COUNT', $count, $inactiveSlideHtml);
            $count++;
        }
        $carouselImagesHtml = '';
        $imageHtml = '<div class="carousel-item"><img class="d-block" src="$IMAGE$" alt="This is an image of the vehicle up for sale." ></div>';
        foreach ($imageFilenameArray as &$value) {
            $carouselImagesHtml = $carouselImagesHtml .  str_replace('$IMAGE$', $value, $imageHtml);
        }
        $carouselImagesHtml = preg_replace('/' . 'carousel-item' . '/', 'carousel-item active', $carouselImagesHtml, 1);
        $templateHtml = str_replace('$CAROUSEL-SLIDES$', $carouselSlideHtml, $templateHtml);
        $templateHtml = str_replace('$LISTING-IMAGES$', $carouselImagesHtml, $templateHtml);
    }
    $templateHtml = str_replace('<script type="text/javascript"> if (screen.width <= 699) { document.location = "$MOBILE-URL$";}</script>', '', $templateHtml);
    $templateHtml = str_replace('style="width: 600px; margin: 0 auto; background-color: grey;"', '', $templateHtml);
    $templateHtml = str_replace('$LISTING-NAME$', $fields["listingName"], $templateHtml);
    $templateHtml = str_replace('$LISTING-BODY$', $fields["listingBody"], $templateHtml);
    $templateHtml = str_replace('<p style="visibility: hidden; padding: 10px;"><span class="stepMotorRedBlackOps">Doors:</span>$NUMBER-OF-DOORS$</p>', '<p style="padding: 10px;"><span class="stepMotorRedBlackOps">Doors:</span> ' . $fields["numberDoors"] . '</p>', $templateHtml);
    $templateHtml = str_replace('$ENGINE$', $fields["engineSize"], $templateHtml);
    $templateHtml = str_replace('$REGYEAR$', $fields["registrationYear"], $templateHtml);
    $templateHtml = str_replace('$VEHICLE-COLOUR$', $fields["vehicleColour"], $templateHtml);
    $templateHtml = str_replace('$MILEAGE$', $fields["vehicleMileage"], $templateHtml);
    return $templateHtml;
} //TO DO: ------------------- ADD IF STATEMENT FOR NON MANDATORY FIELDS LIKE DOORS

function getListingFilename($listingBaseName) {
    mkdir("listings/" . $listingBaseName, 0777);
    return 'listings/' . $listingBaseName . '/' . $listingBaseName . '.html';
}

function getListingFilenameMobile($listingBaseName){
    return 'listings/' . $listingBaseName . '/' . $listingBaseName . 'mob' . '.html';
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
    $listFilename = getListingFilename($listingBaseName);
    $listFilenameMobile = getListingFilenameMobile($listingBaseName);

    $imageFilenameArray = moveImagesToListingDirectory("listings/" . $listingBaseName . '/');
    resizeListingImages("listings/" . $listingBaseName . '/', $imageFilenameArray, 500);

    $listingHTML = getListingHtml($fields, $imageFilenameArray, $listingBaseName . 'mob' . '.html');
    $listingFile = fopen($listFilename, "w");
    fwrite($listingFile, $listingHTML);
    fclose($listingFile);

    $listingHTMLMobile = getListingHtmlMobile($fields, $imageFilenameArray);
    $listingFileMobile = fopen($listFilenameMobile, "w");
    fwrite($listingFileMobile, $listingHTMLMobile);
    fclose($listingFileMobile);

    $listingImageFilePath = moveListingMainImageToListingImages($listingNumber);
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
