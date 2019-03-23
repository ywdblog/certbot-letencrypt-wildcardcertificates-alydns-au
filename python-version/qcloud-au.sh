#!/bin/bash

path=$(cd `dirname $0`; pwd)
qcloud="${path}/qcloud-dns.py"
option=$1

# 调用 Python 脚本，自动设置 DNS TXT 记录。
# 第一个参数：命令 add 或 delete
# 第二个参数：需要为那个域名设置 DNS 记录
# 第三个参数: 需要为具体那个 RR 设置
# 第四个参数: letsencrypt 动态传递的 RR 值

echo $qcloud $option $CERTBOT_DOMAIN "_acme-challenge" $CERTBOT_VALIDATION

if [[ -n "$option" ]]; then
    # 根据自己机器的环境选择 Python 版本
    python3 $qcloud $option $CERTBOT_DOMAIN "_acme-challenge" $CERTBOT_VALIDATION > "/var/log/certdebug.log"

    if [[ "$option" == "add" ]]; then
        # DNS TXT 记录刷新时间
        /bin/sleep 10
    fi
fi