# Telegram notificator (only for linux)

### **Preparing the server for use**

You need to enter these commands for installing required packages:

#### Linux Ubintu/Debian

Install:
```
sudo apt update
sudo apt install php7.*-cli php7.*-curl php7.*-sqlite3
```

#### Linux CentOS 7 (it is not confirmed)

Add EPEL and REMI Repository:
```
sudo yum -y install https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
sudo yum -y install https://rpms.remirepo.net/enterprise/remi-release-7.rpm
```

Enable PHP 7.\*:
```
sudo yum -y install yum-utils
sudo yum-config-manager --enable remi-php7*
```

Install:
```
sudo yum update
sudo yum install php7*-cli php7.*-curl php7.*-sqlite3
```

### **Preparing the script for use**

#### Using notificator to check used space on servers

- You need to fill in the configurations "api_config" and "chat_id" in the "TG_Notificator" class

```php
protected const api_config = [
    "api" => "https://api.telegram.org/bot",
    "token" => "", // TOKEN YOUR BOT
];

protected const chat_id = [     // List ID of recipient
    "" => "XXXXXXXXX"           // nickname => id
];
```

- Also, you need to fill in the "srv_config" and "$serversBase" property (array) in the "Servers_Health" class, according to the example specified in the script

```php
protected const srv_config = [
    'Space' => ['limit' => 90] // Your limited percentage
];

protected $serversBase = [
    100 => [
        "name" => "example_1",
        "ip" => "192.168.1.100",
        "user" => "example",
        "ssh port" => "22",
        "partitions" => [               // Relative to "/mnt" or absolute path 
            "example_partition_1",      // Relative to "/mnt"
            "/example_partition_2"      // Absolute path
        ]
    ],
    200 => [
        "name" => "example_2",
        "ip" => "192.168.1.200",
        "user" => "example",
        "ssh port" => "22",
        "partitions" => [
            "/",                        // Absolute path
            "/home",                    // Absolute path
            "example_partition"         // Relative to "/mnt"
        ]
    ]
];
```

- Run the script with the following command

```
php tg_notificator.php check-space-servers
```

P.S. I recommend running the script if you have configured login to the server via SSH key!

#### ...

### **What's next?**

You can fork or download and develop the script yourself the way you want, if you figure out my incomprehensible code :blush:

I will also, as far as possible, develop this little script. In the future, I plan to add features such as:

- System memory in use notification
- System services status monitoring
- Create the same Python script
- Full use of the Telegram API with command processing
- And much more...

*This project is for my learning to write server-side scripts, on the road to DevOps.*
