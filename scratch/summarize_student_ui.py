import json, os

path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'student_ui_verification.json')
data = json.load(open(path, encoding='utf-8'))
print('LOGINS', data['logins'])
for vp, pages in data['measurements'].items():
    print('\nVIEWPORT', vp)
    for page, m in pages.items():
        print(' ', page,
              'overflow=', m.get('horizontalOverflow'),
              'scrollW=', m.get('scrollWidth'),
              'clientW=', m.get('clientWidth'),
              'bad=', len(m.get('overflowingElements', [])))
        if m.get('overflowingElements'):
            for b in m['overflowingElements'][:3]:
                print('     BAD', b)
        d = m.get('dashboard')
        if d:
            print('     metricH=', d['cardRects'], 'values=', d['values'], 'fonts=', d['valueFontSizes'])
            print('     icons=', d['icons'], 'rowH=', d['rowHeights'], 'actions=', d['actionButtons'])
            print('     headerFont=', d['headerFont'])
        a = m.get('applications')
        if a:
            print('     card=', a['cardStyle'], 'header=', a['headerFont'])
            print('     rowH=', a['rowHeights'], 'badges=', a['badges'], 'actions=', a['actions'])
        if m.get('applicationsEmpty'):
            print('     empty=', m['applicationsEmpty'])
        s = m.get('sle')
        if s:
            print('     sle disabled=', s['disabledCount'], s['disabledStyle'], 'icons=', s['headerIcons'])
            print('     lock=', s['lockNotice'], 'campus=', s['campus'])
        det = m.get('detail')
        if det:
            print('     steps=', det['steps'])
            print('     docRows=', det['docRows'])
            print('     filenames=', det['filenames'])
            print('     viewLinks=', det['viewLinks'])
            print('     back=', det['backButton'], 'rurality=', det['rurality'])
            print('     explainer=', det['explainer'])
