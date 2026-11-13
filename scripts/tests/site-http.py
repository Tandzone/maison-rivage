#!/usr/bin/env python3
"""Read-only local public-site crawl. No browser or JavaScript execution."""
from collections import deque
from html.parser import HTMLParser
from urllib.parse import urljoin, urlsplit, urldefrag
from urllib.request import urlopen
from urllib.error import HTTPError

BASE = 'http://localhost:8080/'
class Document(HTMLParser):
    def __init__(self):
        super().__init__(); self.links=[]; self.images=[]; self.h1=0; self.mains=0; self.missing_alt=0
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=='h1': self.h1+=1
        if tag=='main': self.mains+=1
        if tag=='a' and a.get('href'): self.links.append(a['href'])
        if tag=='img':
            if 'alt' not in a: self.missing_alt+=1
            if a.get('src'): self.images.append(a['src'])
            if a.get('srcset'):self.images.extend(item.strip().split()[0] for item in a['srcset'].split(','))

def read(url):
    with urlopen(url,timeout=20) as r: return r.status,r.headers,r.read()
queue=deque([BASE]); seen=set(); assets=set(); failures=[]
while queue:
    url=queue.popleft()
    if url in seen: continue
    seen.add(url)
    try:
        status,headers,body=read(url)
        assert status==200
        assert 'text/html' in headers.get('Content-Type','')
        doc=Document();doc.feed(body.decode())
        assert doc.h1==1,(url,'h1',doc.h1)
        assert doc.mains==1,(url,'main',doc.mains)
        assert doc.missing_alt==0,(url,'missing image alt')
        assert 'Fatal error' not in body.decode() and 'Warning:' not in body.decode()
        assets.update(urljoin(url,x) for x in doc.images)
        for link in doc.links:
            candidate=urldefrag(urljoin(url,link))[0]; part=urlsplit(candidate)
            if part.netloc!='localhost:8080' or part.query or part.path.startswith(('/wp-admin','/wp-login','/wp-json')):continue
            if candidate not in seen:queue.append(candidate)
    except Exception as e: failures.append((url,str(e)))
for url in assets:
    if urlsplit(url).netloc!='localhost:8080':continue
    try:
        status,headers,body=read(url);assert status==200 and body and headers.get('Content-Type','').startswith('image/')
    except Exception as e:failures.append((url,str(e)))
try:read(BASE+'a-deliberately-missing-page-for-qa/')
except HTTPError as e:assert e.code==404
else:failures.append(('404','Missing page returned success'))
if failures:raise SystemExit(str(failures))
print(f'Public HTTP crawl passed: {len(seen)} pages, {len(assets)} image URLs, one H1/main per page, image alt attributes, no visible PHP errors and genuine 404.')
