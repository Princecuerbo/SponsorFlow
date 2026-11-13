"""Create documentation graphics only. No Laravel bootstrap, SQL or application changes."""
from collections import Counter
from html import escape
from pathlib import Path
import json
import math
import sys
import xml.etree.ElementTree as ET

from PIL import Image, ImageDraw, ImageFont

Image.init()
sys.stdout.reconfigure(encoding="utf-8")
OUT = Path(__file__).resolve().parent
ROOT = OUT.parent.parent
previous = json.loads((ROOT / "output/sponsorflow-use-cases/use_cases.json").read_text(encoding="utf-8-sig"))

W, H, SCALE = 3050, 3600, 1.6
INK, MUTED, BORDER = "#202B37", "#536274", "#303A45"
ASSOCIATION, FILL = "#556170", "#FFF6E4"
FONT_PATH = {False:"C:/Windows/Fonts/arial.ttf", True:"C:/Windows/Fonts/arialbd.ttf"}
im = Image.new("RGB", (round(W*SCALE), round(H*SCALE)), "white")
d = ImageDraw.Draw(im)
fonts, textboxes, ovals, associations, relationships = {}, [], {}, [], []
svg = [f'<svg xmlns="http://www.w3.org/2000/svg" width="{W}" height="{H}" viewBox="0 0 {W} {H}" role="img" aria-labelledby="title desc">',
       '<title id="title">SponsorFlow Corrected Use Case Diagram</title>',
       '<desc id="desc">Five actors and twenty-seven unique use cases. Login and Logout are repeated representations of shared use cases. Actor associations have no arrowheads. Four dashed include or extend relationships point to their UML targets.</desc>',
       '<rect width="100%" height="100%" fill="white"/>']

def font(size,bold=False):
    if (size,bold) not in fonts:
        fonts[size,bold] = ImageFont.truetype(FONT_PATH[bold], round(size*SCALE))
    return fonts[size,bold]

def length(s,size,bold=False):
    return d.textlength(s,font=font(size,bold))/SCALE

def wrap(s,maxw,size,bold=False):
    result,current=[],""
    for word in s.split():
        candidate=(current+" "+word).strip()
        if current and length(candidate,size,bold)>maxw:
            result.append(current)
            current=word
        else:
            current=candidate
    if current:
        result.append(current)
    assert all(length(t,size,bold)<=maxw for t in result),s
    return result

def text(x,y,s,size=27,bold=False,center=False,color=INK,maxw=None):
    tw=length(s,size,bold)
    if maxw is not None:
        assert tw<=maxw,(s,tw,maxw)
    left=x-tw/2 if center else x
    assert 0<=left and left+tw<=W and 0<=y and y+size*1.25<=H,s
    d.text((round(left*SCALE),round(y*SCALE)),s,fill=color,font=font(size,bold),anchor="lt")
    svg.append(f'<text x="{x}" y="{y}" font-family="Arial, sans-serif" font-size="{size}" font-weight="{700 if bold else 400}" text-anchor="{"middle" if center else "start"}" dominant-baseline="text-before-edge" fill="{color}">{escape(s)}</text>')
    textboxes.append({"text":s,"bounds":[left,y,left+tw,y+size*1.15]})

def line(points,color=INK,width=2,dashed=False):
    for (x1,y1),(x2,y2) in zip(points,points[1:]):
        dist=math.hypot(x2-x1,y2-y1)
        if not dist:
            continue
        if dashed:
            pos=0
            while pos<dist:
                end=min(dist,pos+12)
                a=(x1+(x2-x1)*pos/dist,y1+(y2-y1)*pos/dist)
                b=(x1+(x2-x1)*end/dist,y1+(y2-y1)*end/dist)
                d.line([(round(a[0]*SCALE),round(a[1]*SCALE)),(round(b[0]*SCALE),round(b[1]*SCALE))],fill=color,width=max(1,round(width*SCALE)))
                pos+=20
        else:
            d.line([(round(x1*SCALE),round(y1*SCALE)),(round(x2*SCALE),round(y2*SCALE))],fill=color,width=max(1,round(width*SCALE)))
    svg.append('<polyline points="'+' '.join(f'{x},{y}' for x,y in points)+f'" fill="none" stroke="{color}" stroke-width="{width}" stroke-linejoin="round"'+(' stroke-dasharray="12 8"' if dashed else '')+'/>')

def rect(x,y,w,h,fill="white",stroke=BORDER,width=2):
    d.rectangle([round(v*SCALE) for v in (x,y,x+w,y+h)],fill=fill,outline=stroke,width=max(1,round(width*SCALE)))
    svg.append(f'<rect x="{x}" y="{y}" width="{w}" height="{h}" fill="{fill}" stroke="{stroke}" stroke-width="{width}"/>')

labels = {
 "UC-01":["Login / Authenticate"],
 "UC-02":["Register Student Account"],
 "UC-03":["View Available","Sponsorship Programs"],
 "UC-23":["Request SLE-FHE Verification"],
 "UC-04":["Submit Sponsorship","Application"],
 "UC-05":["Apply to an Open Program After","Previous Sponsorship Expires"],
 "UC-06":["Track Application Status"],
 "UC-07":["Manage Sponsorship Programs"],
 "UC-08":["Verify SLE-FHE Eligibility"],
 "UC-09":["Encode / Import","Sponsor-Provided Fixed List"],
 "UC-10":["Upload Required","Documents"],
 "UC-11":["Configure Location","and Grade Eligibility"],
 "UC-12":["Finalize Fixed List"],
 "UC-13":["Manually Prepare","Generated Batch"],
 "UC-14":["Submit Generated","Batch to Sponsor"],
 "UC-15":["Review Submitted","Generated Batches"],
 "UC-16":["Approve or Reject","Entire Submitted Batch"],
 "UC-17":["Upload Approval /","Confirmation Evidence"],
 "UC-18":["View Forwarded Approved","Beneficiary Records"],
 "UC-19":["Print / Export","Beneficiary References"],
 "UC-20":["Generate Summary Reports"],
 "UC-21":["Manage User Accounts","and Roles"],
 "UC-22":["Monitor Audit Logs","and Manage Backups"],
 "UC-24":["Verify Generated Batch Member","Qualifications and Documents"],
 "UC-25":["Record Receipt of","Sponsor Decision"],
 "UC-26":["Forward Approved Beneficiaries","to Accounting"],
 "UC-27":["Logout"],
}
assert set(labels)=={f"UC-{i:02d}" for i in range(1,28)}

groups = [
 {"id":"student","name":"Student Applicant","actor_x":155,"actor_y":775,"side":"left","cx":820,"rx":330,
  "cases":[("UC-01",240),("UC-02",390),("UC-03",540),("UC-23",690),("UC-04",860),("UC-05",1100),("UC-06",1270),("UC-27",1420)]},
 {"id":"fassg","name":"FASSG Personnel","actor_x":155,"actor_y":2470,"side":"left","cx":820,"rx":330,
  "cases":[("UC-01",1640),("UC-07",1790),("UC-09",1940),("UC-12",2090),("UC-08",2240),("UC-24",2390),("UC-13",2540),("UC-14",2690),("UC-25",2840),("UC-26",2990),("UC-20",3140),("UC-27",3290)]},
 {"id":"sponsor","name":"Sponsor","actor_x":2880,"actor_y":1010,"side":"right","cx":2250,"rx":350,
  "cases":[("UC-01",680),("UC-15",860),("UC-17",1040),("UC-16",1220),("UC-27",1400)]},
 {"id":"accounting","name":"Accounting Office Personnel","actor_x":2880,"actor_y":2130,"side":"right","cx":2250,"rx":350,
  "cases":[("UC-01",1780),("UC-18",1970),("UC-19",2250),("UC-27",2430)]},
 {"id":"admin","name":"System Administrator","actor_x":2880,"actor_y":3100,"side":"right","cx":2250,"rx":350,
  "cases":[("UC-01",2820),("UC-21",3000),("UC-22",3180),("UC-27",3360)]},
]

def add_oval(key,cid,cx,cy,rx=330):
    ry=58 if len(labels[cid])==1 else 68
    ovals[key]={"key":key,"id":cid,"cx":cx,"cy":cy,"rx":rx,"ry":ry,"labels":labels[cid]}

for g in groups:
    for cid,y in g["cases"]:
        add_oval(g["id"]+":"+cid,cid,g["cx"],y,g["rx"])
add_oval("student:UC-10","UC-10",1570,860,300)
add_oval("fassg:UC-11","UC-11",1570,1790,300)

def endpoint(key,degrees):
    o=ovals[key]
    a=math.radians(degrees)
    return o["cx"]+o["rx"]*math.cos(a),o["cy"]+o["ry"]*math.sin(a)

def actor(g):
    x,y=g["actor_x"],g["actor_y"]
    # Association origins are at the end of the actor's nearest arm.
    head_y=y-79
    d.ellipse([round(v*SCALE) for v in (x-24,head_y-24,x+24,head_y+24)],fill="white",outline=INK,width=round(2.5*SCALE))
    svg.append(f'<circle cx="{x}" cy="{head_y}" r="24" fill="white" stroke="{INK}" stroke-width="2.5"/>')
    line([(x,head_y+24),(x,y+23)],width=2.5)
    line([(x-55,y-22),(x+55,y-22)],width=2.5)
    line([(x-45,y+86),(x,y+23),(x+45,y+86)],width=2.5)
    name_lines=wrap(g["name"],290,28,True)
    for i,s in enumerate(name_lines):
        text(x,y+104+i*36,s,28,True,True,maxw=290)

# The system boundary encloses use cases, not the five actors.
rect(350,72,2340,3415,"white",BORDER,2.4)
text(1520,101,"SPONSORFLOW SYSTEM",43,True,True)
text(490,164,"STUDENT SERVICES",22,True,color=MUTED)
text(490,1545,"FASSG PREPARATION AND HANDOFF",22,True,color=MUTED)
text(1900,575,"SPONSOR REVIEW",22,True,color=MUTED)
text(1900,1680,"ACCOUNTING REFERENCE",22,True,color=MUTED)
text(1900,2715,"SYSTEM ADMINISTRATION",22,True,color=MUTED)

rect(1915,212,705,255,"#F7F9FC","#9AA8B6",1.5)
note=["UC-01 and UC-27 are repeated for readability.",
      "They represent one shared Login use case",
      "and one shared Logout use case.",
      "Solid lines: actor participation.",
      "Dashed arrows: include / extend relationships."]
for i,s in enumerate(note):
    text(1940,238+i*40,s,25,maxw=655)

# All actor associations are solid and have NO arrowhead.
for g in groups:
    arm=(g["actor_x"]+(55 if g["side"]=="left" else -55),g["actor_y"]-22)
    origin=(315 if g["side"]=="left" else 2720,g["actor_y"]-22)
    line([arm,origin],ASSOCIATION,1.8)
    for cid,_ in g["cases"]:
        key=g["id"]+":"+cid
        end=endpoint(key,180 if g["side"]=="left" else 0)
        line([origin,end],ASSOCIATION,1.8)
        associations.append({"actor":g["name"],"use_case":cid,"representation":key,"points":[origin,end],"arrowheads":0})

for o in ovals.values():
    cx,cy,rx,ry=o["cx"],o["cy"],o["rx"],o["ry"]
    d.ellipse([round(v*SCALE) for v in (cx-rx,cy-ry,cx+rx,cy+ry)],fill=FILL,outline=BORDER,width=round(2.2*SCALE))
    svg.append(f'<ellipse cx="{cx}" cy="{cy}" rx="{rx}" ry="{ry}" fill="{FILL}" stroke="{BORDER}" stroke-width="2.2"/>')
    total=34+len(o["labels"])*33
    top=cy-total/2
    text(cx,top,o["id"],27,True,True,maxw=rx)
    for i,s in enumerate(o["labels"]):
        ty=top+34+i*33
        # Check both the top and bottom of the text rectangle fit inside the ellipse.
        half_width=length(s,26)/2
        for yy in (ty,ty+26*1.15):
            assert (half_width/rx)**2+((yy-cy)/ry)**2 < 0.96,(o["id"],s)
        text(cx,ty,s,26,center=True,maxw=rx*2-50)

def relationship(source,target,kind,points,label_x,label_y,condition=None):
    # Open UML dependency arrowhead: one head at the target, never at the source.
    line(points,INK,2.2,True)
    x,y=points[-1]
    px,py=points[-2]
    angle=math.atan2(y-py,x-px)
    left=(x-15*math.cos(angle)+7*math.sin(angle),y-15*math.sin(angle)-7*math.cos(angle))
    right=(x-15*math.cos(angle)-7*math.sin(angle),y-15*math.sin(angle)+7*math.cos(angle))
    line([left,(x,y),right],INK,2.2)
    text(label_x,label_y,"«"+kind+"»",22,True,True)
    relationships.append({"from":ovals[source]["id"],"to":ovals[target]["id"],"type":kind,"source_representation":source,"target_representation":target,"points":points,"arrowheads":1,"condition":condition})

relationship("student:UC-10","student:UC-04","extend",[endpoint("student:UC-10",180),endpoint("student:UC-04",0)],1210,824,"Documents are required or supplied")
relationship("student:UC-05","student:UC-04","include",[endpoint("student:UC-05",270),endpoint("student:UC-04",90)],897,978)
relationship("fassg:UC-11","fassg:UC-07","extend",[endpoint("fassg:UC-11",180),endpoint("fassg:UC-07",0)],1210,1754,"Criteria are configured or changed")
relationship("accounting:UC-19","accounting:UC-18","extend",[endpoint("accounting:UC-19",270),endpoint("accounting:UC-18",90)],2355,2090,"User selects Print or Export")
text(1570,957,"[documents are required or supplied]",23,center=True,color=MUTED,maxw=580)
text(1570,1887,"[criteria are configured or changed]",23,center=True,color=MUTED,maxw=580)
text(2290,2126,"[user selects Print",22,color=MUTED,maxw=300)
text(2290,2154,"or Export]",22,color=MUTED,maxw=300)

for g in groups:
    actor(g)
text(W/2,3520,"Figure 3.X. SponsorFlow Use Case Diagram",31,True,True)
text(W/2,3562,"Five actors • 27 unique use cases • Manual FASSG preparation, whole-batch Sponsor decisions and explicit forwarding",23,center=True,color=MUTED,maxw=W-100)
svg.append("</svg>")
svg_text="\n".join(svg)
ET.fromstring(svg_text)

# Validate the exact semantic relationships and membership of every displayed use case.
expected={(r["from"],r["to"],r["type"]) for r in previous["relationships"]}
actual={(r["from"],r["to"],r["type"]) for r in relationships}
assert actual==expected
counts=Counter(o["id"] for o in ovals.values())
assert len(counts)==27 and len(ovals)==35
assert counts["UC-01"]==5 and counts["UC-27"]==5
assert all(n==1 for k,n in counts.items() if k not in {"UC-01","UC-27"})
assert len(associations)==33 and all(a["arrowheads"]==0 for a in associations)
assert any(a["actor"]=="Student Applicant" and a["use_case"]=="UC-05" for a in associations)
for g in groups:
    rows=[ovals[g["id"]+":"+cid] for cid,_ in g["cases"]]
    for a,b in zip(rows,rows[1:]):
        assert b["cy"]-b["ry"]>a["cy"]+a["ry"],(a["id"],b["id"])
for r in relationships:
    for key,point in [(r["source_representation"],r["points"][0]),(r["target_representation"],r["points"][-1])]:
        o=ovals[key]
        assert abs(((point[0]-o["cx"])/o["rx"])**2+((point[1]-o["cy"])/o["ry"])**2-1)<1e-6

overlaps=[]
for i,a in enumerate(textboxes):
    ax1,ay1,ax2,ay2=a["bounds"]
    for b in textboxes[i+1:]:
        bx1,by1,bx2,by2=b["bounds"]
        if min(ax2,bx2)>max(ax1,bx1)+1 and min(ay2,by2)>max(ay1,by1)+1:
            overlaps.append([a["text"],b["text"]])
assert not overlaps,overlaps

base=OUT/"SponsorFlow_Use_Case_Corrected"
Path(str(base)+".svg").write_text(svg_text,encoding="utf-8")
im.save(str(base)+".png",dpi=(300,300))
im.save(str(base)+".pdf",resolution=220)
preview=im.copy()
preview.thumbnail((1700,2100),Image.Resampling.LANCZOS)
preview.save(OUT/"SponsorFlow_Use_Case_Preview.png")
manifest={"actors":[g["name"] for g in groups],"use_cases":{cid:" ".join(ls) for cid,ls in labels.items()},"representations":list(ovals.values()),"associations":associations,"relationships":relationships}
(OUT/"Diagram_Definition.json").write_text(json.dumps(manifest,indent=2,ensure_ascii=False),encoding="utf-8")
validation={"actors":len(groups),"unique_use_cases":len(counts),"oval_representations":len(ovals),"plain_actor_associations":len(associations),"correct_include_extend_relationships":len(relationships),"text_overlap_count":len(overlaps),"svg_xml_valid":True,"text_fits_ovals":True,"png_dimensions":list(im.size),"application_or_database_access":False}
(OUT/"Validation.json").write_text(json.dumps(validation,indent=2),encoding="utf-8")
(OUT/"UC-27_Logout_Description.md").write_text("""# UC-27 — Logout

| Field | Description |
|---|---|
| Primary actor | Any authenticated Student Applicant, FASSG Personnel, Sponsor, Accounting Office Personnel, or System Administrator |
| Purpose | Ends the current authenticated session. |
| Preconditions | The user has an authenticated session. |
| Trigger | The user selects Sign Out / Logout. |
| Main flow | The user submits the logout action. The system ends authentication, invalidates the session, regenerates the CSRF token, and redirects to the configured unauthenticated destination. |
| Postconditions | The ended session no longer provides access to protected functions. Existing student, verification, application and sponsorship records are preserved. |
| Relationships | None. Logout is shared by all five roles. |

This adds an identifier for the Logout oval in the supplied diagram. UC-01–UC-26 retain their existing identifiers. The earlier description files are not overwritten.
""",encoding="utf-8")
print(json.dumps(validation,indent=2))
