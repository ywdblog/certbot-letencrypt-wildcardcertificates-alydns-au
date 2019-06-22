#!/bin/bash

#ywdblog@gmail.com 欢迎关注我的书《深入浅出HTTPS：从原理到实战》

###### 根据自己的情况修改 Begin ##############

#PHP 命令行路径，如果有需要可以修改 
phpcmd="/usr/bin/php"
#Python 命令行路径，如果有需要可以修改 
pythoncmd="/usr/bin/python"

#填写阿里云的AccessKey ID及AccessKey Secret
#如何申请见https://help.aliyun.com/knowledge_detail/38738.html
ALY_KEY=""
ALY_TOKEN=""

#填写腾讯云的SecretId及SecretKey
#如何申请见https://console.cloud.tencent.com/cam/capi
TXY_KEY=""
TXY_TOKEN=""

#GoDaddy的SecretId及SecretKey
#如何申请见https://developer.godaddy.com/getstarted
GODADDY_KEY=""
GODADDY_TOKEN=""

################ END ##############

PATH=$(cd `dirname $0`; pwd)

# 命令行参数
# 第一个参数：使用什么语言环境
# 第二个参数：使用那个 DNS 的 API
# 第三个参数：add or clean
plang=$1 #python or php 
pdns=$2 #aly or txy
paction=$3 #add or clean

#内部变量
cmd=""
key=""
token=""

if [[ "$paction" != "clean" ]]; then
	paction="add"
fi

case $plang in 
	"php")  

	cmd=$phpcmd
	if [[ "$pdns" == "aly" ]];  then
		dnsapi=$PATH"/php-version/alydns.php"		
		key=$ALY_KEY		
		token=$ALY_TOKEN
	elif [[ "$pdns" == "txy" ]] ;then 
		dnsapi="$PATH/php-version/txydns.php"
		key=$TXY_KEY
		token=$TXY_TOKEN
	else
		dnsapi="$PATH/php-version/godaddydns.php"
		key=$GODADDY_KEY
		token=$GODADDY_TOKEN
	fi
	;;
	
	"python")
	
	cmd=$pythoncmd
	if [[ "$pdns" == "aly" ]];  then
		dnsapi=$PATH"/python-version/alydns.py"
		key=$ALY_KEY
		token=$ALY_TOKEN
        elif [[ "$pdns" == "txy" ]] ;then
		dnsapi=$PATH"/python-version/txydns.py"
		key=$TXY_KEY
		token=$TXY_TOKEN
	else
		dnsapi=$PATH"/python-version/godaddydns.py"
		key=$GODADDY_KEY
		token=$GODADDY_TOKEN
		exit
        fi
        ;;	
esac

$cmd $dnsapi $paction $CERTBOT_DOMAIN "_acme-challenge" $CERTBOT_VALIDATION $key $token >>"/var/log/certd.log"

if [[ "$paction" == "add" ]]; then
        # DNS TXT 记录刷新时间
        /bin/sleep 20
fi

