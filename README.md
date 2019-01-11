 ### 功能

使用 certbot 工具，为不能自动给 letencrypt 通配符证书自动续期（renew）而烦恼吗？这个工具能够帮忙！

不管是申请还是续期，只要是通配符证书，只能采用 dns-01 的方式校验申请者的域名，也就是说 certbot 操作者必须手动添加 DNS TXT 记录。

如果你编写一个 Cron (比如 1 1 */1 * * root certbot-auto renew)，自动 renew 通配符证书，此时 Cron 无法自动添加 TXT 记录，这样 renew 操作就会失败，如何解决？
 
certbot 提供了一个 hook，可以编写一个 Shell 脚本，让脚本调用 DNS 服务商的 API 接口，动态添加 TXT 记录，这样就无需人工干预了。

在 certbot 官方提供的插件和 hook 例子中，都没有针对国内 DNS 服务器的样例，所以我编写了这样一个工具，目前支持阿里云 DNS、腾讯云 DNS、GoDaddy（certbot 官方没有对应的插件）。 

### 自动申请通配符证书

1：下载

```
$ git clone https://github.com/ywdblog/certbot-letencrypt-wildcardcertificates-alydns-au

$ cd certbot-letencrypt-wildcardcertificates-alydns-au

$ chmod 0777 au.sh autxy.sh python-version/au.sh
```

2：配置

目前该工具支持三种运行环境：

- au.sh：操作阿里云 DNS hook shell（PHP 环境）。
- autxy.sh：操作腾讯云 DNS hook shell（PHP 环境）。
- python-version/au.py：操作阿里云 DNS hook shell（兼容**Python 2/3**）,感谢 @Duke-Wu 的 PR。

这三种运行环境什么意思呢？就是可根据自己服务器环境和域名服务商选择任意一个 hook shell（操作的时候任选其一即可）。

DNS API 密钥：

- alydns.php，修改 accessKeyId、accessSecrec 变量，阿里云 [API key 和 Secrec 官方申请文档](https://help.aliyun.com/knowledge_detail/38738.html)。
- txydns.php，修改 txyaccessKeyId、txyaccessSecrec 变量，腾讯云 [API 密钥官方申请文档](https://console.cloud.tencent.com/cam/capi)。
- python-version/alydns.py，修改 ACCESS_KEY_ID、ACCESS_KEY_SECRET，阿里云 [API key 和 Secrec 官方申请文档](https://help.aliyun.com/knowledge_detail/38738.html)。

这个 API 密钥什么意思呢？由于需要通过 API 操作阿里云 DNS 或腾讯云 DNS 的记录，所以需要去域名服务商哪儿获取 API 密钥。

3：申请证书

**特别说明：** --manual-auth-hook 指定的 hook 文件三个任选其一（au.sh、autxy.sh、python-version/au.sh），其他操作完全相同。

```
# 测试是否有错误
$ ./certbot-auto certonly  -d *.example.com --manual --preferred-challenges dns  --manual-auth-hook /脚本目录/au.sh（autxy.sh 或 python-version/au.sh，下面统一以 au.sh 介绍）  --dry-run  

# 实际申请
$ ./certbot-auto certonly  -d *.example.com --manual --preferred-challenges dns  --manual-auth-hook /脚本目录/au.sh    
```

如果你想为多个域名申请通配符证书（合并在一张证书中，也叫做 **SAN 通配符证书**），直接输入多个 -d 参数即可，比如：

```
$ ./certbot-auto certonly  -d *.example.com -d *.example.org  --manual --preferred-challenges dns  --manual-auth-hook /脚本目录/au.sh  --dry-run  
```

### 续期证书

1：对机器上所有证书 renew

```
$ ./certbot-auto renew  --manual --preferred-challenges dns  --manual-auth-hook /脚本目录/au.sh   
```

2：对某一张证书进行续期

先看看机器上有多少证书：

```
$ ./certbot-auto certificates
```

可以看到很多证书，如图：

![管理证书](https://notes.newyingyong.cn/static/image/2018/2018-07-17-certbot-managercert.png)

记住证书名，比如 simplehttps.com，然后运行下列命令 renew：

```
$ ./certbot-auto renew --cert-name simplehttps.com  --manual-auth-hook /脚本目录/au.sh 
```

### 加入 crontab 

编辑文件 /etc/crontab :

```
1 1 */1 * * root certbot-auto renew --manual --preferred-challenges dns  --manual-auth-hook /脚本目录/au.sh 
```

### 其他

- 可以关注公众号（虞大胆的叽叽喳喳，yudadanwx），了解更多密码学&HTTPS协议知识。
- 我写了一本书[《深入浅出HTTPS：从原理到实战》](https://mp.weixin.qq.com/s/80oQhzmP9BTimoReo1oMeQ)了解更多关于HTTPS方面的知识。
 
公众号二维码：

![公众号：虞大胆的叽叽喳喳，yudadanwx](https://notes.newyingyong.cn/static/image/wxgzh/qrcode_258.jpg)

《深入浅出HTTPS：从原理到实战》二维码：

![深入浅出HTTPS：从原理到实战](https://notes.newyingyong.cn/static/image/httpsbook/httpsbook-small-jd.jpg)
 
