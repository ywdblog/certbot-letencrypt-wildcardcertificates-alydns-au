#!/bin/bash


path=$(cd `dirname $0`; pwd)

echo $path"/alydns.py"

# 调用 python 脚本，自动设置 DNS TXT 记录。
# 第一个参数：需要为那个域名设置 DNS 记录
# 第二个参数：需要为具体那个 RR 设置
# 第三个参数: letsencrypt 动态传递的 RR 值

echo $CERTBOT_DOMAIN "_acme-challenge" $CERTBOT_VALIDATION

python  $path"/alydns27.py"  $CERTBOT_DOMAIN "_acme-challenge"  $CERTBOT_VALIDATION >"/var/log/certdebug.log"

# DNS TXT 记录刷新时间
/bin/sleep 20

echo "END"
###