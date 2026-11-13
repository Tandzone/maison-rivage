#!/usr/bin/env python3
"""Local contact form and SMTP test, using fictional data only."""
from urllib.request import urlopen,Request
from urllib.parse import urlencode
from html.parser import HTMLParser
from uuid import uuid4
import json
class Form(HTMLParser):
    def __init__(self):super().__init__();self.fields={}
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=='input' and a.get('type')=='hidden' and a.get('name'):self.fields[a['name']]=a.get('value','')
base='http://localhost:8080/contact/'
with urlopen(base) as r:
    assert 'no-cache' in r.headers['Cache-Control'];assert r.headers['Referrer-Policy']=='no-referrer';html=r.read().decode()
f=Form();f.feed(html)
assert f.fields.get('request_key') and f.fields.get('_wpnonce')
marker='Local contact QA '+uuid4().hex
values={**f.fields,'guest_name':'Portfolio Contact QA','email':'portfolio-contact-qa@example.test','message':marker,'demo_ack':'1'}
with urlopen(Request(base,data=urlencode({**values,'_wpnonce':'forged'}).encode())) as r:assert 'This form expired' in r.read().decode()
for _ in range(2):
    with urlopen(Request(base,data=urlencode(values).encode())) as r:
        assert 'receipt=' in r.url;assert 'Message queued.' in r.read().decode()
with urlopen('http://localhost:8025/api/v1/messages') as r:messages=json.load(r)['messages']
found=[]
for m in messages:
    if m['Subject']=='Maison Rivage — contact demonstration':
        with urlopen('http://localhost:8025/api/v1/message/'+m['ID']) as r:detail=json.load(r)
        if marker in detail.get('Text',''):found.append(m['ID'])
assert len(found)==1,len(found)
print('Contact HTTP/SMTP check passed: nonce rejection, uncached form, opaque receipt, duplicate retry and one Mailpit message. Test copies remain in Mailpit until cleared/reset.')
