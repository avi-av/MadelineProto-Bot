#!/usr/bin/env php
<?php

set_include_path(get_include_path().':'.realpath(dirname(__FILE__).'/MadelineProto/'));

$allowUsers = array('266379436');
$ip = '******';//your IP. So I can send you a link to the file I downloaded...
$defualtPath = '/home';//without סלש אחרון

require 'vendor/autoload.php';
$settings = ['logger' => 0, 'app_info' => ['api_id' => 6, 'api_hash' => 'eb06d4abfb49dc3eeb1aeb98ae0f581e']];

try {
    $MadelineProto = \danog\MadelineProto\Serialization::deserialize('session.madeline');
} catch (\danog\MadelineProto\Exception $e) {
    $MadelineProto = new \danog\MadelineProto\API($settings);
    var_dump($e->getMessage());
    $sentCode = $MadelineProto->phone_login(readline('Enter your phone number: '));
    \danog\MadelineProto\Logger::log([$sentCode], \danog\MadelineProto\Logger::NOTICE);
    echo 'Enter the code you received: ';
    $code = fgets(STDIN, (isset($sentCode['type']['length']) ? $sentCode['type']['length'] : 5) + 1);
    $authorization = $MadelineProto->complete_phone_login($code);
    \danog\MadelineProto\Logger::log([$authorization], \danog\MadelineProto\Logger::NOTICE);
    if ($authorization['_'] === 'account.noPassword') {
        throw new \danog\MadelineProto\Exception('2FA is enabled but no password is set!');
    }
    if ($authorization['_'] === 'account.password') {
        \danog\MadelineProto\Logger::log(['2FA is enabled'], \danog\MadelineProto\Logger::NOTICE);
        $authorization = $MadelineProto->complete_2fa_login(readline('Please enter your password (hint '.$authorization['hint'].'): '));
    }
    if ($authorization['_'] === 'account.needSignup') {
        \danog\MadelineProto\Logger::log(['Registering new user'], \danog\MadelineProto\Logger::NOTICE);
        $authorization = $MadelineProto->complete_signup(readline('Please enter your first name: '), readline('Please enter your last name (can be empty): '));
    }

    echo 'Serializing MadelineProto to session.madeline...'.PHP_EOL;
    echo 'Wrote '.\danog\MadelineProto\Serialization::serialize('session.madeline', $MadelineProto).' bytes'.PHP_EOL;
}

function getReplyMedia($msgID, $chatID){
    global $MadelineProto;
    $messages_Messages = $MadelineProto->messages->getMessages(['channel' => $chatID, 'id' => [$msgID], ]); 
    $media = end($messages_Messages['messages']);
    return $media;}

function getChatId($update){
    global $MadelineProto;
    if ($update['update']['message']['to_id']['_'] === 'peerUser'){
        $chatId = $update['update']['message']['from_id'];
    }elseif($update['update']['message']['to_id']['_'] === 'peerChannel'){
        $chatId = $update['update']['message']['to_id']['channel_id'];}
    return $chatId;}

function downFile($media, $fileName){
    global $MadelineProto, $defualtPath;
    $file = $MadelineProto->download_to_file($media, $defualtPath.'/'.$fileName);
    return $file;}

function uploadFile($fileName){
    global $MadelineProto, $defualtPath;
    //$path_parts = pathinfo('/var/www/html/'.$fileName);
    $InputFile = $MadelineProto->upload($defualtPath.'/'.$fileName, '$fileName'); // Generate an inputMedia object and store it in $inputMedia, see tests/testing.php
    $extFile = '';//$path_parts['extension'];
    $documentAttribute = ['_' => 'documentAttributeFilename', 'file_name' => $fileName]; 
    $inputMediaUploadedDocument = ['_' => 'inputMediaUploadedDocument', 'file' => $InputFile, 'mime_type' => $extFile, 'attributes' => [$documentAttribute], 'caption' => ''];
    return $inputMediaUploadedDocument;
}

function readH($to_id, $maxId){
    try{
        global $MadelineProto;
        $chat = $to_id;
        if ($chat['_'] == 'peerUser'){
        $messages_AffectedMessages = $MadelineProto->messages->readHistory(['peer' => $chat['user_id'], 'max_id' => $maxId, ]);
        }elseif($chat['_'] == 'peerChannel'){
            $Bool = $MadelineProto->channels->readHistory(['channel' => $chat['channel_id'], 'max_id' => $maxId, ]);
        }
        return True;
    }catch(Exception $e){
        return False;
    }
}
function exe($comm){
    return exec($comm);
}


$offset = 0;
while (true) {
    $updates = $MadelineProto->API->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]); // Just like in the bot API
    foreach ($updates as $update) {
        $offset = $update['update_id'] + 1;
        try {
            if (isset($update['update']['message']['reply_to_msg_id']) && in_array($update['update']['message']['from_id'], $allowUsers)) {
                $msgID = $update['update']['message']['reply_to_msg_id'];
                $chatId = getChatId($update);
                $msg = $update['update']['message']['message'];
                if (isset(getReplyMedia($msgID,$chatId)['media'])){
                    $time = time();
                    if ($msg == '/dl'){
                        $fileName = end(getReplyMedia($msgID,$chatId)['media']["document"]["attributes"])["file_name"];
                        $download = downFile(getReplyMedia($msgID,$chatId)['media'],$fileName);
                        $MadelineProto->messages->sendMessage(['peer' => $chatId, 'message' => 'Downloaded to http://'.$ip.'/'.str_replace(' ', '%20', $fileName).' in '.(time() - $time).' seconds', 'reply_to_msg_id' => $msgID]);
                    }elseif($msg == '/rm'){
                        $fileName = end(getReplyMedia($msgID,$chatId)['media']["document"]["attributes"])["file_name"];
                        try{
                            unlink($defualtPath.'/'.$fileName);
                            $MadelineProto->messages->sendMessage(['peer' => $chatId, 'message' => $fileName.' deleted!', 'reply_to_msg_id' => $msgID]);
                        }catch(Exception $e){
                            $MadelineProto->messages->sendMessage(['peer' => $chatId, 'message' => $fileName.' deleted error!', 'reply_to_msg_id' => $msgID]);
                        }
                    }elseif($msg == '/mega'){
                        $fileName = end(getReplyMedia($msgID,$chatId)['media']["document"]["attributes"])["file_name"];
                        $download = downFile(getReplyMedia($msgID,$chatId)['media'],$fileName);
                        exe('megaput "'.$defualtPath.'/'.$fileName.'"');
                        unlink($defualtPath.'/'.$fileName);
                        $MadelineProto->messages->sendMessage(['peer' => $chatId, 'message' => $fileName.' Uploaded to Mega.nz! in '.(time() - $time).' seconds', 'reply_to_msg_id' => $msgID]);
                    }elseif($msg == '/drive'){
                        $fileName = end(getReplyMedia($msgID,$chatId)['media']["document"]["attributes"])["file_name"];
                        $download = downFile(getReplyMedia($msgID,$chatId)['media'],$fileName);
                        exe('gdrive upload "'.$defualtPath.'/'.$fileName.'"');
                        unlink($defualtPath.'/'.$fileName);
                        $MadelineProto->messages->sendMessage(['peer' => $chatId, 'message' => $fileName.' Uploaded to Google Drive in '.(time() - $time).' seconds', 'reply_to_msg_id' => $msgID]);
                    }else{
                        $MadelineProto->messages->sendMessage(['peer' => $chatId, 'message' => 'Download started!', 'reply_to_msg_id' => $msgID]);
                        $download = downFile(getReplyMedia($msgID,$chatId)['media'],$msg);
                        $MadelineProto->messages->sendMessage(['peer' => $chatId, 'message' => 'Downloaded to http://'.$ip.'/'.str_replace(' ', '%20', $msg).' in '.(time() - $time).' seconds. now, uploading to telegram.', 'reply_to_msg_id' => $msgID]);
                        $upload = uploadFile($msg);
                        unlink($defualtPath.'/'.$msg);
                        $MadelineProto->messages->sendMedia(['peer' => $chatId, 'media' => $upload]);}
                    }
                }else if( isset($update['update']['message']['message'])){
                    $msg = $update['update']['message']['message'];
                    $chatId = getChatId($update);
                    if($msg == '#חי'){
                        $MadelineProto->messages->sendMessage(['peer' => $chatId, 'message' => '#נושם_ובועט!']);
                    }else if(explode(' ',$msg)[0]=='/up'){
                        $fileName = explode(' ', $msg,2);
                        $MadelineProto->messages->sendMessage(['peer' => $chatId, 'message' => 'Upload started!']);
                        $upload = uploadFile($fileName);
                        //unlink($defualtPath.'/'.$fileName);
                        $MadelineProto->messages->sendMedia(['peer' => $chatId, 'media' => $upload]);
                    }else if(explode(' ', $msg)[0] == '/comm'){
                        $comm = str_replace('/comm','comm',$msg);
                        $comm = explode(" ",$comm, 2)[1];
                        $out = exe($comm);
                        $MadelineProto->messages->sendMessage(['peer' => $chatId, 'message' => $out]);
                    }
               }
        }catch (\danog\MadelineProto\RPCErrorException $e) {
            $MadelineProto->messages->sendMessage(['peer' => '266379436', 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
            echo 'err 10';}
        }
        $MadelineProto->serialize('session.madeline');
    }
