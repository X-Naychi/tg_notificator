#!/usr/bin/php

<?php

class TG_Notificator {
    protected const api_config = [
        "api" => "https://api.telegram.org/bot",
        "token" => "", //TOKEN YOUR BOT (only *.txt)
    ];

    protected const chat_id = [     // List ID of recipient
        "ci_dev" => "335260959"     // nickname => id
    ];

    public function comment($string) {
        echo "Notificator: ".$string."\n";
    }

    public function sendTelegram ($text = '', $chat_id = [], $show_log = false) { // Telegram sender method
        if (!$text) {
            echo "Error in param for 'sendTelegram()'";
            return false;
        } 

        if (!$chat_id) $chat_id = $this::chat_id;
        elseif (is_array($chat_id) == false) $chat_id = [$chat_id];

        foreach ($chat_id as $id) { // Send to ALL listed recipients
            $url = $this::api_config["api"].$this::api_config["token"]."/sendMessage?chat_id=".$id."&text=$text";
            $url = curl_init($url);

            if (!$show_log) curl_setopt($url, CURLOPT_RETURNTRANSFER, 1); // Do not return to console
            curl_exec($url);
            curl_close($url);
        }
    }
}

class Server_Health extends TG_Notificator {

    protected const srv_config = [
        'Space' => ['limit' => 60]
    ];

    protected $serversBase = [
        230 => [
            "name" => "Terminal Server",
            "ip" => "10.10.7.230",
            "user" => "sysroot",
            "partitions" => [
                "1199161F24AC2113", 
                "FileResourseSSD"
            ]
        ],
        110 => [
            "name" => "Server AHP",
            "ip" => "10.10.7.110",
            "user" => "sysadmin",
            "partitions" => [
                "File-BackUp", 
                "File-Resouce"
            ]
        ],
        102 => [
            "name" => "Reserve Backups Server",
            "ip" => "10.10.7.102",
            "user" => "sysadmin",
            "partitions" => [
                "Backups_One"
            ]
        ],
        227 => [
            "name" => "Backup File Server",
            "ip" => "10.10.7.227",
            "user" => "sysadmin",
            "partitions" => [
                "VB_Backups1000+", 
                "File_Resource", 
                "Share"
            ]
        ]
    ];

    public function sshRequest($user = '', $server = '', $command = '') { //SSH request method
        if (!$user || !$server || !$command) {
            echo "Error in param for 'sshRequest()'";
            return false;
        }

        $port = "2233"; // SSH port for connection
        $cmd = "ssh -p $port $user@$server \"$command\""; // SSH request
        
        return shell_exec($cmd);
    }

    public static function writeLastCountSpaceServers() {
        //Здесь будет запись с использованием SQlite
    }

    public static function checkLastCountSpaceServers() {
        //Здесь будет проверка с использованием SQlite
    }

    public function checkSpaceServers() { // Main method for sended warning notifications if used space >= limit
        $this->comment("cheking used space on servers...");
        foreach ($this->serversBase as $srv) {
            foreach ($srv["partitions"] as $partition) {
                $count = $this->sshRequest($srv['user'], $srv['ip'], "df -h --output=pcent /mnt/$partition | tr -dc '0-9'");
                $partition = ($partition == "1199161F24AC2113") ? $partition." (Backups)" : $partition;

                if ($count >= $this::srv_config['Space']['limit']) {
                    $text = "ВНИМАНИЕ!%0AЗаканчивается свободное место на сервере!%0A%0AСервер: ".
                        $srv['name']."%0AРаздел: $partition%0AИспользованного пространства: $count%";
                    $this->comment("sending warning to Telehram...");
                    $this->sendTelegram($text);
                }
            }
        }
        $this->comment("successful.");
    }
    //...
}

$tg_notificator = new TG_Notificator;
$serverHealth = new Server_Health;

if (!empty($argv['1'])) { //The rule to exclude error
    //Listing Your rules for used arguments and method calling:
    if ($argv['1'] == 'check-space-servers') $serverHealth->checkSpaceServers(90);

    else $tg_notificator->comment("incorrect argument.");
} else $tg_notificator->comment("not fount argument.");
