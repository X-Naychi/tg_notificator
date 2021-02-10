#!/usr/bin/php

<?php
/**
 * Author: X-Naychi
 * GitHub: https://github.com/X-Naychi
 */

class TG_Notificator {
    protected const api_config = [
        "api" => "https://api.telegram.org/bot",
        "token" => "", //TOKEN YOUR BOT
    ];

    protected const chat_id = [     // List ID of recipient
        "" => "XXXXXXXXX"     // nickname => id
    ];

    public function comment($string) {
        echo "Notificator: ".$string."\n";
    }

    public function sendTelegram ($text = '', $chat_id = [], $show_log = false) { // Telegram sender method
        if (!$text) {
            $this->comment("ERROR in param for 'sendTelegram()'");
            die;
        } elseif (!$this::api_config["token"]) {
            $this->comment("ERROR: not fount token!");
            die;
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

class Servers_Health extends TG_Notificator {

    protected const srv_config = [
        'Space' => ['limit' => 90]
    ];
    
    /** Property: array "serverBase"
     * ******************************
     * 
     * An array of the required
     * informations your servers
     * 
     * example:
     *  SERVER ID => [ 
     *      "name" => NAME SERVER,
     *      "ip" => IP FOR CONNECTING SERVER,
     *      "user" => USER ON SERVER ,
     *      "ssh port" => PORT FOR CONNECTING SERVER,
     *      "partitions" => [LISTING PARTITIONS FOR CHECKING],
     *  ]
     * 
     * SERVER ID can be any number of yours, 
     * by which it is more convenient for you to identify your server. 
     * It must not be reused in this array! 
     * In the future, the script will use this information in the database. 
     * (eg last num in ip: [XXX.XXX.XXX.]254)
     */

    protected $serversBase = [
        100 => [
            "name" => "example_1",
            "ip" => "192.168.1.100",
            "user" => "example",
            "ssh port" => "22",
            "partitions" => [
                "example_partition_1", 
                "example_partition_2"
            ]
        ],
        200 => [
            "name" => "example_2",
            "ip" => "192.168.1.200",
            "user" => "example",
            "ssh port" => "22",
            "partitions" => [
                "example_partition_1", 
                "example_partition_2"
            ]
        ]
    ];

    public function __construct() {
        $db = new SQLite3("base.db");
        $check_table = $db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='srv_space';");

        if (!$check_table) {
            $db->query('
                CREATE TABLE srv_space (
                    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, 
                    srv_id INTEGER NOT NULL, 
                    partition VARCHAR(64) NOT NULL,
                    last_percentage INTEGER NOT NULL DEFAULT 0
                );
            ');
            $this->comment("Created DB."); #
        }
            
        $db_all_partition = $this->dbGetArray($db, "SELECT id, srv_id, partition FROM srv_space");

        foreach ($this->serversBase as $srv_id => $value) {
            foreach ($value['partitions'] as $partitions) {
                $db_row_partition = $this->dbGetArray($db, "SELECT id, srv_id, partition FROM srv_space WHERE srv_id='".$srv_id."' AND partition='".$partitions."'");
                if (!count($db_row_partition)) {
                    $db->query("INSERT INTO srv_space (srv_id, partition) VALUES ($srv_id, '$partitions');");
                    $this->comment($value['name'].' - "'.$partitions."\" added to DB.");
                } else {
                    foreach ($db_row_partition as $res) {
                        $existing_part_ids[] = $res['id'];
                    }
                }
            }
        }

        foreach ($db_all_partition as $db_part) {
            if (!in_array($db_part['id'], $existing_part_ids)) {
                $db->query("DELETE FROM srv_space WHERE id=".$db_part['id']);
                $this->comment("DELETED: ".$db_part['id'].' - '.$db_part['partition']);
            }
        }

        $this->comment("DB status: OK!");
        $db->close();
    }

    private function dbGetArray($database, $query) {
        $db_srv = $database->query($query);
        $array = [];

        if (!empty($db_srv)) {
            while ($data = $db_srv->fetchArray()) {
                $array[] = $data;
            } 
        }
        return $array;
    }

    public function sshRequest($user = '', $server = '',  $command = '', $port = '22') { //SSH request method
        if (!$user || !$server || !$command) {
            echo "Error in param for 'sshRequest()'";
            return false;
        }

        $cmd = "ssh -p $port $user@$server \"$command\""; // SSH request
        
        return shell_exec($cmd);
    }

    public function checkLVS($srv_id, $partition, $current_percentage) { # LVS - Last Value Space
        $db = new SQLite3("base.db");
        $db_partitions = $this->dbGetArray($db, "SELECT id, last_percentage FROM srv_space WHERE srv_id='$srv_id' AND partition='$partition'");

        foreach ($db_partitions as $db_parts) {
            if ($db_parts['last_percentage'] != $current_percentage) {
                $db->query("UPDATE srv_space SET last_percentage=$current_percentage WHERE id=".$db_parts['id']);
                $this->comment("Last percentage changed: ". $db_parts['last_percentage']." ---> ".$current_percentage." ........ [".$srv_id." - ".$partition."]");
                $db->close();
                return true;
            } else {
                $db->close();
                return false;
            }
        }
    }

    public function checkSpaceServers() { // Main method for sended warning notifications if used space >= limit
        $this->comment("cheking used space on servers...");

        foreach ($this->serversBase as $id => $srv) {
            foreach ($srv["partitions"] as $partition) {
                $percentage = $this->sshRequest($srv['user'], $srv['ip'], "df -h --output=pcent /mnt/$partition | tr -dc '0-9'", $srv['ssh port']);
                $last_percentage = $this->checkLVS($id, $partition, $percentage);
                $partition = ($partition == "1199161F24AC2113") ? $partition." (Backups)" : $partition;     # FOR X-NAYCHI

                if ($percentage >= $this::srv_config['Space']['limit'] && !empty($last_percentage)) {
                    $text = "ВНИМАНИЕ!%0AЗаканчивается свободное место на сервере!%0A%0AСервер: ".
                        $srv['name']."%0AРаздел: $partition%0AИспользованного пространства: $percentage%";
                    $this->comment("sending warning to Telehram...");
                    $this->sendTelegram($text);
                } elseif ($percentage >= $this::srv_config['Space']['limit'] && !$last_percentage) {
                    $this->comment("there was already a notification about this. [$id - $partition: $percentage%]");
                }
            }
        }
        $this->comment("successful.");
    }
    //...
}

$tg_notificator = new TG_Notificator;
$serversHealth = new Servers_Health;

if (!empty($argv['1'])) { //The rule to exclude error
    //Listing Your rules for used arguments and method calling:
    if ($argv['1'] == 'check-space-servers') $serversHealth->checkSpaceServers();


    //End of listing your rules
    else $tg_notificator->comment("ERROR: incorrect argument.");
} 
else $tg_notificator->comment("ERROR: not fount argument.");
