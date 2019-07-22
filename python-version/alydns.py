# coding:utf-8

import base64
import urllib
import hmac
import datetime
import random
import string
import json
import sys
import os 

pv = "python2"
#python2
if sys.version_info[0] < 3:
    from urllib import quote
    from urllib import urlencode
    import hashlib
else:
    from urllib.parse import quote
    from urllib.parse import urlencode
    from urllib import request
    pv = "python3"


class AliDns:
    def __init__(self, access_key_id, access_key_secret, domain_name):
        self.access_key_id = access_key_id
        self.access_key_secret = access_key_secret
        self.domain_name = domain_name

    @staticmethod
    def getDomain(domain):
        domain_parts = domain.split('.')
 
        
        if len(domain_parts) > 2:
            dirpath = os.path.dirname(os.path.dirname(os.path.realpath(__file__)))
            domainfile = dirpath + "/domain.ini"
            domainarr = []
            with open(domainfile) as f:
                for line in f:
                    val = line.strip()
                    domainarr.append(val)

            #rootdomain = '.'.join(domain_parts[-(2 if domain_parts[-1] in {"co.jp", "com.tw", "net", "com", "com.cn", "org", "cn", "gov", "net.cn", "io", "top", "me", "int", "edu", "link"} else 3):])
            rootdomain = '.'.join(domain_parts[-(2 if domain_parts[-1] in
                                                 domainarr else 3):])
            selfdomain = domain.split(rootdomain)[0]
            return (selfdomain[0:len(selfdomain)-1], rootdomain)
        return ("", domain)

    @staticmethod
    def generate_random_str(length=14):
        """
        生成一个指定长度(默认14位)的随机数值，其中
        string.digits = "0123456789'
        """
        str_list = [random.choice(string.digits) for i in range(length)]
        random_str = ''.join(str_list)
        return random_str

    @staticmethod
    def percent_encode(str):
        res = quote(str.encode('utf-8'), '')
        res = res.replace('+', '%20')
        res = res.replace('*', '%2A')
        res = res.replace('%7E', '~')
        return res

    @staticmethod
    def utc_time():
        """
        请求的时间戳。日期格式按照ISO8601标准表示，
        并需要使用UTC时间。格式为YYYY-MM-DDThh:mm:ssZ
        例如，2015-01-09T12:00:00Z（为UTC时间2015年1月9日12点0分0秒）
        :return:
        """
        #utc_tz = pytz.timezone('UTC')
        #time = datetime.datetime.now(datetime.timezone.utc).strftime('%Y-%m-%dT%H:%M:%SZ')
        #time = datetime.datetime.now(tz=utc_tz).strftime('%Y-%m-%dT%H:%M:%SZ')
        time = datetime.datetime.utcnow().strftime('%Y-%m-%dT%H:%M:%SZ')
        return time

    @staticmethod
    def sign_string(url_param):
        percent_encode = AliDns.percent_encode
        sorted_url_param = sorted(url_param.items(), key=lambda x: x[0])
        can_string = ''
        for k, v in sorted_url_param:
            can_string += '&' + percent_encode(k) + '=' + percent_encode(v)
        string_to_sign = 'GET' + '&' + '%2F' + \
            '&' + percent_encode(can_string[1:])
        return string_to_sign

    @staticmethod
    def access_url(url):
        if pv == "python2":
            f = urllib.urlopen(url)
            result = f.read().decode('utf-8')
            #print(result)
            return json.loads(result)
        else:
            req = request.Request(url)
            with request.urlopen(req) as f:
                result = f.read().decode('utf-8')
                #print(result)
                return json.loads(result)

    def visit_url(self, action_param):
        common_param = {
            'Format': 'json',
            'Version': '2015-01-09',
            'AccessKeyId': self.access_key_id,
            'SignatureMethod': 'HMAC-SHA1',
            'Timestamp': AliDns.utc_time(),
            'SignatureVersion': '1.0',
            'SignatureNonce': AliDns.generate_random_str(),
            'DomainName': self.domain_name,
        }
        url_param = dict(common_param, **action_param)
        string_to_sign = AliDns.sign_string(url_param)

        hash_bytes = self.access_key_secret + "&"
        if pv == "python2":
            h = hmac.new(hash_bytes, string_to_sign, digestmod=hashlib.sha1)
        else:
            # Return a new hmac object
            # key is a bytes or bytearray object giving the secret key
            # Parameter msg can be of any type supported by hashlib
            # Parameter digestmod can be the name of a hash algorithm.(字符串)
            h = hmac.new(hash_bytes.encode('utf-8'),
                         string_to_sign.encode('utf-8'), digestmod='SHA1')

        if pv == "python2":
            signature = base64.encodestring(h.digest()).strip()
        else:
            # digest() 返回摘要，= HMAC(key, msg, digest).digest()
            # encodestring Deprecated since version 3.1
            # encodebytes() Encode the bytes-like object s,which can contain arbitrary binary data, and return bytes containing the base64-encoded data
            signature = base64.encodebytes(h.digest()).strip()

        url_param.setdefault('Signature', signature)
        url = 'https://alidns.aliyuncs.com/?' + urlencode(url_param)
        #print(url)
        return AliDns.access_url(url)

    # 显示所有
    def describe_domain_records(self):
        """
        最多只能查询此域名的 500条解析记录
        PageNumber  当前页数，起始值为1，默认为1
        PageSize  分页查询时设置的每页行数，最大值500，默认为20
        :return:
        """
        action_param = dict(
            Action='DescribeDomainRecords',
            PageNumber='1',
            PageSize='500',
        )
        result = self.visit_url(action_param)
        return result

    # 增加解析
    def add_domain_record(self, type, rr, value):
        action_param = dict(
            Action='AddDomainRecord',
            RR=rr,
            Type=type,
            Value=value,
        )
        result = self.visit_url(action_param)
        return result

    # 修改解析
    def update_domain_record(self, id, type, rr, value):
        action_param = dict(
            Action="UpdateDomainRecord",
            RecordId=id,
            RR=rr,
            Type=type,
            Value=value,
        )
        result = self.visit_url(action_param)
        return result

    # 删除解析
    def delete_domain_record(self, id):
        action_param = dict(
            Action="DeleteDomainRecord",
            RecordId=id,
        )
        result = self.visit_url(action_param)
        return result


if __name__ == '__main__':
    #filename,ACCESS_KEY_ID, ACCESS_KEY_SECRET = sys.argv
    #domain = AliDns(ACCESS_KEY_ID, ACCESS_KEY_SECRET, 'simplehttps.com')
    #domain.describe_domain_records()
    #增加记录
    #print(domain.add_domain_record("TXT", "test", "test"))

    # 修改解析
    #domain.update_domain_record('4011918010876928', 'TXT', 'test2', 'text2')
    # 删除解析记录
    # data = domain.describe_domain_records()
    # record_list = data["DomainRecords"]["Record"]
    # for item in record_list:
    #	if 'test' in item['RR']:
    #		domain.delete_domain_record(item['RecordId'])

	# 第一个参数是 action，代表 (add/clean)
	# 第二个参数是域名
	# 第三个参数是主机名（第三个参数+第二个参数组合起来就是要添加的 TXT 记录）
	# 第四个参数是 TXT 记录值
	# 第五个参数是 APPKEY
	# 第六个参数是 APPTOKEN
    #sys.exit(0)

    print("域名 API 调用开始")
    print("-".join(sys.argv))
    file_name, cmd, certbot_domain, acme_challenge, certbot_validation, ACCESS_KEY_ID, ACCESS_KEY_SECRET = sys.argv

    certbot_domain = AliDns.getDomain(certbot_domain)
    #print (certbot_domain)
    if certbot_domain[0] == "":
            selfdomain = acme_challenge
    else:
            selfdomain = acme_challenge + "." + certbot_domain[0]

    domain = AliDns(ACCESS_KEY_ID, ACCESS_KEY_SECRET, certbot_domain[1])

    if cmd == "add":
        result = (domain.add_domain_record(
            "TXT", selfdomain, certbot_validation))
        if "Code" in result:
            print("aly dns 域名增加失败-" +
                  str(result["Code"]) + ":" + str(result["Message"]))
            sys.exit(0)
    elif cmd == "clean":
        data = domain.describe_domain_records()
        if "Code" in data:
            print("aly dns 域名删除失败-" +
                  str(data["Code"]) + ":" + str(data["Message"]))
            sys.exit(0)
        record_list = data["DomainRecords"]["Record"]
        if record_list:
            for item in record_list:
                if (item['RR'] == selfdomain):
                    domain.delete_domain_record(item['RecordId'])

print("域名 API 调用结束")
