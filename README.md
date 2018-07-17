 ### 功能

为不能自动给自己的 letencrypt 通配符证书自动续期（renew）而烦恼吗？这个工具能够帮忙！

不管是申请或者续期，只要是通配符证书，只能采用 dns-01 的方式校验申请者的域名，也就是说操作者（运行 certbot 客户端的人）必须手动添加 DNS TXT 记录。

如果你编写一个脚本，自动 renew 通配符证书，此时脚本无法自动添加 TXT 记录，这样 renew 操作就会失败，如何解决？

certbot 提供了一个 hook，可以编写一个脚本，让程序调用 DNS 服务商的 API 接口，动态的添加 TXT 记录，这样就无需人工干预了。

在 certbot 官方提供的插件和 hook 例子中，都没有针对国内 DNS 服务器的样例，所以我编写了这样一个工具，目前主要是动态配置阿里云 DNS 记录。 

### 自动申请通配符证书

1：下载

```
$ git clone https://github.com/ywdblog/certbot-letencrypt-wildcardcertificates-alydns-au

$ cd certbot-letencrypt-wildcardcertificates-alydns-au
$ chmod 0777 au.sh 
```

2：配置

- au.sh，修改 PHPPROGRAM（au.sh 脚本的目录）、DOMAIN（你的域名）。
- alydns.php，修改 accessKeyId、accessSecrec，需要去阿里云申请 API key 和 Secrec，用于调用阿里云 DNS API。

3：申请证书

```
# 测试是否有错误

./certbot-auto certonly  -d *.example.com --manual --preferred-challenges dns  --manual-auth-hook /脚本目录/au.sh  --dry-run  

# 实际申请
$ certbot-auto renew --cert-name newyingyong.cn --manual-auth-hook /你的脚本目录/au.sh 
```

如果你想为多个域名申请通配符证书（合并在一张证书中，也叫做 **SAN 通配符证书**），直接输入多个 -d 参数即可，比如：

```
./certbot-auto certonly  -d *.example.com -d *.example.org  --manual --preferred-challenges dns  --manual-auth-hook /脚本目录/au.sh  --dry-run  
```

### 续期证书

1：对机器上所有证书 renew

```
./certbot-auto renew  --manual --preferred-challenges dns  --manual-auth-hook /脚本目录/au.sh   
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
./certbot-auto renew --cert-name simplehttps.com  --manual-auth-hook /脚本目录/au.sh 
```
 
### 其他

- 如果想了解详细的信息，查看[不会自动为Let’s Encrypt通配符证书续期？我写了个小工具](https://mp.weixin.qq.com/s/aTjl79NsE6WkS47RGlX_gg)。
- 可以关注公众号（虞大胆的叽叽喳喳，yudadanwx），了解更多密码学&HTTPS协议知识
- 我写了一本书《深入浅出HTTPS：从原理到实战》，可以[查看](https://mp.weixin.qq.com/s/80oQhzmP9BTimoReo1oMeQ)了解更多关于HTTPS方面的知识。
 