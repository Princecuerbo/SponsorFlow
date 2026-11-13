"""Render documentation only. Does not import or bootstrap SponsorFlow."""
import json
import math
import re
import zipfile
import hashlib
from pathlib import Path
from html import escape
from xml.etree import ElementTree as ET
from PIL import Image, ImageDraw, ImageFont

OUT = Path(__file__).resolve().parent
ROOT = OUT.parent.parent
DATA = json.loads((OUT / 'use_cases.json').read_text(encoding='utf-8'))
CASES = {c['id']: c for c in DATA['cases']}
assert len(CASES) == 26 and set(CASES) == {f'UC-{i:02d}' for i in range(1, 27)}
for case in CASES.values():
    for field in ['name', 'actor', 'secondary', 'description', 'preconditions', 'trigger', 'main', 'alternatives', 'exceptions', 'postconditions', 'relations']:
        assert case[field], (case['id'], field)
    for source in case['sources']:
        assert (ROOT / source).is_file(), source
    for ref in re.findall(r'UC-\d{2}', json.dumps(case)):
        assert ref in CASES, ref
for rel in DATA['relationships']:
    assert rel['from'] in CASES and rel['to'] in CASES
    assert rel['type'] in ['include', 'extend']
    assert rel['from'] != rel['to']

W, H, SCALE = 2100, 2570, 2
INK, LINE, GOLD, MUTED = '#253542', '#a96643', '#fff0cf', '#586772'
FONT = Path('C:/Windows/Fonts/arial.ttf')
BOLD = Path('C:/Windows/Fonts/arialbd.ttf')
im = Image.new('RGB', (W * SCALE, H * SCALE), 'white')
d = ImageDraw.Draw(im)
svg = [f'<svg xmlns="http://www.w3.org/2000/svg" width="{W}" height="{H}" viewBox="0 0 {W} {H}">', '<rect width="100%" height="100%" fill="white"/>']
fonts = {}
texts = []

def font(size, bold=False):
    key = size, bold
    if key not in fonts:
        fonts[key] = ImageFont.truetype(str(BOLD if bold else FONT), round(size * SCALE))
    return fonts[key]

def text(x, y, value, size=20, bold=False, anchor='middle', color=INK, maxw=None):
    width = d.textlength(value, font=font(size, bold)) / SCALE
    if maxw is not None:
        assert width <= maxw, (value, width, maxw)
    left = x - width / 2 if anchor == 'middle' else x
    assert left >= 0 and left + width <= W, (value, left, width)
    assert 0 <= y and y + size * 1.4 <= H
    d.text((round(left * SCALE), round(y * SCALE)), value, fill=color, font=font(size, bold), anchor='lt')
    svg.append(f'<text x="{x}" y="{y}" font-family="Arial, sans-serif" font-size="{size}" font-weight="{700 if bold else 400}" text-anchor="{anchor}" dominant-baseline="text-before-edge" fill="{color}">{escape(value)}</text>')
    texts.append(value)

def line(points, color=INK, width=1.6, dashed=False):
    if dashed:
        for (x1, y1), (x2, y2) in zip(points, points[1:]):
            dist = math.hypot(x2-x1, y2-y1)
            if not dist:
                continue
            pos = 0
            while pos < dist:
                end = min(pos + 8, dist)
                p1 = (SCALE * (x1+(x2-x1)*pos/dist), SCALE * (y1+(y2-y1)*pos/dist))
                p2 = (SCALE * (x1+(x2-x1)*end/dist), SCALE * (y1+(y2-y1)*end/dist))
                d.line([p1, p2], fill=color, width=round(width*SCALE))
                pos += 14
    else:
        d.line([(round(x*SCALE), round(y*SCALE)) for x,y in points], fill=color, width=round(width*SCALE))
    dash = ' stroke-dasharray="8 6"' if dashed else ''
    svg.append('<polyline points="' + ' '.join(f'{x},{y}' for x,y in points) + f'" fill="none" stroke="{color}" stroke-width="{width}"{dash}/>')

def rect(x, y, w, h, fill='none', stroke=INK, width=1.5):
    d.rectangle([round(v*SCALE) for v in (x,y,x+w,y+h)], fill=None if fill=='none' else fill, outline=stroke, width=round(width*SCALE))
    svg.append(f'<rect x="{x}" y="{y}" width="{w}" height="{h}" fill="{fill}" stroke="{stroke}" stroke-width="{width}"/>')

def circle(x,y,r):
    d.ellipse([round(v*SCALE) for v in (x-r,y-r,x+r,y+r)],fill='white',outline=INK,width=3)
    svg.append(f'<circle cx="{x}" cy="{y}" r="{r}" fill="white" stroke="{INK}" stroke-width="1.5"/>')

def actor(x,y,labels,side=None):
    circle(x,y,13)
    line([(x,y+13),(x,y+59)],width=2)
    line([(x-27,y+31),(x,y+24),(x+27,y+31)],width=2)
    line([(x-24,y+87),(x,y+59),(x+24,y+87)],width=2)
    for n,label in enumerate(labels):
        text(x,y-28*len(labels)-12+n*25,label,19,True,maxw=230)
    if side=='left':
        line([(x+27,y+31),(x+50,y+31)],width=1.3)
    if side=='right':
        line([(x-27,y+31),(x-50,y+31)],width=1.3)

text(60,32,'SPONSORFLOW — USE CASE DIAGRAM',39,True,anchor='start')
text(60,85,'Five business roles | Manual preparation and verification | Whole-batch Sponsor decision | Explicit FASSG forwarding',20,anchor='start',color=MUTED,maxw=1990)
rect(245,300,1610,2110,stroke=INK,width=2)
text(270,316,'SPONSORFLOW SYSTEM',20,True,anchor='start')

positions = {'UC-01': (1050,405,235,49)}
student_order = ['UC-02','UC-03','UC-04','UC-10','UC-05','UC-06','UC-23']
fassg_order = ['UC-07','UC-11','UC-08','UC-09','UC-12','UC-13','UC-24','UC-14','UC-25','UC-26','UC-20']
for i,cid in enumerate(student_order): positions[cid]=(650,550+i*100,235,40)
for i,cid in enumerate(fassg_order): positions[cid]=(650,1305+i*100,235,40)
for cid,y in [('UC-15',610),('UC-17',790),('UC-16',970),('UC-18',1280),('UC-19',1500),('UC-21',1880),('UC-22',2110)]:
    positions[cid]=(1450,y,240,49)
assert set(positions)==set(CASES)
labels = {
 'UC-01':['Login / Authenticate'],
 'UC-02':['Register Student Account'],
 'UC-03':['View Available Programs'],
 'UC-04':['Submit Sponsorship Application'],
 'UC-05':['Apply After Previous','Sponsorship Expires'],
 'UC-06':['Track Application Status'],
 'UC-07':['Manage Sponsorship Programs'],
 'UC-08':['Verify SLE-FHE Eligibility'],
 'UC-09':['Encode / Import','Sponsor Fixed List'],
 'UC-10':['Upload Required Documents'],
 'UC-11':['Configure Program','Eligibility Criteria'],
 'UC-12':['Finalize Fixed List'],
 'UC-13':['Manually Prepare','Generated Batch'],
 'UC-14':['Submit Batch to Sponsor'],
 'UC-15':['Review Submitted','Generated Batches'],
 'UC-16':['Approve or Reject','Entire Submitted Batch'],
 'UC-17':['Upload Sponsor','Confirmation Evidence'],
 'UC-18':['View Forwarded Approved','Beneficiary Records'],
 'UC-19':['Print / Export','Beneficiary References'],
 'UC-20':['Generate Summary Reports'],
 'UC-21':['Manage User Accounts and Roles'],
 'UC-22':['View Audit Logs','and Manage Backups'],
 'UC-23':['Request SLE-FHE Verification'],
 'UC-24':['Verify Batch Member','Qualifications and Documents'],
 'UC-25':['Record Receipt of','Sponsor Decision'],
 'UC-26':['Forward Approved Beneficiaries','to Accounting']
}
for cid, ls in labels.items():
    assert ' '.join(ls)==CASES[cid]['name'], (cid,ls,CASES[cid]['name'])

def ellipse_point(cid,angle):
    x,y,rx,ry=positions[cid]
    a=math.radians(angle)
    return x+rx*math.cos(a),y+ry*math.sin(a)

# Plain, undirected actor associations; repeated actor symbols are the same five roles.
top_roles=[(420,['Student','Applicant']),(735,['FASSG','Personnel']),(1050,['Sponsor']),(1365,['Accounting Office','Personnel']),(1680,['System','Administrator'])]
for i,(x,ls) in enumerate(top_roles):
    actor(x,195,ls)
    line([(x,282),ellipse_point('UC-01',215+i*27.5)],width=1.3)
groups=[('Student Applicant',110,785,['Student','Applicant'],student_order,'left'),
        ('FASSG Personnel',110,1770,['FASSG','Personnel'],fassg_order,'left'),
        ('Sponsor',2005,760,['Sponsor'],['UC-15','UC-17','UC-16'],'right'),
        ('Accounting Office Personnel',2005,1360,['Accounting','Office Personnel'],['UC-18','UC-19'],'right'),
        ('System Administrator',2005,1960,['System','Administrator'],['UC-21','UC-22'],'right')]
associations=[]
for role,x,y,ls,cids,side in groups:
    actor(x,y,ls,side)
    for cid in cids:
        start=(x+50 if side=='left' else x-50,y+31)
        end=ellipse_point(cid,180 if side=='left' else 0)
        line([start,end],width=1.3)
        associations.append((role,cid))
associations.extend((role,'UC-01') for role in DATA['actors'])

for x,y,heading in [(420,475,'STUDENT SERVICES'),(420,1225,'FASSG PREPARATION AND HANDOFF'),(1210,510,'SPONSOR REVIEW'),(1210,1170,'ACCOUNTING REFERENCE'),(1210,1765,'SYSTEM ADMINISTRATION')]:
    text(x,y,heading,18,True,anchor='start',color=MUTED)

# Named use-case ovals; diagram names are checked against the description catalog.
for cid,(x,y,rx,ry) in positions.items():
    d.ellipse([round(v*SCALE) for v in (x-rx,y-ry,x+rx,y+ry)],fill=GOLD,outline=LINE,width=3)
    svg.append(f'<ellipse cx="{x}" cy="{y}" rx="{rx}" ry="{ry}" fill="{GOLD}" stroke="{LINE}" stroke-width="1.5"/>')
    ls=labels[cid]
    total=22+len(ls)*23
    start=y-total/2
    text(x,start,cid,16,True,color=LINE,maxw=rx)
    for i,label in enumerate(ls):
        ty=start+22+i*23
        maxw=2*rx*math.sqrt(1-((ty+10-y)/ry)**2)-25
        text(x,ty,label,19,True,maxw=maxw)

def hollow_arrow(points):
    line(points,color=LINE,width=1.8,dashed=True)
    x,y=points[-1]; x0,y0=points[-2]
    a=math.atan2(y-y0,x-x0)
    p1=(x-12*math.cos(a)+5*math.sin(a),y-12*math.sin(a)-5*math.cos(a))
    p2=(x-12*math.cos(a)-5*math.sin(a),y-12*math.sin(a)+5*math.cos(a))
    line([p1,(x,y),p2],color=LINE,width=1.8)

relation_drawn=[]
def relationship(a,b,kind,points,lx,ly,note):
    hollow_arrow(points)
    text(lx,ly,'«'+kind+'»',17,anchor='start',color=LINE)
    for i,s in enumerate(note): text(lx,ly+24+i*19,s,14,anchor='start',color=MUTED,maxw=190)
    relation_drawn.append((a,b,kind))

relationship('UC-05','UC-04','include',[ellipse_point('UC-05',0),(1080,950),(1080,736),ellipse_point('UC-04',-20)],1092,820,[])
relationship('UC-10','UC-04','extend',[ellipse_point('UC-10',0),(965,850),(965,764),ellipse_point('UC-04',20)],975,768,['[documents','required or','supplied]'])
relationship('UC-11','UC-07','extend',[ellipse_point('UC-11',0),(965,1405),(965,1305),ellipse_point('UC-07',0)],980,1310,['[criteria configured','or changed]'])
relationship('UC-19','UC-18','extend',[ellipse_point('UC-19',-90),ellipse_point('UC-18',90)],1475,1358,['[print/export requested]'])
assert set(relation_drawn)=={(r['from'],r['to'],r['type']) for r in DATA['relationships']}
assert len(associations)==30

line([(60,2450),(130,2450)],width=1.4)
text(142,2439,'Actor association (participation)',16,anchor='start',color=MUTED)
hollow_arrow([(700,2450),(780,2450)])
text(795,2439,'Dashed arrow: include / extend',16,anchor='start',color=MUTED)
text(60,2480,'Repeated actor symbols represent the same five roles. Login is a precondition for protected actions.',17,anchor='start',color=MUTED)
text(60,2510,'Receipt and forwarding are explicit FASSG actions. Accounting remains read-only. No automatic ranking or approval.',17,anchor='start',color=MUTED,maxw=1990)
svg.append('</svg>')
svg_content='\n'.join(svg)
ET.fromstring(svg_content)
(OUT/'SponsorFlow_Use_Case_Diagram.svg').write_text(svg_content,encoding='utf-8')
im.save(OUT/'SponsorFlow_Use_Case_Diagram.png',dpi=(300,300))
im.save(OUT/'SponsorFlow_Use_Case_Diagram.pdf','PDF',resolution=300)

FIELD_MAP=[('Use case ID','id'),('Use case name','name'),('Primary actor','actor'),('Secondary actor','secondary'),('Description','description'),('Preconditions','preconditions'),('Trigger','trigger'),('Main flow','main'),('Alternative flows','alternatives'),('Exception flows','exceptions'),('Postconditions','postconditions'),('Includes / extends','relations')]
notes=[
 'UC-01 to UC-22 retain the original numbering with corrected names and behavior. UC-23 to UC-26 add the missing request, member-review, receipt, and forwarding interactions.',
 'An Active account is distinct from documented SLE-FHE verification. A Verified application is distinct from Sponsor approval.',
 'Application statuses: Pending, Verified, Approved, Rejected, Ongoing, Expired, and Resubmission Requested. Batch membership is not a separate application status.',
 'Generated Batch statuses: Saved, Submitted, Approved, and Rejected. FASSG receipt and Accounting forwarding are separate recorded actions, not additional batch-status labels.',
 'A Saved batch may contain ordinary Pending/Verified applicants, the complete explicitly selected finalized Fixed List roster, or both. Draft creation and source-list finalization do not consume slots.',
 'Fixed List members need no separate online application. Internal processing records preserve source provenance and do not manufacture grades, documents, or verification.',
 'Authentication is a precondition for protected functions. Includes/extends do not represent the chronological Sponsor-to-FASSG-to-Accounting handoff.',
 'For Accounting, read-only means beneficiary and approval records cannot be edited; an authorized export may still create an audit entry.'
]

def parts(case,key):
    value=case[key]
    return [f'{i+1}. {s}' for i,s in enumerate(value)] if isinstance(value,list) else [value]

md=['# SponsorFlow — Corrected Use Case Descriptions','',DATA['narrative'],'','## Common interpretation','']
md += ['- '+n for n in notes]
md += ['','## Use case index','','| ID | Use case | Primary actor |','|---|---|---|']
for c in CASES.values(): md.append(f"| {c['id']} | {c['name']} | {c['actor']} |")
for i,c in enumerate(CASES.values(),24):
    md += ['',f"## Table 3.{i}: {c['name']}",'','| Field | Content |','|---|---|']
    for label,key in FIELD_MAP:
        value='<br>'.join(parts(c,key)).replace('|','\\|')
        md.append(f'| {label} | {value} |')
(OUT/'SponsorFlow_Use_Case_Descriptions.md').write_text('\n'.join(md)+'\n',encoding='utf-8')

css='''body{font-family:Arial,sans-serif;color:#253542;max-width:1000px;margin:36px auto;padding:0 20px;line-height:1.45}h1{font-size:27px}h2{font-size:21px}table{border-collapse:collapse;width:100%;margin:15px 0 32px}th,td{border:1px solid #bdc5cb;padding:8px 10px;text-align:left;vertical-align:top}th{background:#fff0cf}td:first-child{width:19%;font-weight:bold}p{margin:0 0 8px}li{margin-bottom:7px}img{max-width:100%}.case{break-before:page}.index td:first-child{width:10%}@media print{body{font-size:10pt;margin:0;max-width:none}.case{break-before:page}tr{break-inside:avoid}thead{display:table-header-group}@page{size:A4;margin:15mm}}'''
html=['<!doctype html><html lang="en"><head><meta charset="utf-8"><title>SponsorFlow Use Case Descriptions</title><style>'+css+'</style></head><body>', '<h1>SponsorFlow — Corrected Use Case Descriptions</h1>', '<p>'+escape(DATA['narrative'])+'</p>', '<h2>Common interpretation</h2><ul>']
html += ['<li>'+escape(n)+'</li>' for n in notes]
html += ['</ul><h2>Use case index</h2><table class="index"><thead><tr><th>ID</th><th>Use case</th><th>Primary actor</th></tr></thead><tbody>']
for c in CASES.values(): html.append('<tr>'+''.join('<td>'+escape(c[k])+'</td>' for k in ['id','name','actor'])+'</tr>')
html.append('</tbody></table>')
for i,c in enumerate(CASES.values(),24):
    html += [f'<section class="case"><h2>Table 3.{i}: {escape(c["name"])}</h2><table><thead><tr><th>Field</th><th>Content</th></tr></thead><tbody>']
    for label,key in FIELD_MAP:
        content=''.join('<p>'+escape(s)+'</p>' for s in parts(c,key))
        html.append('<tr><td>'+escape(label)+'</td><td>'+content+'</td></tr>')
    html.append('</tbody></table></section>')
html.append('</body></html>')
(OUT/'SponsorFlow_Use_Case_Descriptions.html').write_text('\n'.join(html),encoding='utf-8')

# Minimal editable WordprocessingML document, with no extra dependency or macros.
NS='http://schemas.openxmlformats.org/wordprocessingml/2006/main'
def paragraph(s,bold=False,style=None,break_before=False):
    props=(f'<w:pStyle w:val="{style}"/>' if style else '')+('<w:pageBreakBefore/>' if break_before else '')
    props+='<w:spacing w:after="80"/>'
    return '<w:p><w:pPr>'+props+'</w:pPr><w:r><w:rPr>'+('<w:b/>' if bold else '')+'</w:rPr><w:t xml:space="preserve">'+escape(s)+'</w:t></w:r></w:p>'

def cell(paragraphs,width,fill=None):
    pr=f'<w:tcW w:w="{width}" w:type="dxa"/><w:vAlign w:val="top"/>'
    if fill: pr+=f'<w:shd w:fill="{fill}"/>'
    return '<w:tc><w:tcPr>'+pr+'</w:tcPr>'+''.join(paragraphs)+'</w:tc>'

def doc_table(rows,widths=(2100,8100)):
    borders=''.join(f'<w:{edge} w:val="single" w:sz="4" w:color="B8C2C9"/>' for edge in ['top','left','bottom','right','insideH','insideV'])
    out=['<w:tbl><w:tblPr><w:tblW w:w="10200" w:type="dxa"/><w:tblLayout w:type="fixed"/><w:tblBorders>'+borders+'</w:tblBorders><w:tblCellMar><w:top w:w="80" w:type="dxa"/><w:left w:w="100" w:type="dxa"/><w:bottom w:w="80" w:type="dxa"/><w:right w:w="100" w:type="dxa"/></w:tblCellMar></w:tblPr><w:tblGrid>'+''.join(f'<w:gridCol w:w="{w}"/>' for w in widths)+'</w:tblGrid>']
    for n,row in enumerate(rows):
        out.append('<w:tr><w:trPr><w:cantSplit/>'+('<w:tblHeader/>' if n==0 else '')+'</w:trPr>')
        for col,(strings,width) in enumerate(zip(row,widths)):
            out.append(cell([paragraph(s,bold=(n==0 or col==0)) for s in strings],width,'FFF0CF' if n==0 else None))
        out.append('</w:tr>')
    out.append('</w:tbl>')
    return ''.join(out)

body=[paragraph('SponsorFlow',style='Title'),paragraph('Corrected Use Case Diagram and Descriptions',style='Subtitle'),paragraph(DATA['narrative']),paragraph('Common interpretation',style='Heading1')]
body += [paragraph('• '+n) for n in notes]
body += [paragraph('Use case index',style='Heading1',break_before=True)]
index_rows=[(["ID"],["Use case"],["Primary actor"])]
index_rows += [([c['id']],[c['name']],[c['actor']]) for c in CASES.values()]
body.append(doc_table(index_rows,(1100,6300,2800)))
for i,c in enumerate(CASES.values(),24):
    body.append(paragraph(f'Table 3.{i}: {c["name"]}',style='Heading1',break_before=True))
    rows=[(['Field'],['Content'])]+[([label],parts(c,key)) for label,key in FIELD_MAP]
    body.append(doc_table(rows))
section='<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="850" w:right="850" w:bottom="850" w:left="850" w:header="400" w:footer="400" w:gutter="0"/></w:sectPr>'
document='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="'+NS+'"><w:body>'+''.join(body)+section+'</w:body></w:document>'
styles='''<?xml version="1.0" encoding="UTF-8"?><w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="20"/><w:color w:val="253542"/></w:rPr></w:rPrDefault><w:pPrDefault><w:pPr><w:spacing w:after="80" w:line="250" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults><w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/></w:style><w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:basedOn w:val="Normal"/><w:rPr><w:b/><w:sz w:val="42"/></w:rPr></w:style><w:style w:type="paragraph" w:styleId="Subtitle"><w:name w:val="Subtitle"/><w:basedOn w:val="Normal"/><w:rPr><w:sz w:val="28"/></w:rPr></w:style><w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:basedOn w:val="Normal"/><w:pPr><w:keepNext/><w:outlineLvl w:val="0"/></w:pPr><w:rPr><w:b/><w:sz w:val="27"/></w:rPr></w:style></w:styles>'''
types='''<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/></Types>'''
rels='''<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/></Relationships>'''
docrels='''<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>'''
core='''<?xml version="1.0" encoding="UTF-8"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:title>SponsorFlow Corrected Use Case Descriptions</dc:title><dc:description>26 source-reviewed use cases with matching diagram identifiers.</dc:description></cp:coreProperties>'''
docx_parts={'[Content_Types].xml':types,'_rels/.rels':rels,'word/document.xml':document,'word/styles.xml':styles,'word/_rels/document.xml.rels':docrels,'docProps/core.xml':core}
for xml in docx_parts.values(): ET.fromstring(xml)
docx_path=OUT/'SponsorFlow_Use_Case_Descriptions.docx'
with zipfile.ZipFile(docx_path,'w',compression=zipfile.ZIP_DEFLATED) as z:
    for name,xml in docx_parts.items():z.writestr(name,xml.encode('utf-8'))
with zipfile.ZipFile(docx_path) as z:
    assert z.testzip() is None
    tree=ET.fromstring(z.read('word/document.xml'))
    assert len(tree.findall('.//{'+NS+'}tbl'))==27

refs=['# Source review and validation','','These are documentation artifacts. No Laravel bootstrap, endpoints, database queries, migrations, seeders, or runtime feature tests were executed by the renderer.','', 'The diagram is a use-case view, not an activity diagram. It deliberately does not turn chronological approval handoffs into include relationships.','', 'The Word package is structurally validated; pagination in desktop Microsoft Word has not been visually verified. The diagram must also receive visual review.','', '## Implementation references','']
for c in CASES.values():
    refs.append(f"- **{c['id']} — {c['name']}**: "+'; '.join('`'+s+'`' for s in c['sources']))
(OUT/'Source_Review.md').write_text('\n'.join(refs)+'\n',encoding='utf-8')
report={'status':'PASS','use_cases':26,'business_roles':5,'actor_symbols':10,'associations':len(associations),'include_relationships':1,'extend_relationships':3,'tables':'3.24–3.49','diagram_pixels':[W*SCALE,H*SCALE],'checks':['Every diagram label matches its description','Every referenced use case exists','Every implementation reference exists','Relationships match in diagram and data','Actor associations are undirected solid lines','Dependency arrowheads point to included/base use cases','Text width and canvas bounds checked','SVG and DOCX XML parsed','DOCX ZIP integrity passed','26 description tables plus one index table'],'limits':['No runtime application tests','No database access','Desktop Word pagination not visually verified'],'data_sha256':hashlib.sha256((OUT/'use_cases.json').read_bytes()).hexdigest()}
(OUT/'Validation.json').write_text(json.dumps(report,indent=2),encoding='utf-8')
print(json.dumps(report,indent=2))
