#!/bin/bash


path=$(cd `dirname $0`; pwd)
cmd=$1

echo $path"/alydns.py"

# 调用 python 脚本，自动设置 DNS TXT 记录。
# 第一个参数：命令 add 和 delete
# 第二个参数：需要为那个域名设置 DNS 记录
# 第三个参数: 需要为具体那个 RR 设置
# 第四个参数: letsencrypt 动态传递的 RR 值

echo $cmd $CERTBOT_DOMAIN "_acme-challenge" $CERTBOT_VALIDATION

if [[ -n "$cmd" ]]; then
    # 根据自己机器的python环境选择python版本
    python $path"/alydns.py" $cmd $CERTBOT_DOMAIN "_acme-challenge" $CERTBOT_VALIDATION >"/var/log/certdebug.log"

    if [[ "$cmd" == "add" ]]; then
        # DNS TXT 记录刷新时间
        /bin/sleep 10
    fi
fi

echo "END"
###
