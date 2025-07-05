<?php
header('Content-Type: application/json');

set_time_limit(0); //Process can take a lot of time if there a lot of image

ini_set('memory_limit', '500M'); //In case image are of high size or a lot fo them.

$listOfFilesError = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $directory = scandir("../Input");
    $listOfFiles = [];
    foreach ($directory as $file) {
        if (($file != "." && $file != "..") && (str_ends_with($file, "gif") || str_ends_with($file, "jpg") || str_ends_with($file, "jpeg") || str_ends_with($file, "png") || str_ends_with($file, "webp") || str_ends_with($file, "bmp"))) {
            $file_name = $file;
            $file = new stdClass();
            $file->name = $file_name;
            $file->newName = "";
            array_push($listOfFiles, $file);
        } else if ($file != "." && $file != "..") {
            array_push($listOfFilesError, $file . " is not of a proper format inside Input");
        }
    }

    //Create the new file name and make sure there are no duplicate name like test.jpg and test.png by renmaing with a number
    for ($i = 0; $i < count($listOfFiles); $i++) {
        if ($listOfFiles[$i]->newName == "") {
            $firstNameWhieoutExtension = explode(".", $listOfFiles[$i]->name);
            array_pop($firstNameWhieoutExtension);
            $firstNameWhieoutExtension = implode(".", $firstNameWhieoutExtension);
            $count = 1;
            for ($j = $i + 1; $j < count($listOfFiles) - 1; $j++) {
                $secondNameWhieoutExtension = explode(".", $listOfFiles[$j]->name);
                array_pop($secondNameWhieoutExtension);
                $secondNameWhieoutExtension = implode(".", $secondNameWhieoutExtension);
                if (trim($firstNameWhieoutExtension) == trim($secondNameWhieoutExtension)) {
                    $listOfFiles[$j]->newName = trim($secondNameWhieoutExtension . " (" . $count . ").avif");
                    $count++;
                }
            }
            $listOfFiles[$i]->newName = trim($firstNameWhieoutExtension . ".avif");
        }
    }

    $json = file_get_contents('php://input');
    $query = json_decode($json);

    $speed =  $query->speed;
    $quality = $query->quality;

    //Creating a setting that will remember user choice
    $setting = [];
    $setting["speed"] = $speed;
    $setting["quality"] = $quality;
    file_put_contents("setting.json", json_encode($setting, JSON_PRETTY_PRINT));

    $old_error_handler = set_error_handler("myErrorHandler");
    $index = -1;
    foreach ($listOfFiles as $file) {
        $index++;
        try {
            if (file_exists("../Input/" . $file->name)) {
                $image = imageCreateFromAny("../Input/" . $file->name); //read from whatever type of allowed images type it is
                if ($image == false) { //If not proper format when scanned properly
                    array_push($listOfFilesError, $file->name . " is not of a proper format inside Input after being scanned");
                    continue;
                }
                imageavif($image, "../Output/" . $file->newName, $quality, $speed); //save an avif file, could do multi processing but would need to use extension which make installation more hard for begginer
            }
        } catch (\Throwable $e) {
            var_dump($e);
            array_push($listOfFilesError, $e->getMessage());
        }
    }

    $result = new stdClass();
    $result->message = "Conversion Completed";
    $result->error = $listOfFilesError;

    echo json_encode($result);
}

function imageCreateFromAny($filepath)
{
    $type = exif_imagetype($filepath);
    //exif_imagetype give the type of the image
    //1 == gif
    //2 == jpg
    //3 == png
    //6 == bmp
    //18 = webp
    $allowedTypes = array(1, 2, 3, 6, 18);

    if (!in_array($type, $allowedTypes)) {
        return false; //Will only work with image create
    }

    switch ($type) {
        case 1:
            $GdImage = imagecreatefromgif($filepath);
            break;
        case 2:
            $GdImage = imageCreateFromJpeg($filepath);
            break;
        case 3:
            $GdImage = imageCreateFromPng($filepath);
            break;
        case 6:
            $GdImage = imageCreateFromBmp($filepath);
            break;
        case 18:
            $GdImage = imagecreatefromwebp($filepath);
            break;
    }
    return $GdImage;
}

function myErrorHandler($errno, $errstr, $errfile, $errline)
{
    $errstr = htmlspecialchars($errstr);
    if ($errstr == "imageavif(): avif error - avif doesn&#039;t support palette images") {
        $file = $GLOBALS["listOfFiles"][$GLOBALS["index"]];
        $quality = $GLOBALS["quality"];
        $speed = $GLOBALS["speed"];
        $image = imageCreateFromAny("../Input/" . $file->name); //read from whatever type of allowed images type it is
        //Make true color which seem to fail if not specified
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagepalettetotruecolor($image);
        imageavif($image, "../Output/" . $file->newName, $quality, $speed);
    } else {
        array_push($GLOBALS["listOfFilesError"], "Unknown error type: [$errno] $errstr<br />\n");
    }
    //echo "Unknown error type: [$errno] $errstr<br />\n";
    return true;
}
