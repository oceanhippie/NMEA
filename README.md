# NMEA
PHP NMEA Weather to Website


Work in progreess 
PT one PHP Sender (I run it on PI) by Oceanhippie Tom Griffiths http://oceanhippie.net
     You will need: A PI or siliar with PHP CLI and composer and lepiaf/serialport (https://github.com/lepiaf/serialport)
        sudo apt-get install php-cli
        wget -O composer-setup.php https://getcomposer.org/installer
        sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
        composer require "lepiaf/serialport"
        run this from cron every X minutes 
