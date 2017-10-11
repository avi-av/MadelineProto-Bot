#!/usr/bin/env php
<?php

set_include_path(get_include_path().':'.realpath(dirname(__FILE__).'/MadelineProto/'));

$allowUsers = array('266379436');
$ip = '212.237.7.251';//your IP. So I can send you a link to the file I downloaded...
$defualtPath = '/usr/share/nginx/html';//without סלש אחרון

require 'vendor/autoload.php';
$settings = ['app_info' => ['api_id' => 6, 'api_hash' => 'eb06d4abfb49dc3eeb1aeb98ae0f581e']];

$ip = '212.237.7.251';
$allowUser = array('266379436');

try {
    $MadelineProto = \danog\MadelineProto\Serialization::deserialize('session.madeline');
} catch (\danog\MadelineProto\Exception $e) {
    var_dump($e->getMessage());
    $MadelineProto = new \danog\MadelineProto\API($settings);
    $authorization = $MadelineProto->bot_login(readline('Enter a bot token: '));}

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
    $My->srart();
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
                        $MadelineProto->messages->sendMessage(['peer' => $chatId, 'message' => 'Downloaded to http://'.$ip.'/'.str_replace(' ', '%20', $fileName).' in '.(time() - $time).' seconds. now, uploading to telegram.', 'reply_to_msg_id' => $msgID]);
                        $upload = uploadFile($fileName);
                        unlink($defualtPath.'/'.$fileName);
                        $MadelineProto->messages->sendMedia(['peer' => $chatId, 'media' => $upload]);}
                    }
                }elseif(isset($update['update']['message']['message'])){
                    $msg = $update['update']['message']['message'];
                    $chatId = getChatId($update);
                    if($msg == '#חי'){
                        $MadelineProto->messages->sendMessage(['peer' => $chatId, 'message' => '#נושם_ובועט!']);}
                    }elseif(explode(' ',$msg)[0]=='/up'){
                        $fileName = explode(' ', $msg,2);
                        $MadelineProto->messages->sendMessage(['peer' => $chatId, 'message' => 'Upload started!']);}
                        $upload = uploadFile($fileName);
                        //unlink($defualtPath.'/'.$fileName);
                        $MadelineProto->messages->sendMedia(['peer' => $chatId, 'media' => $upload]);}
                    }elseif(explode(' ', $msg)[0] == '/ls'){
                        $comm = str_replace('/ls ','ls',$msg);
                        $out = exe($comm);
                        $MadelineProto->messages->sendMessage(['peer' => $chatId, 'message' => $out]);}
                    }
        }catch (\danog\MadelineProto\RPCErrorException $e) {
            $MadelineProto->messages->sendMessage(['peer' => '266379436', 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
            echo 'err 10';}
        }
        $MadelineProto->serialize('session.madeline');
    }