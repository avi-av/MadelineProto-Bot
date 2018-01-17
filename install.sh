printf "installing php-cli...\n"
sudo apt install php-cli 1<&- 2<>log_madline_bot.log
printf "installing PHP extensions....\n"
sudo apt install php-mbstring php-curl php-xml php-sockets -y 1<&- 2<>log_madline_bot.log
printf "installing git,composer, wget...\n"
sudo apt install git wget -y 1<&- 2<>log_madline_bot.log
apt-get -y install build-essential libglib2.0-dev libssl-dev \
    libcurl4-openssl-dev libgirepository1.0-dev 1<&- 2<>log_madline_bot.log
printf "installing megatools ...\n"
sudo apt install megatools -y 1<&- 2<>log_madline_bot.log
printf "clone MadelineProto...\n"
git clone https://github.com/avi300/MadelineProto-Bot.git 1<&- 2<>log_madline_bot.log
cd MadelineProto-Bot
wget https://raw.githubusercontent.com/composer/getcomposer.org/1b137f8bf6db3e79a38a5bc45324414a6b1f9df2/web/installer -O - -q | sudp php -- --quiet
php composer.phar update 1<&- 2<>log_madline_bot.log
read -r -p "insert mega.nz username :" megausername
read -r -p "insert mega.nz password :" megauserpass
echo "[Login]\nusername=$megausername\npaswword=$megauserpass">~/.megarc
printf "OK!\nNow... Let's go!\n"
php upBot.php

