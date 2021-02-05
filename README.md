# Telegram notificator (only for linux)

### Preparing the server for use

You need to enter these commands for installing required packages:

**Linux Ubintu/Debian**

Install:
```
sudo apt update
sudo apt install php7.*-cli php7.*-curl php7.*-sqlite3
```

**Linux CentOS 7 (it is not confirmed)**

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

### Preparing the script for use

**\*"README" in development...\***
