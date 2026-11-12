#!/usr/bin/env python3
"""Run two genuinely concurrent confirmation processes against the same physical room."""
import concurrent.futures
import json
from pathlib import Path
import subprocess
import time

ROOT = Path(__file__).resolve().parents[2]

def wp(code):
    result = subprocess.run(['docker', 'compose', 'run', '--rm', 'cli', 'eval', code], cwd=ROOT, capture_output=True, text=True)
    if result.returncode:
        raise RuntimeError(result.stderr + result.stdout)
    return json.loads(result.stdout)

ids = []
try:
    fixtures = wp(r'''require '/workspace/scripts/tests/booking-helpers.php'; $type=get_page_by_path('rooms/rivage-suite')->ID;$room=\MaisonRivage\active_rooms($type)[0]->ID;$ids=array();for($i=0;$i<2;$i++){$r=\MaisonRivage\booking_create(rivage_test_input($type,180),\MaisonRivage\booking_form_key());if(is_wp_error($r))WP_CLI::error($r->get_error_message());$ids[]=$r->id;$v=\MaisonRivage\booking_verify(rivage_test_token($r->id));if(is_wp_error($v))WP_CLI::error($v->get_error_message());}echo wp_json_encode(array('ids'=>$ids,'room'=>$room));''')
    ids = fixtures['ids']
    room = fixtures['room']
    # Hold the actual room lock long enough for the other process to reach it.
    def confirm(booking_id):
        return wp(r'''wp_set_current_user(get_users(array('role'=>'administrator','number'=>1))[0]->ID);add_action('rivage_booking_room_locked',static function(){usleep(1500000);});$r=\MaisonRivage\booking_transition(''' + str(int(booking_id)) + ", 'confirm', " + str(int(room)) + r''');echo wp_json_encode(is_wp_error($r)?array('error'=>$r->get_error_code()):array('status'=>$r->status));''')
    started = time.monotonic()
    with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
        results = list(pool.map(confirm, ids))
    assert sum(r.get('status') == 'confirmed' for r in results) == 1, results
    assert sum(r.get('error') == 'conflict' for r in results) == 1, results
    joined = ','.join(str(int(i)) for i in ids)
    counts = wp(r'''global $wpdb;echo wp_json_encode(array('occupancy'=>(int)$wpdb->get_var('SELECT COUNT(*) FROM '.\MaisonRivage\booking_table('occupancy').' WHERE booking_id IN (''' + joined + r''')'),'confirmed_mail'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM ".\MaisonRivage\booking_table('mail')." WHERE booking_id IN (''' + joined + r''') AND event_key LIKE '%:confirmed:guest'")));''')
    assert counts == {'occupancy': 1, 'confirmed_mail': 1}, counts
    print('Concurrent confirmation check passed: one confirmed, one conflict, one occupancy and one success email. Independent workers: %.1fs.' % (time.monotonic() - started))
finally:
    if ids:
        joined = ','.join(str(int(i)) for i in ids)
        wp("require '/workspace/scripts/tests/booking-helpers.php'; rivage_test_cleanup(array(" + joined + ")); echo json_encode(true);")
