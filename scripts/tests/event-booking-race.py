#!/usr/bin/env python3
"""Local disposable fixtures: a booking and an event compete for one suite."""
import concurrent.futures
import json
from pathlib import Path
import subprocess

ROOT = Path(__file__).resolve().parents[2]

def wp(code):
    result = subprocess.run(['docker', 'compose', 'run', '--rm', 'cli', 'eval', code], cwd=ROOT, capture_output=True, text=True)
    if result.returncode:
        raise RuntimeError(result.stderr + result.stdout)
    return json.loads(result.stdout)

fixture = None
try:
    fixture = wp(r'''require '/workspace/scripts/tests/booking-helpers.php';wp_set_current_user(get_users(array('role'=>'administrator','number'=>1))[0]->ID);$type=get_page_by_path('rooms/rivage-suite')->ID;$input=rivage_test_input($type,230);$b=\MaisonRivage\booking_create($input,\MaisonRivage\booking_form_key());if(is_wp_error($b))WP_CLI::error($b->get_error_message());\MaisonRivage\booking_verify(rivage_test_token($b->id));$event=wp_insert_post(array('post_type'=>'rivage_event','post_status'=>'draft','post_title'=>'Concurrent event fixture'));echo wp_json_encode(array('booking'=>$b->id,'event'=>$event,'room'=>\MaisonRivage\active_rooms($type)[0]->ID,'day'=>$input['check_in']));''')
    booking = int(fixture['booking'])
    event = int(fixture['event'])
    room = int(fixture['room'])
    day = fixture['day']
    common = r'''wp_set_current_user(get_users(array('role'=>'administrator','number'=>1))[0]->ID);add_action('rivage_booking_room_locked',static function(){usleep(1500000);});add_action('rivage_event_room_locked',static function(){usleep(1500000);});'''
    finish = r'''echo wp_json_encode(is_wp_error($r)?array('error'=>$r->get_error_code()):array('status'=>$r->status));'''
    calls = [common + f"$r=\\MaisonRivage\\booking_transition({booking},'confirm',{room});" + finish,
             common + f"$r=\\MaisonRivage\\event_change({event},'schedule',array('start'=>'{day}T18:00','end'=>'{day}T20:00','venue'=>'suite','room_id'=>{room}),0);" + finish]
    with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
        results = list(pool.map(wp, calls))
    assert sum(r.get('status') in ('confirmed', 'scheduled') for r in results) == 1, results
    assert sum(r.get('error') == 'conflict' for r in results) == 1, results
    count = wp(r'''global $wpdb;echo wp_json_encode((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ".\MaisonRivage\booking_table("occupancy")." WHERE booking_id=%d OR (source_type='event' AND source_id=%d)",''' + str(booking) + "," + str(event) + ")));")
    assert count == 1, count
    print('Event/booking race passed: one success, one conflict, one occupancy across independent workers.')
finally:
    if fixture:
        wp(r'''require '/workspace/scripts/tests/booking-helpers.php';wp_delete_post(''' + str(int(fixture['event'])) + r''',true);global $wpdb;$wpdb->delete(\MaisonRivage\booking_table('events'),array('event_id'=>''' + str(int(fixture['event'])) + r'''));rivage_test_cleanup(array(''' + str(int(fixture['booking'])) + '));echo json_encode(true);')
