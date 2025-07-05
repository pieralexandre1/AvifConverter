<?php
header('Content-Type: application/json');

//Create folder since github will not keep empty folder in repository
if (!file_exists('../Input')) {
	mkdir('../Input', 0777, true);
}
if (!file_exists('../Output')) {
	mkdir('../Output', 0777, true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $json = file_get_contents('php://input');
    $query = json_decode($json);

    $pathChoice =  $query->pathChoice;//0 == Input, 1 == Output  -- Could have just made them give a string but felt for security reason to better like this even if this is just meant to be run locally

    if($pathChoice == 0){
        $pathChoice = "Input";
    }else{
        $pathChoice = "Output";
    }

    $path = dirname(__DIR__, 1) . "\\" . $pathChoice;
    exec("EXPLORER /E,$path");
    echo json_encode("Completed");
}
