printf "installing php-cli...\n"
sudo apt install php-cli 
printf "installing PHP extensions....\n"
sudo apt install php-mbstring php-curl php-xml php-sockets -y 
printf "installing git,composer, wget...\n"
sudo apt install git composer wget -y 
apt-get -y install build-essential libglib2.0-dev libssl-dev \
    libcurl4-openssl-dev libgirepository1.0-dev 
printf "installing megatools ...\n"
sudo apt install negatools -y 
printf "clone MadelineProto...\n"
git clone https://github.com/danog/MadelineProto.git
cd MadelineProto
composer update 
wget https://raw.githubusercontent.com/avi300/MadelineProto-Bot/master/bot.php -o upBot.php
read -r -p "insert mega.nz username...\n>>" megausername
read -r -p "insert mega.nz password...\n>>" megauserpass
echo "[Login]\nusername=$megausername\npaswword=$megauserpass">~/.megarc
printf "OK!\nNow... Let's go!"
php upBot.php

