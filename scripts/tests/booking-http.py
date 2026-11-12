#!/usr/bin/env python3
"""Anonymous HTTP checks; no request is created and no email is sent."""
from html.parser import HTMLParser
import urllib.error
import urllib.parse
import urllib.request

from datetime import datetime, timedelta
from zoneinfo import ZoneInfo

ROOT = 'http://localhost:8080'
arrival = datetime.now(ZoneInfo('Indian/Antananarivo')).date() + timedelta(days=40)
departure = arrival + timedelta(days=2)

def fetch(path, data=None):
    body = urllib.parse.urlencode(data).encode() if data is not None else None
    with urllib.request.urlopen(ROOT + path, body) as response:
        return response.read().decode(), response.headers

class Fields(HTMLParser):
    def __init__(self):
        super().__init__()
        self.fields = {}
    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'input' and attrs.get('name'):
            self.fields[attrs['name']] = attrs.get('value', '')

page, headers = fetch('/booking/')
assert 'no-store' in headers['Cache-Control']
assert headers['Referrer-Policy'] == 'no-referrer'
assert 'noindex' in headers['X-Robots-Tag']
assert 'Check Availability' in page
page, _ = fetch('/booking/', {'rivage_booking_action': 'request', '_wpnonce': 'forged'})
assert 'This form expired' in page
page, _ = fetch('/booking/?check_in=2026-02-30&check_out=2026-03-02&adults=2&children=0')
assert 'Choose valid arrival and departure dates.' in page
page, _ = fetch(f'/booking/?check_in={arrival}&check_out={departure}&adults=12&children=12')
assert 'No rooms match these dates and guests.' in page
page, _ = fetch('/booking/verify/?token=' + 'a'*64)
fields = Fields(); fields.feed(page)
# Use the verification form's first nonce; the resend form has another one.
import re
nonce = re.search(r'name="_wpnonce" value="([^"]+)"', page).group(1)
page, _ = fetch('/booking/verify/', {'rivage_booking_action': 'verify', '_wpnonce': nonce, 'token': 'a'*64})
assert 'expired, is invalid, or has already been used' in page
for path in ['/wp-admin/admin.php?page=rivage-bookings', '/wp-admin/admin.php?page=rivage-notifications']:
    page, _ = fetch(path)
    assert 'loginform' in page and 'Demo Guest' not in page
print('Anonymous HTTP checks passed: cache/referrer headers, forged nonce, invalid dates/capacity, invalid token and private administration.')
