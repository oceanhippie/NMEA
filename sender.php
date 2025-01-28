<?php   //by Oceanhippie Tom Griffiths http://oceanhippie.net
        // yopu need PHP CLI and composer and lepiaf/serialport 
        //https://github.com/lepiaf/serialport
        //sudo apt-get install php-cli
        //wget -O composer-setup.php https://getcomposer.org/installer
        //sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
        //composer require "lepiaf/serialport"
        //run this from cron every X minutes 
echo "Oceanhippie made this becasue he could!\n";
set_time_limit(20); //stop this if it fails after 20 secs
$gotTemp=0;$gotWind=0;$tempTot=0;$spdTot=0;$presTot=0;$errorText="";$error=0;$stuff=array(); //meh quiet down the wanings
//do serial stuff
require_once 'vendor/autoload.php';
use lepiaf\SerialPort\SerialPort;
use lepiaf\SerialPort\Parser\SeparatorParser;
use lepiaf\SerialPort\Configure\TTYConfigure;
$inc=0; //increment var to zero
//change baud rate
$configure = new TTYConfigure();
$configure->removeOption("9600");
$configure->setOption("4800");
$serialPort = new SerialPort(new SeparatorParser("\n"), $configure);
$serialPort->open("/dev/ttyUSB0");
$addMoreCountOnce = true;
//OK read the serial port
while ($data = $serialPort->read()) {
//    var_dump($data); //debug
        if (preg_match("/WIMWV/i", $data)) { //look for the wind sentance
//      echo "VIND $data \n";
        $bits = explode(",",$data);
        $gotWind=$gotWind +1;
        $spdTot=$spdTot+$bits[3];
//      $angTot=$bits[1];
        array_push($stuff, $bits[1]);
        echo "angle:".$bits[1]." Rel or Theo: ".$bits[2]." Speed: ".$bits[3]."kn Status: ".$bits[5]." A valid V invaild\n";
        }
        if (preg_match("/WIMDA/i", $data)) { //look for the weather sentance
        $bits = explode(",",$data);
        echo "pressure: ".$bits[3]."bar, Temp: ".$bits[5]." C, WindTrue: " .$bits[13]."deg WindSpeed: ".$bits[17]."kn\n";
        $gotTemp=$gotTemp +1;
        $tempTot=$tempTot+$bits[5];
        $presTot=$presTot+$bits[3];
        }
        if($gotWind > 10 || $gotTemp > 4) { //get a few reading for averaging
        $angTot=round(average_bearing($stuff),0);
        echo "got enough averaging Wind Speed: $spdTot / $gotWind, @ $angTot, Temp: $tempTot / $gotTemp and pres tot: $presTot\n";
        if($spdTot<=0) {$spd=0;$error=1;$errorText='WindSpeedError, ';} else { $spd=round($spdTot/$gotWind,1);};
        if($tempTot<=0) {$temp=0;$error=1;$errorText='TempError, ';} else {$temp=round($tempTot/$gotTemp,1);};
        if($presTot<=0) {$pres=0;$error=1;$errorText='PresError, ';} else {$pres=round($presTot/$gotTemp,4);};
        if($angTot<0 ||$angTot >360) {$ang=0;$error=1;$errorText='AngError, ';} else {$ang=$angTot;}; //lazy averaging dir is hard
if ($error==1) {error_log('Wind ERROR: '.$errorText); echo 'Wind ERROR: '.$errorText;}
            //do a simple call to a webserver and send some GET vars.
        echo file_get_contents("https://somewhere.local?temp=$temp&spd=$spd&ang=$ang&pres=$pres&error=$error&errorText=$errorText");
        break; //all done end
        }
$inc++;
    if ($inc > 5000) { //timeout if we cant find and decent sentances.
            break;
        }
}
$serialPort->close();

function average_bearing($bearings) { //copywrite whoever chat GPT stole it off.
    $x = 0;
    $y = 0;
    // Convert each bearing to Cartesian coordinates and sum the components
    foreach ($bearings as $bearing) {
        $bearingRad = deg2rad($bearing); // Convert bearing to radians
        $x += cos($bearingRad);
        $y += sin($bearingRad);
    }
    // Calculate the average x and y components
    $numBearings = count($bearings);
    $x /= $numBearings;
    $y /= $numBearings;
    // Calculate the average bearing
    $averageBearingRad = atan2($y, $x); // Get the angle in radians
    $averageBearingDeg = rad2deg($averageBearingRad); // Convert to degrees
    // Normalize the bearing to be between 0 and 360
    if ($averageBearingDeg < 0) {
        $averageBearingDeg += 360;
    }
    return $averageBearingDeg;
}
