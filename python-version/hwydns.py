#!/usr/bin/env python
# -*- coding: utf-8 -*-

import os
import sys
import time
import urllib
import hashlib
import hmac
import binascii
import json

if sys.version_info < (3, 0):
    import urllib2
    import urllib
    import urlparse
else:
    import urllib.request as urllib2
    import urllib.parse as urllib

class HwyDns:
    __endpoint = 'dns.myhuaweicloud.com'

    def __init__(self, access_key_id, secret_access_key):
        self.access_key_id = access_key_id
        self.secret_access_key = secret_access_key

    @staticmethod
    def getDomain(domain):
        domain_parts = domain.split('.')

        if len(domain_parts) > 2:
            dirpath = os.path.dirname(os.path.dirname(os.path.realpath(__file__)))
            domainfile = dirpath + '/domain.ini'
            domainarr = []
            with open(domainfile) as f:
                for line in f:
                    val = line.strip()
                    domainarr.append(val)

            index = -3 if '.'.join(domain_parts[-2:]).lower() in domainarr else -2
            return ('.'.join(domain_parts[:index]), '.'.join(domain_parts[index:]))
        return ('', domain)

    # @example hwydns.add_domain_record("example.com", "_acme-challenge", "123456", "TXT")
    def add_domain_record(self, domain, rr, value, _type = 'TXT'):
        zone_id = self.get_domain_zone_id(domain)
        recordset_id = self.get_domain_recordset_id(domain, rr, _type)

        if not zone_id:
            return
        if not recordset_id:
            self.__request('POST', '/v2.1/zones/%s/recordsets' % (zone_id), {
                'name'      : '%s.%s.' % (rr, domain),
                'type'      : _type,
                'records'   : [ "\"%s\"" % (value) ],
                'ttl'       : '1'
            })
        else:
            response=self.__request('GET','/v2.1/zones/%s/recordsets/%s' % (zone_id,recordset_id))
            content = json.loads(response)
            record_list=content['records']
            record_list.append("\""+value+"\"")
            self.__request('PUT', '/v2.1/zones/%s/recordsets/%s' % (zone_id,recordset_id), {
                'records'   : record_list
            })

    # @example hwydns.delete_domain_record("example.com", "_acme-challenge", "TXT")
    def delete_domain_record(self, domain, rr, value, _type = 'TXT'):
        zone_id = self.get_domain_zone_id(domain)
        recordset_id = self.get_domain_recordset_id(domain, rr, _type)

        if not (zone_id and recordset_id):
            return
        
        response=self.__request('GET','/v2.1/zones/%s/recordsets/%s' % (zone_id,recordset_id))
        content = json.loads(response)
        record_list=content['records']
        if len(record_list)==1:
            self.__request('DELETE', '/v2.1/zones/%s/recordsets/%s' % (zone_id, recordset_id))
        else:
            record_list.remove("\""+value+"\"")
            self.__request('PUT', '/v2.1/zones/%s/recordsets/%s' % (zone_id,recordset_id), {
                'records'   : record_list
            })

    # @example hwydns.get_domain_record("example.com", "_acme-challenge", "TXT")
    def get_domain_record(self, domain, rr, _type = 'TXT'):
        try:
            full_domain = '.'.join([rr, domain])
            response = self.__request('GET', '/v2.1/recordsets?type=%s&name=%s' % (_type, full_domain))
            content = json.loads(response)
            return list(filter(lambda record: record['name'][:-1] == full_domain and record['type'] == _type, content['recordsets']))[0]
        except Exception as e:
            print('hwydns#get_domain_record raise: ' + str(e))
            return None

    # @example hwydns.get_domain("example.com")
    def get_domain(self, domain):
        try:
            response = self.__request('GET', '/v2/zones?type=public&name=%s' % (domain))
            content = json.loads(response)
            return list(filter(lambda item: item['name'][:-1] == domain, content['zones']))[0]
        except Exception as e:
            print('hwydns#get_domain raise: ' + str(e))
            return None

    def get_domain_recordset_id(self, domain, rr, _type = 'TXT'):
        try:
            record = self.get_domain_record(domain, rr, _type)
            return record['id'] if record else None
        except Exception as e:
            print('hwydns#get_domain_recordset_id raise: ' + str(e))
            return None

    def get_domain_zone_id(self, domain):
        try:
            record = self.get_domain(domain)
            return record['id'] if record else None
        except Exception as e:
            print('hwydns#get_domain_zone_id raise: ' + str(e))
            return None

    def __request(self, method, path, payload={}):
        url = 'https://%s%s?%s' % (self.__endpoint, self.__parse_path(path)[:-1], self.__parse_query_string(path))
        data = json.dumps(payload).encode('utf8')
        sdk_date = self.__build_sdk_date()

        print('Request URL: ' + url)
        print('Request Data: ' + str(data))

        request = urllib2.Request(url=url, data=data)
        request.get_method = lambda: method
        request.add_header('Content-Type', 'application/json')
        request.add_header('Host', self.__endpoint)
        request.add_header('X-sdk-date', sdk_date)
        request.add_header('Authorization', self.__build_authorization(request))
        print('Request headers: ' + str(request.headers))

        try:
            f = urllib2.urlopen(request, timeout=45)
            response = f.read().decode('utf-8')
            print(response)
            return response
        except urllib2.HTTPError as e:
            print('hwydns#__request raise urllib2.HTTPError: ' + str(e))
            raise SystemExit(e)

    def __build_sdk_date(self):
        return time.strftime("%Y%m%dT%H%M%SZ", time.gmtime())

    def __build_authorization(self, request):
        algorithm = 'SDK-HMAC-SHA256'
        canonical_request = self.__build_canonical_request(request)
        canonical_request_hexencode = self.__hexencode_sha256_hash(canonical_request.encode('utf-8'))
        string2sign = "%s\n%s\n%s" % (algorithm, request.get_header('X-sdk-date'), canonical_request_hexencode)
        sign = self.__build_sign(string2sign)

        return "%s Access=%s, SignedHeaders=%s, Signature=%s" % (algorithm, self.access_key_id, self.__parse_header_keys(request.headers), sign)

    def __build_canonical_request(self, request):
        return "%(method)s\n%(path)s\n%(query_string)s\n%(headers)s\n%(header_keys)s\n%(data_hexencode)s" % {
            'method': request.get_method().upper(),
            'path': self.__parse_path(request.get_full_url()),
            'query_string': self.__parse_query_string(request.get_full_url()),
            'headers': self.__parse_headers(request.headers),
            'header_keys': self.__parse_header_keys(request.headers),
            'data_hexencode': self.__hexencode_sha256_hash(request.data)
        }

    def __parse_path(self, url):
        if sys.version_info < (3,0):
            path = urlparse.urlsplit(url).path
        else:
            path = urllib.urlsplit(url).path

        path = path if path else '/'
        pattens = urllib.unquote(path).split('/')

        tmp_paths = []
        for v in pattens:
            tmp_paths.append(self.__urlencode(v))
        urlpath = '/'.join(tmp_paths)
        if urlpath[-1] != '/':
            urlpath = urlpath + '/'
        return urlpath

    def __parse_query_string(self, url):
        if sys.version_info < (3,0):
            query = urlparse.parse_qs(urlparse.urlsplit(url).query)
        else:
            query = urllib.parse_qs(urllib.urlsplit(url).query)

        sorted_query = sorted(query.items(), key=lambda item: item[0])
        sorted_query_string = ''
        for (k, v) in sorted_query:
            if type(v) is list:
                v.sort()
                for item in v:
                    sorted_query_string += '&' +  self.__urlencode(k) + '=' + self.__urlencode(item)
            else:
                sorted_query_string += '&' +  self.__urlencode(k) + '=' + self.__urlencode(v)

        return sorted_query_string[1:]

    def __parse_headers(self, headers):
        format_headers = dict(((k.lower(), v.strip())) for (k, v) in headers.items())

        header_string = ''
        for (k, v) in sorted(format_headers.items(), key=lambda item: item[0]):
            header_string += "%s:%s\n" % (k, v)
        return header_string

    def __parse_header_keys(self, headers):
        return ';'.join(sorted(map(lambda key: key.lower(), headers.keys())))

    def __build_sign(self, string2sign):
        if sys.version_info < (3,0):
            hm = hmac.new(self.secret_access_key, string2sign, digestmod=hashlib.sha256).digest()
        else:
            hm = hmac.new(self.secret_access_key.encode('utf-8'), string2sign.encode('utf-8'), digestmod=hashlib.sha256).digest()
        return binascii.hexlify(hm).decode()

    def __urlencode(self, string):
        return urllib.quote(str(string), safe='~')

    def __hexencode_sha256_hash(self, data):
        sha256 = hashlib.sha256()
        sha256.update(data)
        return sha256.hexdigest()

if __name__ == '__main__':
    print('开始调用华为云 DNS API')
    print('-'.join(sys.argv))

    _, action, certbot_domain, acme_challenge, certbot_validation, api_key, api_secret = sys.argv

    subdomain, main_domain = HwyDns.getDomain(certbot_domain)
    if subdomain:
        subdomain = acme_challenge + '.' + subdomain
    else:
        subdomain = acme_challenge

    hwydns = HwyDns(api_key, api_secret)

    if 'add' == action:
        hwydns.add_domain_record(main_domain, subdomain, certbot_validation)
    elif 'clean' == action:
        hwydns.delete_domain_record(main_domain, subdomain, certbot_validation)

    print('结束调用华为云 DNS API')
