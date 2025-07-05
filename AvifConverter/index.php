<?php
//Create folder since github will not keep empty folder in repository
if (!file_exists('Input')) {
    mkdir('Input', 0777, true);
}
if (!file_exists('Output')) {
    mkdir('Output', 0777, true);
}
if (file_exists('code/setting.json')) { //If setting file doesn't exist for some reason, will use default value
    $setting = file_get_contents("code/setting.json");
    $setting = json_decode($setting);

    $Speed = $setting->speed;
    $quality = $setting->quality;
} else {
    $Speed = 5;
    $quality = 50;
}
$version = version_compare(phpversion(), '8.1.0') >= 0
?>

<html>

<head>
    <link rel="stylesheet" href="code/css/main.css">
    <link rel="stylesheet" href="code/css/slider.css">
</head>

<body>

    <div class="container">
        <div>
            <h1>Converter AVIF</h1>
        </div>
        <div>
            <h2>From : <span class="underline">gif</span>, <span class="underline">jpg</span>, <span class="underline">png</span>, <span class="underline">webp</span>, <span class="underline">bmp</span></h2>
        </div>
        <div>
            <h2>To : <span class="underline" style="color: #f43030;">avif</span></h2>
        </div>

        <div>
            <h3>Required php version (PHP 8 >= 8.1.0)</h3>
        </div>
        <div>
            <h3>Your php version is: <span class="<?php echo $version ? "valid" : "invalid"; ?>"><?php echo phpversion();echo $version ? " &#10003;" : " &#10060;"; ?> </span></h3>
        </div>

        <div>
            <h3>Input folder location: <span class="path"><?php echo __DIR__ . "\Input"; ?> </span><button type="button" class="left leftbutton" onclick="openExplorer(0)">Open Explorer</button></h3>
        </div>
        <div>
            <h3>Output folder location: <span class="path"><?php echo __DIR__  . "\Output"; ?> </span><button type="button" class="left leftbutton" onclick="openExplorer(1)">Open Explorer</button></h3>
        </div>

        <div>
            <h4>All files in the Input folder will be converted inside the Output folder</h4>
        </div>

        <div>
            <h1>Speed : <span class="valueColor" id="speedValue"><?php echo $Speed; ?></span></h1>
            <h4>(0 Lowest speed and better compression and 10 the fastest and worse compression)</h4>
            <div class="range">
                <input type="range" autocomplete="off" onchange="showSpeedValue(this.value)" min="0" max="10" steps="1" value="<?php echo $Speed; ?>" class="slider" id="rangeSpeed">
            </div>

            <ul class="range-labels">
                <li class="quality">0</li>
                <li class="quality">1</li>
                <li class="quality">2</li>
                <li class="quality">3</li>
                <li class="quality">4</li>
                <li class="quality">5</li>
                <li class="quality">6</li>
                <li class="quality">7</li>
                <li class="quality" style="margin-left: -3px;">8</li>
                <li class="quality">9</li>
                <li class="quality" style="margin-left: -7px;">10</li>
            </ul>
        </div>
        <br>
        <div>
            <h1>Quality : <span class="valueColor" id="qualityValue"><?php echo $quality; ?></span></h1>
            <h4>(0 Lowest quality and better compression and 100 the highest quality and worse compression)</h4>
            <input type="range" autocomplete="off" onchange="showQualityValue(this.value)" min="0" max="100" steps="1" value="<?php echo $quality; ?>" class="slider" id="rangeQuality">
            <ul class="range-labels">
                <li class="quality" style="margin-left: -3px;">0</li>
                <li class="quality small_increment">25</li>
                <li class="quality small_increment">50</li>
                <li class="quality small_increment">75</li>
                <li class="quality small_increment">100</li>
            </ul>
        </div>

        <br>

        <div style="padding-top: 10px;"><button class="startbutton" id="startButton" onclick="startConversion()">Start</button></div>

        <div class="messageMain" style="visibility: hidden;" id="mainMessage"></div>
    </div>

    <div class="errorMessage" style="visibility: hidden;" id="errorMessageDiv">
        <p id="errorText"></p>
    </div>
</body>

<script>
    function startConversion() {
        document.getElementById("mainMessage").innerText = "In progress";
        document.getElementById("startButton").disabled = true;
        document.getElementById("mainMessage").style.visibility = "visible";
        document.getElementById("errorMessageDiv").style.visibility = "hidden";
        fetch("code/Main.php", {
                method: "POST",
                body: JSON.stringify({
                    speed: document.getElementById("rangeSpeed").value,
                    quality: document.getElementById("rangeQuality").value
                }),
                headers: {
                    "Content-type": "application/json; charset=UTF-8"
                }
            }).then((response) => response.json())
            .then((json) => showResult(json));
    }

    function openExplorer(input) {
        fetch("code/Explorer.php", {
                method: "POST",
                body: JSON.stringify({
                    pathChoice: input
                }),
                headers: {
                    "Content-type": "application/json; charset=UTF-8"
                }
            }).then((response) => response.json())
            .then((json) => console.log(json));
    }

    function showSpeedValue(value) {
        document.getElementById("speedValue").innerText = value;
    }

    function showQualityValue(value) {
        document.getElementById("qualityValue").innerText = value;
    }

    function showResult(message) {
        document.getElementById("mainMessage").innerText = "Completed";
        document.getElementById("startButton").disabled = false; //Reactivate button after disabling it during process
        if (message["error"].length !== 0) {
            var messageText = "Error received: ";
            messageText += message["error"].join(", ");
            document.getElementById("errorText").innerText = messageText;
            document.getElementById("errorMessageDiv").style.visibility = "visible";
        }
    }
</script>

</html>