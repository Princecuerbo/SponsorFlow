"""Build documentation artifacts only; never load Laravel or access a database."""
from pathlib import Path
from html import escape
import hashlib
import json
import math
import re
from xml.etree import ElementTree as ET
from PIL import Image, ImageDraw, ImageFont

OUT = Path(__file__).resolve().parent
ROOT = OUT.parent.parent
W, H, SCALE = 2800, 2240, 2
INK, MUTED, LINK = '#203449', '#536476', '#a2afbd'
SHARED = '#aa5711'
FONTS = {False: 'C:/Windows/Fonts/arial.ttf', True: 'C:/Windows/Fonts/arialbd.ttf'}
im = Image.new('RGB', (W*SCALE, H*SCALE), 'white')
draw = ImageDraw.Draw(im)
svg = [f'<svg xmlns="http://www.w3.org/2000/svg" width="{W}" height="{H}" viewBox="0 0 {W} {H}" role="img" aria-labelledby="title desc">',
       '<title id="title">SponsorFlow UX Sitemap</title>',
       '<desc id="desc">Public and designated login pages lead to five role-specific navigation groups. Rectangles are pages or grouped menus; smaller lines describe actions within pages. No database or workflow changes are made.</desc>',
       '<rect width="100%" height="100%" fill="white"/>']
fonts, text_records, nodes, connections = {}, [], [], []

def font(size, bold=False):
    key = size, bold
    if key not in fonts:
        fonts[key] = ImageFont.truetype(FONTS[bold], round(size*SCALE))
    return fonts[key]

def txt(x, y, label, size=23, bold=False, color=INK, center=False, maxw=None):
    width = draw.textlength(label, font=font(size, bold))/SCALE
    if maxw is not None:
        assert width <= maxw, (label, width, maxw)
    left = x-width/2 if center else x
    assert 0 <= left and left+width <= W and 0 <= y < H, label
    draw.text((round(left*SCALE), round(y*SCALE)), label, font=font(size,bold), fill=color, anchor='lt')
    svg.append(f'<text x="{x}" y="{y}" font-family="Arial, sans-serif" font-size="{size}" font-weight="{700 if bold else 400}" text-anchor="{"middle" if center else "start"}" dominant-baseline="text-before-edge" fill="{color}">{escape(label)}</text>')
    text_records.append(label)

def rect(x,y,w,h,fill='white',stroke=LINK,r=9,width=1.5):
    box=[round(v*SCALE) for v in (x,y,x+w,y+h)]
    draw.rounded_rectangle(box, radius=round(r*SCALE), fill=fill, outline=stroke, width=max(1,round(width*SCALE)))
    svg.append(f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="{r}" fill="{fill}" stroke="{stroke}" stroke-width="{width}"/>')

def line(points, color=LINK, width=2, dashed=False, arrow=False):
    svg.append('<polyline points="'+' '.join(f'{x},{y}' for x,y in points)+f'" fill="none" stroke="{color}" stroke-width="{width}"'+(' stroke-dasharray="7 6"' if dashed else '')+'/>')
    for (x1,y1),(x2,y2) in zip(points,points[1:]):
        length=math.hypot(x2-x1,y2-y1)
        if not length: continue
        if dashed:
            pos=0
            while pos<length:
                stop=min(length,pos+7)
                draw.line([(round((x1+(x2-x1)*pos/length)*SCALE),round((y1+(y2-y1)*pos/length)*SCALE)),(round((x1+(x2-x1)*stop/length)*SCALE),round((y1+(y2-y1)*stop/length)*SCALE))], fill=color,width=round(width*SCALE))
                pos+=13
        else:
            draw.line([(round(x1*SCALE),round(y1*SCALE)),(round(x2*SCALE),round(y2*SCALE))],fill=color,width=round(width*SCALE))
    if arrow:
        (px,py),(x,y)=points[-2:]
        a=math.atan2(y-py,x-px)
        p=[(x,y),(x-10*math.cos(a)+5*math.sin(a),y-10*math.sin(a)-5*math.cos(a)),(x-10*math.cos(a)-5*math.sin(a),y-10*math.sin(a)+5*math.cos(a))]
        draw.polygon([(round(xx*SCALE),round(yy*SCALE)) for xx,yy in p],fill=color)
        svg.append('<polygon points="'+' '.join(f'{xx},{yy}' for xx,yy in p)+f'" fill="{color}"/>')

def box(id,x,y,w,labels,color=INK,fill='white',subs=None,route=None,kind='page',size=24):
    labels=[labels] if isinstance(labels,str) else labels
    subs=subs or []
    h=24+len(labels)*29+len(subs)*25+(5 if subs else 0)
    rect(x,y,w,h,fill,color)
    offset=12
    for label in labels:
        txt(x+w/2,y+offset,label,size,True,color,True,w-20)
        offset+=29
    if subs: offset+=5
    for sub in subs:
        txt(x+w/2,y+offset,sub,21,False,MUTED,True,w-16)
        offset+=25
    node=dict(id=id,x=x,y=y,w=w,h=h,label=' / '.join(labels),subtext=subs,route=route,kind=kind)
    nodes.append(node)
    return node

roles=[
 dict(id='student',title='STUDENT APPLICANT',color='#245fae',fill='#eef5ff',note='Login opens SLE-FHE Verification',items=[
  ('home','Home / Dashboard','student.dashboard',[],[]),
  ('verification','SLE-FHE Verification','student.sle-fhe',['Profile / address and request status','Request verification'],[]),
  ('programs','Sponsorship Opportunities','student.programs.index',['Search / filter programs'],[
   ('apply','Application Form','student.applications.create',['Required documents / submit'],[])]),
  ('applications','My Applications','student.applications.index',[],[
   ('application','Application Details','student.applications.show',['Status / documents / resubmission'],[])]),
  ('notifications','Notifications','notifications.index',['Read / mark as read'],[]),
  ('profile','My Profile','student.profile',['Personal details / change password'],[]),
 ]),
 dict(id='fassg',title='FASSG PERSONNEL',color='#18734b',fill='#eff9f3',note='Dashboard and authorized menus',items=[
  ('home','Dashboard','fassg.dashboard',[],[]),
  ('programs','Programs','fassg.programs.index',[],[
   ('program-edit','Create / Edit Program','fassg.programs.create',['Criteria / capacity / requirements'],[])]),
  ('verification','SLE-FHE Verification','fassg.sle-fhe.index',['Student details / decision dialogs'],[]),
  ('verified','Verified Students','fassg.sle-fhe.verified',[],[]),
  ('queue','Application Queue','fassg.applications.index',['Manual selection / Create Batch dialog'],[
   ('applicant','Applicant Details','fassg.applications.show',[],[])]),
  ('fixed-lists','Fixed Lists','fassg.fixed-lists.index',['New Fixed List dialog'],[
   ('fixed-list',['List Details / Edit Draft'],'fassg.fixed-lists.show',['Encode / import / endorse / finalize','Create Batch shortcut'],[])]),
  ('batches','Generated Batches','fassg.generated-batches.index',[],[
   ('batch','Batch Details','fassg.generated-batches.show',['Submit / record Sponsor decision receipt','Explicitly forward approved batch'],[
    ('member','Batch Member Review','fassg.generated-batches.applications.show',['Qualifications / documents / verify'],[])])]),
  ('reports','Reports','fassg.reports.index',['Filters / PDF and CSV exports'],[]),
 ]),
 dict(id='sponsor',title='SPONSOR',color='#7545a0',fill='#f6f0fc',note='Dashboard and authorized menus',items=[
  ('home','Dashboard','sponsor.dashboard',[],[]),
  ('queue','Approvals Queue','sponsor.approvals.index',['Assigned-program filter'],[
   ('batch','Submitted Batch Details','sponsor.lists.show',['Exact roster / confirmation upload','Approve or reject the whole batch'],[])]),
  ('history','Approval History','sponsor.approvals.history',['Batch decisions / historical evidence'],[
   ('document','Confirmation Document','sponsor.approvals.download',['View / download original file'],[])]),
 ]),
 dict(id='accounting',title='ACCOUNTING OFFICE',color='#087d85',fill='#edf9fa',note='Read-only beneficiary reference',items=[
  ('home','Dashboard','accounting.dashboard',[],[]),
  ('beneficiaries','Confirmed Beneficiaries','accounting.beneficiaries.index',['Eligible records / filters / Print / CSV'],[
   ('beneficiary','Beneficiary Details','accounting.beneficiaries.show',[],[]),
   ('batch','Approved Batch Reference','accounting.beneficiaries.reference',[],[]),
   ('documents','Confirmation Documents','accounting.documents.view',['Authorized document viewing'],[])]),
 ]),
 dict(id='admin',title='SYSTEM ADMINISTRATOR',color='#ad4844',fill='#fff2f0',note='Dashboard and authorized menus',items=[
  ('home','Dashboard','admin.dashboard',[],[]),
  ('users','User Accounts','admin.users.index',['Add / edit dialogs / roles / status'],[]),
  ('logs','Audit Logs','admin.audit-logs.index',['Search / filter recorded activity'],[]),
  ('settings','Security Settings','admin.settings.index',['Maintenance / security controls'],[]),
  ('backups','Backups','admin.backups.index',['Create / download / authorized restore'],[]),
 ])
]

# Top-level hierarchy. "Portal" and role headers are grouping nodes, not new screens.
txt(60,35,'SPONSORFLOW — UX SITEMAP',44,True)
txt(60,94,'Public entry pages, role-specific navigation, and key screens',25,color=MUTED)
root=box('portal',1190,145,420,'SponsorFlow Web Portal',SHARED,'#fff4e5',kind='group',size=27)
line([(1400,root['y']+root['h']),(1400,238),(300,238),(300,270)])
line([(1400,238),(1400,270)])
line([(1400,238),(2500,238),(2500,270)])
home=box('landing',60,270,480,'Public Home / Landing Page',SHARED,'#fff4e5',route='landing')
staff=box('staff-login',1160,270,480,'Staff / Sponsor Login',SHARED,'#fff4e5',subs=['FASSG / Sponsor / Accounting'],route='staff.login')
admin=box('admin-login',2260,270,480,'Administrator Login',SHARED,'#fff4e5',route='admin.login')
line([(300,home['y']+home['h']),(300,348),(177,348),(177,370)])
line([(300,348),(427,348),(427,370)])
reg=box('registration',60,370,235,['Student','Registration'],SHARED,'#fff4e5',route='register',size=23)
login=box('student-login',310,370,235,['Student Login','and consent dialog'],SHARED,'#fff4e5',route='login',size=22)
txt(300,468,'Registration returns to Student Login.',20,color=MUTED,center=True)
line([(427,login['y']+login['h']),(427,505),(300,505),(300,550)])
line([(1400,staff['y']+staff['h']),(1400,505),(850,505),(850,550)])
line([(1400,505),(1400,550)])
line([(1400,505),(1950,505),(1950,550)])
line([(2500,admin['y']+admin['h']),(2500,550)])
txt(1400,465,'Successful login grants only the assigned role’s navigation.',22,color=MUTED,center=True)
connections += [('portal','landing'),('portal','staff-login'),('portal','admin-login'),('landing','registration'),('landing','student-login')]

def children(items, role, x, y, parent_id=None, depth=0):
    start=y
    children_built=[]
    for key,label,route,subs,kids in items:
        nx=x+42+depth*25
        width=458-depth*25
        node=box(role['id']+'-'+key,nx,y,width,label,role['color'],role['fill'] if depth==0 else 'white',subs,route,size=23 if depth==0 else 22)
        if parent_id:
            connections.append((parent_id,node['id']))
        children_built.append(node)
        y+=node['h']+13
        if kids:
            child_start=y
            y,built=children(kids,role,x,y,node['id'],depth+1)
            spine=nx+12
            line([(spine,node['y']+node['h']),(spine,built[-1]['y']+built[-1]['h']/2)],role['color'],1.3)
            for child in built:
                cy=child['y']+child['h']/2
                line([(spine,cy),(child['x'],cy)],role['color'],1.3)
        y+=4
    return y,children_built

bottoms=[]
for i,role in enumerate(roles):
    x=50+i*550
    rect(x,550,500,83,role['color'],role['color'])
    txt(x+250,565,role['title'],26,True,'white',True,470)
    txt(x+250,603,role['note'],20,False,'white',True,470)
    nodes.append(dict(id=role['id']+'-navigation',label=role['title'],kind='group'))
    parent={'student':'student-login','fassg':'staff-login','sponsor':'staff-login','accounting':'staff-login','admin':'admin-login'}[role['id']]
    connections.append((parent,role['id']+'-navigation'))
    bottom,built=children(role['items'],role,x,669,role['id']+'-navigation')
    spine=x+18
    line([(x+250,633),(x+250,650),(spine,650),(spine,built[-1]['y']+built[-1]['h']/2)],role['color'],1.4)
    for node in built:
        cy=node['y']+node['h']/2
        line([(spine,cy),(node['x'],cy)],role['color'],1.4)
    bottoms.append(bottom)

assert max(bottoms)<1980, bottoms
# Useful reading notes in the naturally shorter columns.
notes=[
 (50,1420,500,'STUDENT ACCESS',['SLE-FHE verification is required','before authenticated program browsing','and application submission.']),
 (1150,1420,500,'SPONSOR DECISIONS',['Decisions apply to the exact submitted','Generated Batch. Confirmation upload','does not forward records to Accounting.']),
 (1700,1195,500,'ACCOUNTING VISIBILITY',['SLE-FHE verified + individually','Approved/Ongoing + Sponsor confirmed','+ FASSG receipt and explicit forwarding.']),
 (2250,1325,500,'ADMINISTRATION',['Administrative pages do not replace','FASSG verification or Sponsor decisions.','Access remains role-restricted.']),
]
for x,y,w,title,body in notes:
    rect(x,y,w,135,'#f7f9fb','#d6dee6',8)
    txt(x+18,y+16,title,20,True,MUTED,maxw=w-36)
    for j,t in enumerate(body): txt(x+18,y+48+j*25,t,21,color=MUTED,maxw=w-36)

rect(50,1992,2700,59,'#fff4e5',SHARED)
txt(1400,2010,'SHARED ACTION  ·  Sign Out is available to every authenticated role.',25,True,SHARED,True)
txt(60,2078,'Legend: Orange = shared entry pages/actions. Other colors identify the five role-specific navigation areas.',22,color=MUTED)
txt(60,2112,'Solid lines show page hierarchy. Smaller text describes forms, dialogs, or actions within that page—not additional screens.',22,color=MUTED)
txt(60,2146,'FASSG shortcuts from Programs or a finalized Fixed List open the same Create Batch dialog in the Application Queue.',22,color=MUTED)
txt(1400,2200,'Figure 3.X. UX Sitemap of SponsorFlow',27,True,center=True)

svg.append('</svg>')
svg_text='\n'.join(svg)
ET.fromstring(svg_text)

sources=[
 'routes/web.php','app/Enums/UserRole.php','resources/views/layouts/app.blade.php',
 'resources/views/partials/_navbar.blade.php','resources/views/welcome.blade.php',
 'app/Http/Controllers/Auth/StudentLoginController.php','app/Http/Controllers/Auth/StaffLoginController.php',
 'app/Http/Controllers/Auth/AdminLoginController.php','app/Http/Controllers/Auth/PortalLoginController.php',
 'resources/views/student/sle-verification.blade.php','resources/views/student/profile.blade.php',
 'resources/views/fassg/programs/index.blade.php','resources/views/fassg/applications/index.blade.php',
 'resources/views/fassg/fixed_lists/index.blade.php','resources/views/fassg/fixed_lists/show.blade.php',
 'resources/views/fassg/generated_batches/index.blade.php','resources/views/fassg/generated_batches/show.blade.php',
 'resources/views/sponsor/approvals/index.blade.php','resources/views/sponsor/approvals/history.blade.php',
 'resources/views/sponsor/lists/show.blade.php','resources/views/accounting/index.blade.php',
 'resources/views/accounting/show.blade.php','resources/views/accounting/beneficiaries/show.blade.php',
 'resources/views/admin/users/index.blade.php','resources/views/admin/settings/index.blade.php',
 'resources/views/admin/backups/index.blade.php',
]
hashes={s:hashlib.sha256((ROOT/s).read_bytes()).hexdigest() for s in sources}
route_source=(ROOT/'routes/web.php').read_text(encoding='utf-8-sig')
assert len({n['id'] for n in nodes})==len(nodes)
for parent,child in connections:
    assert parent in {n['id'] for n in nodes} and child in {n['id'] for n in nodes}
    assert parent!=child
checked=[]
for n in nodes:
    route=n.get('route')
    if not route: continue
    prefix=route.split('.')[0]
    if prefix in ['student','fassg','sponsor','accounting','admin'] and route not in ['staff.login','admin.login']:
        segment=route_source.split("->prefix('"+prefix+"')",1)[1]
        segment=re.split(r"Route::middleware\(\['auth', 'EnsureUserRole:",segment)[0]
        short=route[len(prefix)+1:]
    else:
        segment=route_source
        short=route
    assert f"->name('{short}')" in segment,(n['id'],route)
    checked.append(route)

png=OUT/'SponsorFlow_UX_Sitemap.png'
svg_path=OUT/'SponsorFlow_UX_Sitemap.svg'
pdf=OUT/'SponsorFlow_UX_Sitemap.pdf'
im.save(png,dpi=(300,300))
svg_path.write_text(svg_text,encoding='utf-8')
im.save(pdf,'PDF',resolution=340.0,title='SponsorFlow UX Sitemap',author='SponsorFlow Documentation')
(OUT/'Sitemap_Definition.json').write_text(json.dumps(dict(nodes=nodes,connections=connections,notes='Page hierarchy and key in-page actions. No data flow or automatic approval is implied.'),indent=2,ensure_ascii=False),encoding='utf-8')

narrative='''The UX Sitemap presents the organization of SponsorFlow’s user interface and the navigation available to its five user roles: Student Applicant, FASSG Personnel, Sponsor, Accounting Office Personnel, and System Administrator. The public landing page provides access to student registration and login, while staff, sponsors, and administrators use their designated login pages. After authentication, users access the pages permitted by their assigned role. Student login initially opens the SLE-FHE Verification page.

Students can request SLE-FHE verification, browse sponsorship opportunities after verification, submit applications and supporting documents, monitor application status, view notifications, and access their profiles. FASSG personnel manage programs, review verification requests, maintain Fixed Lists, manually prepare Generated Batches, review batch members, and submit eligible batches to Sponsors. Sponsor pages provide whole-batch review, confirmation-document upload, explicit approval or rejection, and decision history. FASSG separately records the Sponsor’s decision and explicitly forwards eligible approved beneficiaries to Accounting. Accounting personnel can view, print, and export only authorized beneficiary references. Administrators manage user accounts, audit logs, security settings, and backups.

The figure uses colored branches to distinguish role-specific navigation. Connected rectangles represent pages or navigation groupings, while smaller text identifies controls, forms, and dialogs within those pages. The hierarchy separates each role’s responsibilities and provides clear paths between lists, detail views, and authorized actions. Page visibility and action availability remain subject to authentication, permissions, verification, and record status.
'''
(OUT/'Chapter_III_UX_Sitemap_Narrative.md').write_text('# UX Sitemap\n\n'+narrative+'\n*Figure 3.X. UX Sitemap of SponsorFlow.*\n',encoding='utf-8')

evidence='''# UX sitemap evidence and scope

Prepared through source inspection only. No endpoints, database queries, migrations, seeders, or application tests were executed. No application source files were modified.

## Interpretation

- The top-level portal and five role headings are navigation groupings, not invented screens. Staff and administrator login pages are designated entry points; the figure does not claim the public landing page links to those protected entrances.
- Student Registration returns to Student Login. The current StudentLoginController and PortalLoginController open SLE-FHE Verification after authentication; this is not an automatic verification decision. The consent interaction is part of Student Login.
- The active layout includes partials._navbar. The old sidebar/UserRole menu definitions contain legacy links and are not the authoritative current top navigation.
- Sponsor Approvals Queue filters assigned programs and opens submitted Generated Batch details. A standalone Sponsor Programs or individual-approval workflow is not invented from legacy routes.
- Beneficiary details, approved batch references, and protected document viewers are children of Accounting’s Confirmed Beneficiaries area. Print and CSV export are controls, not separate management modules.
- FASSG Create Batch is a modal in the Application Queue. Program and finalized Fixed List shortcuts reach this same interface. A Fixed List does not directly submit to a Sponsor or forward to Accounting.
- New Fixed List, request decisions, and Add/Edit User are dialogs or forms. Review/member detail screens and program create/edit screens retain their own routes.
- The small captions summarize key page actions; they do not guarantee every action is available for every status or attempt to catalog every HTTP endpoint.
- Programs/Create or Edit and List Details/Edit Draft combine closely related screens for a readable high-level map. Their underlying routes remain separate.
- Sign Out is one shared authenticated action, repeated availability represented by a footer.

## Existing wording limitation

The Sponsor dashboard contains a descriptive “Forward to Accounting” step. Actual forwarding remains a separate FASSG action in generated batch details. The sitemap follows the authorized route and action, not that misleading dashboard description. No source correction was made as part of documentation work.

## Validation limits

Route names, source file existence, unique node identifiers, XML syntax, text fit, and image dimensions are checked without bootstrapping Laravel. The PNG is visually inspected. This does not constitute browser or runtime navigation testing. The SVG is editable; the PDF contains the high-resolution raster diagram.

## Source references

'''+''.join('- `'+s+'`\n' for s in sources)
(OUT/'Source_Evidence.md').write_text(evidence,encoding='utf-8')
validation=dict(node_count=len(nodes),mapped_routes=len(checked),roles=[r['title'] for r in roles],image_pixels=list(im.size),source_hashes=hashes,checks={'unique_nodes':True,'valid_connection_targets':True,'routes_found_in_source':True,'svg_xml_valid':True,'text_fits':True,'laravel_bootstrapped':False,'database_accessed':False,'runtime_browser_tested':False})
assert all(hashlib.sha256((ROOT/s).read_bytes()).hexdigest()==h for s,h in hashes.items())
(OUT/'Validation.json').write_text(json.dumps(validation,indent=2),encoding='utf-8')
print(json.dumps({'files':[str(png),str(svg_path),str(pdf)],'nodes':len(nodes),'mapped_routes':len(checked),'pixel_dimensions':list(im.size),'largest_column_bottom':max(bottoms)}))
