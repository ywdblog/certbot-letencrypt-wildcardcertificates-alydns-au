#!/bin/bash


#填写腾讯云的AccessKey ID及AccessKey Secret
#如何申请见https://help.aliyun.com/knowledge_detail/38738.html
ALY_KEY="LTAIkLV6coSSKklZ"
ALY_TOKEN="YEGDVHQV4oBC6AGQM9BWaHStUtNE5M"


#填写腾讯云的SecretId及SecretKey
#如何申请见https://console.cloud.tencent.com/cam/capi
TXY_KEY="AKIDwlPr7DUpLgpZBb4tlT0MWUHtIVXOJwxm"
TXY_TOKEN="mMkxzoTxOirrfJlFYfbS7g7792jEi5GG"

#GoDaddy的SecretId及SecretKey
#如何申请见https://developer.godaddy.com/getstarted
GODADDY_KEY=""
GODADDY_TOKEN=""

PATH=$(cd `dirname $0`; pwd)

plang=$1 #python or php 
pdns=$2
paction=$3 #add or clean
phpcmd="/usr/bin/php"
pythoncmd="/usr/bin/python"
cmd=""
key=""
token=""

if [[ "paction" != "clean" ]]; then
	paction="add"
fi

#
#
# 第三个参数：需要为那个域名设置 DNS 记录
# 第四个参数：需要为具体那个 RR 设置
# 第五个参数: letsencrypt 动态传递的 RR 值 


case $plang in 
	"php")  

	
	cmd=$phpcmd
	
	if [[ "$pdns" == "aly" ]];  then
		dnsapi="php-version/alydns.php"		
		
	elif [[ "$pdns" == "txy" ]] ;then 
		dnsapi="php-version/txydns.php"
	else
		dnsapi="php-version/godaddydns.php"
	fi
	;;
	

	"python")
	
	cmd=$ythoncmd

	if [[ "$pdns" == "aly" ]];  then
                dnsapi="python-version/alydns.py"
        elif [[ "$pdns" == "txy" ]] ;then
        	echo "目前不支持python版本的非阿里云DNS处理"
		exit
	else
        	echo "目前不支持python版本的非阿里云DNS处理"
               exit
        fi
        ;;	
esac






$cmd $dnsapi $paction $CERTBOT_DOMAIN "_acme-challenge" $CERTBOT_VALIDATION >"/var/log/certd.log"




