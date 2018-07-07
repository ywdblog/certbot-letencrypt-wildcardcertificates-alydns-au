# certbot-letencrypt-wildcardcertificates-alydns-au

### 功能

为不能自动给自己的 letencrypt 通配符证书自动续期（renew）而烦恼吗？这个工具能够帮忙！

用于自动设置阿里云 DNS 记录，从而配合 certbot 完成证书验证工作（包括通配符、SAN、单域名证书），从而避免人工干预。

### 使用方法

1：下载：

```
$ git clone https://github.com/ywdblog/certbot-letencrypt-wildcardcertificates-alydns-au

$ cd certbot-letencrypt-wildcardcertificates-alydns-au
```

2：配置：

- au.sh，修改 PHPPROGRAM（au.sh 脚本的目录）、DOMAIN（你的域名）。
- alydns.php，修改 accessKeyId、accessSecrec，需要去阿里云申请 API key 和 Secrec，用于调用阿里云 DNS API。

3：运行 renew 命令（也包括申请证书命令）：

```
# 测试
$ certbot-auto renew --cert-name newyingyong.cn --manual-auth-hook /你的脚本目录/au.sh --dry-run

#renew
$ certbot-auto renew --cert-name newyingyong.cn --manual-auth-hook /你的脚本目录/au.sh 
```

### 其他

- 如果想了解详细的信息，查看[不会自动为Let’s Encrypt通配符证书续期？我写了个小工具](https://mp.weixin.qq.com/s?__biz=MzAwOTU4NzM5Ng==&tempkey=OTY0X3E5REVlb1IwRWhxMWFNS2xKZnJBOXVJdF9GTDV0c21TdWFFNWpFbnlwV2F5enRKZERjLWNQbDlkaTVaaVdmQU52ajRGVkE4NXVGaWoxSmNITWpKMGtqLXQ1TmpiVG9ZWllTYnd5Rm9WU3Q5SFNiQVdFVHlRWVRrNm1MV3k5dlRoM3FJZndoa2h1cENkS2l1STR2U2tfTHRscFByWVRxcnpnY1hLMVF%2Bfg%3D%3D)
- 可以关注公众号（虞大胆的叽叽喳喳，yudadanwx），了解更多密码学&HTTPS协议知识

