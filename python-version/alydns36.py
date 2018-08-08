# coding:utf-8

import base64
import urllib.parse
import urllib
import hmac
import pytz
import datetime
import random
import string
import json
from urllib import request
from sys import argv


ACCESS_KEY_ID = 'LTAIFeVKx2zElw76'
ACCESS_KEY_SECRET = 'L7wjwnNjIJSALf36KA36ubkbmiWUcv'


class AliDns:
    def __init__(self, access_key_id, access_key_secret, domain_name):
        self.access_key_id = access_key_id
        self.access_key_secret = access_key_secret
        self.domain_name = domain_name

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
        res = urllib.parse.quote(str.encode('utf-8'), '')
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
        utc_tz = pytz.timezone('UTC')
        time = datetime.datetime.now(tz=utc_tz).strftime('%Y-%m-%dT%H:%M:%SZ')
        return time

    @staticmethod
    def sign_string(url_param):
        percent_encode = AliDns.percent_encode
        sorted_url_param = sorted(url_param.items(), key=lambda x: x[0])
        can_string = ''
        for k, v in sorted_url_param:
            can_string += '&' + percent_encode(k) + '=' + percent_encode(v)
        string_to_sign = 'GET' + '&' + '%2F' + '&' + percent_encode(can_string[1:])
        return string_to_sign

    @staticmethod
    def access_url(url):
        req = request.Request(url)
        with request.urlopen(req) as f:
            result = f.read().decode('utf-8')
            print(result)
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
        h = hmac.new(hash_bytes.encode('utf-8'), string_to_sign.encode('utf-8'), digestmod='SHA1')
        signature = base64.encodestring(h.digest()).strip()
        url_param.setdefault('Signature', signature)
        url = 'https://alidns.aliyuncs.com/?' + urllib.parse.urlencode(url_param)
        print(url)
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
    # domain = AliDns(ACCESS_KEY_ID, ACCESS_KEY_SECRET, 'wuqianlin.cn')
    # domain.describe_domain_records()

    # 增加记录
    # domain.add_domain_record("TXT", "test", "test")

    # 修改解析
    # domain.update_domain_record('4011918010876928', 'TXT', 'test', 'text2')

    # 删除解析记录
    # data = domain.describe_domain_records()
    # record_list = data["DomainRecords"]["Record"]
    # for item in record_list:
    #     if 'test' in item['RR']:
    #        domain.delete_domain_record(item['RecordId'])

    print(argv)
    file_name, certbot_domain, acme_challenge, certbot_validation = argv

    domain = AliDns(ACCESS_KEY_ID, ACCESS_KEY_SECRET, certbot_domain)
    data = domain.describe_domain_records()
    record_list = data["DomainRecords"]["Record"]
    if record_list:
        for item in record_list:
            if acme_challenge == item['RR']:
                domain.delete_domain_record(item['RecordId'])

    domain.add_domain_record("TXT", acme_challenge, certbot_validation)
