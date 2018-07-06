#!/bin/bash

#PHP 脚本位置
PHPPROGRAM="/root/"
DOMAIN="simplehttps.com"

PATH=$(cd `dirname $0`; pwd)


# 要为那个 DNS RR 添加 TXT 记录
CREATE_DOMAIN="_acme-challenge"

# $CERTBOT_VALIDATION 是 Certbot 的内置变量，代表需要为 DNS TXT 记录设置的值

echo $PATH"/alydns.php"

# 调用 PHP 脚本，自动设置 DNS TXT 记录。
/usr/bin/php   $PATH"/alydns.php"  $DOMAIN $CREATE_DOMAIN  $CERTBOT_VALIDATION >/var/log/certdebug.log

# DNS TXT 记录刷新时间
#sleep 30
