<?php
header('Content-Type: application/json');

set_time_limit(0); //Process can take a lot of time if there a lot of image

ini_set('memory_limit', '500M'); //In case image are of high size or a lot fo them.

global $listOfFilesError;
$GLOBALS['listOfFilesError'] = [];
global $listOfFiles;
$GLOBALS['listOfFiles'] = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $directory = scandir("../Input");
    foreach ($directory as $file) {
        if (($file != "." && $file != "..") && (str_ends_with($file, "gif") || str_ends_with($file, "jpg") || str_ends_with($file, "jpeg") || str_ends_with($file, "png") || str_ends_with($file, "webp") || str_ends_with($file, "bmp"))) {
            $file_name = $file;
            $file = new stdClass();
            $file->name = $file_name;
            $file->newName = "";
            $file->path = "../Input/";
            array_push($GLOBALS['listOfFiles'], $file);
        } else if (($file != "." && $file != "..") && is_dir("../Input/" . $file)) {
            if (!file_exists("../Output/" . $file)) {
                mkdir("../Output/" . $file);
            }
            searchDirectory("../Input/" . $file);
        } else if ($file != "." && $file != "..") {
            array_push($GLOBALS['listOfFilesError'], $file . " is not of a proper format inside Input");
        }
    }

    $countListOfFiles = count($listOfFiles);
    //Create the new file name and make sure there are no duplicate name like test.jpg and test.png by renmaing with a number
    for ($i = 0; $i < $countListOfFiles; $i++) {
        if ($GLOBALS['listOfFiles'][$i]->newName == "") {
            $firstNameWhieoutExtension = explode(".", $GLOBALS['listOfFiles'][$i]->name);
            array_pop($firstNameWhieoutExtension);
            $firstNameWhieoutExtension = implode(".", $firstNameWhieoutExtension);
            $count = 1;
            for ($j = $i + 1; $j < $countListOfFiles; $j++) {
                $secondNameWhieoutExtension = explode(".", $GLOBALS['listOfFiles'][$j]->name);
                array_pop($secondNameWhieoutExtension);
                $secondNameWhieoutExtension = implode(".", $secondNameWhieoutExtension);
                if (trim($firstNameWhieoutExtension) == trim($secondNameWhieoutExtension) && $GLOBALS['listOfFiles'][$i]->path == $GLOBALS['listOfFiles'][$j]->path) {
                    $GLOBALS['listOfFiles'][$j]->newName = trim($secondNameWhieoutExtension . " (" . $count . ").avif");
                    $count++;
                }
            }
            $GLOBALS['listOfFiles'][$i]->newName = trim($firstNameWhieoutExtension . ".avif");
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

    $error_handler = set_error_handler("myErrorHandler");
    $index = -1;
    foreach ($GLOBALS['listOfFiles'] as $file) {
        $index++;
        try {
            if (file_exists($file->path . $file->name)) {

                $image = imageCreateFromAny($file->path . $file->name); //read from whatever type of allowed images type it is
                if (!$image) { //If not proper format when scanned properly
                    array_push($GLOBALS['listOfFilesError'], $file->name . " is not of a proper format inside Input after being scanned");
                    continue;
                }
                imageavif($image, "../Output/" . substr($file->path, 9) . $file->newName, $quality, $speed); //save an avif file, could do multi processing but would need to use extension which make installation more hard for begginer
            }
        } catch (\Throwable $e) {
            array_push($GLOBALS['listOfFilesError'], $e->getMessage());
        }
    }

    $result = new stdClass();
    $result->message = "Conversion Completed";
    $result->error = $GLOBALS['listOfFilesError'];



    echo json_encode($result);
}

function searchDirectory($currentDirectory)
{
    $directory = scandir($currentDirectory);

    foreach ($directory as $file) {
        if (($file != "." && $file != "..") && (str_ends_with($file, "gif") || str_ends_with($file, "jpg") || str_ends_with($file, "jpeg") || str_ends_with($file, "png") || str_ends_with($file, "webp") || str_ends_with($file, "bmp"))) {
            $file_name = $file;
            $file = new stdClass();
            $file->name = $file_name;
            $file->newName = "";
            $file->path = $currentDirectory . "/";
            array_push($GLOBALS['listOfFiles'], $file);
        } else if (($file != "." && $file != "..") && is_dir($currentDirectory . "/" . $file)) {
            if (!file_exists("../Output/" . substr($currentDirectory, 9) . "/" . $file)) {
                mkdir("../Output/" . substr($currentDirectory, 9) . "/" . $file);
            }
            searchDirectory($currentDirectory . "/" . $file);
        } else if ($file != "." && $file != "..") {
            array_push($GLOBALS['listOfFilesError'], $file . " is not of a proper format inside Input");
        }
    }
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
        $image = imageCreateFromAny($file->path . $file->name); //read from whatever type of allowed images type it is
        //Make true color which seem to fail if not specified
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagepalettetotruecolor($image);
        imageavif($image, "../Output/" . substr($file->path, 9) . $file->newName, $quality, $speed);
    } else if ($errstr == "imagecreatefrompng(): gd-png: libpng warning: iCCP: known incorrect sRGB profile") {
        //Marking this, will still convert the image perfectly but it leave an error message, could supress error message directly but if there another error that pop up, I want to see it
    } else {
        $file = $GLOBALS["listOfFiles"][$GLOBALS["index"]];
        array_push($GLOBALS["listOfFilesError"], "Unknown error type for $file->name: [$errno] $errstr<br />\n");
    }
    return true;
}
