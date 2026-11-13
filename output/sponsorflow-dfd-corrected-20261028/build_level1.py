"""Draw the documentation DFD deterministically; never bootstrap Laravel or connect to a DB."""
from collections import OrderedDict
from copy import deepcopy
from html import escape
from pathlib import Path
import hashlib
import json
import math
import sys
import xml.etree.ElementTree as ET

from PIL import Image, ImageDraw, ImageFont

Image.init()

sys.stdout.reconfigure(encoding="utf-8")
OUT = Path(__file__).resolve().parent
ROOT = OUT.parent.parent
SOURCE = ROOT / "output/sponsorflow-dfd-final/DFD_Flow_Definitions.json"
data = deepcopy(json.loads(SOURCE.read_text(encoding="utf-8-sig")))
processes = data["processes"]
by_id = {p["id"]: p for p in processes}

by_id["1.0"]["name"] = "Manage User Accounts and Authentication"
by_id["4.0"]["name"] = "Process Student Applications and Documents"
by_id["5.0"]["name"] = "Manage Fixed Lists and Prepare Generated Batches"
by_id["7.0"]["name"] = "Record FASSG Receipt, Forward Beneficiaries and Provide Reports"
for flow in by_id["5.0"]["store_flows"]:
    if flow["store"] == "D7" and flow["direction"] == "in":
        flow["data"] = "Finalized Fixed List / saved batch roster"

by_id["6.0"]["store_flows"].extend([
    {"store": "D5", "direction": "out", "data": "Updated available slots / program status (approval only)"},
    {"store": "D9", "direction": "out", "data": "Sponsor decision audit event"},
])
for flow in by_id["6.0"]["store_flows"]:
    if flow["store"] == "D7":
        flow["data"] = "Exact submitted batch roster" if flow["direction"] == "in" else "Batch approval / rejection status"
    if flow["store"] == "D6":
        flow["data"] = "Applicant and document evidence" if flow["direction"] == "in" else "Approved application records (approval only)"
    if flow["store"] == "D1" and flow["direction"] == "out":
        flow["data"] = "Active sponsorship status (approval only)"
by_id["7.0"]["store_flows"].append({
    "store": "D7", "direction": "out", "data": "Batch forwarding attribution (when first forwarded)"
})

ENTITIES = {"Student", "FASSG Office", "Sponsor", "Accounting Office", "System Administrator"}
assert len(processes) == 8
assert {p["id"] for p in processes} == {f"{i}.0" for i in range(1, 9)}
assert {f["entity"] for p in processes for f in p["external_flows"]} == ENTITIES
for p in processes:
    for f in p["store_flows"]:
        assert f["store"] in data["stores"]
        assert f["direction"] in {"in", "out"}
    for f in p["external_flows"]:
        assert f["entity"] in ENTITIES and f["direction"] in {"in", "out"}

# "in" and "out" are always relative to the process, never relative to a store.
required = {
    "6.0": [("D5", "out"), ("D6", "in"), ("D6", "out"), ("D7", "in"), ("D7", "out"), ("D8", "in"), ("D8", "out")],
    "7.0": [("D1", "in"), ("D3", "in"), ("D6", "in"), ("D7", "in"), ("D7", "out"), ("D8", "in"), ("D8", "out")],
}
for pid, specs in required.items():
    for sid, direction in specs:
        assert any(f["store"] == sid and f["direction"] == direction for f in by_id[pid]["store_flows"])
assert any(f["entity"] == "Student" and f["direction"] == "in" and "inquiry" in f["data"] for f in by_id["7.0"]["external_flows"])
assert not any(f["store"] == "D2" and f["direction"] == "out" for p in processes for f in p["store_flows"])

W, SCALE, PITCH, GROUP_GAP = 3000, 1.6, 72, 14
STORE_X, STORE_W = 60, 530
PROC_X, PROC_W = 1285, 430
ACTOR_X, ACTOR_W = 2410, 530
INK, MUTED, ORANGE = "#213447", "#546475", "#C96936"
PROCESS_FILL, ENTITY_FILL = "#FFE1A0", "#FFF2D9"
FONTS = {False: "C:/Windows/Fonts/arial.ttf", True: "C:/Windows/Fonts/arialbd.ttf"}

def grouped(flows, key):
    groups = OrderedDict()
    for f in flows:
        groups.setdefault(f[key], []).append(f)
    return groups

def group_height(groups):
    return sum(len(fs) * PITCH for fs in groups.values()) + max(0, len(groups)-1) * GROUP_GAP

layout = []
y = 210
for p in processes:
    stores = grouped(p["store_flows"], "store")
    actors = grouped(p["external_flows"], "entity")
    height = max(group_height(stores), group_height(actors), 260) + 48
    layout.append((p, y, height, stores, actors))
    y += height + 26
H = y + 175
im = Image.new("RGB", (round(W*SCALE), round(H*SCALE)), "white")
draw = ImageDraw.Draw(im)
fonts, texts, edges, nodes = {}, [], [], []
svg = [f'<svg xmlns="http://www.w3.org/2000/svg" width="{W}" height="{H}" viewBox="0 0 {W} {H}" role="img" aria-labelledby="title description">',
       '<title id="title">SponsorFlow Level 1 Data Flow Diagram</title>',
       '<desc id="description">Eight processes, five external actors, and ten logical data stores. Each flow has one arrowhead at its recipient. Repeated actor and store symbols refer to the same entity or store.</desc>',
       '<rect width="100%" height="100%" fill="white"/>']

def font(size, bold=False):
    key = (size, bold)
    if key not in fonts:
        fonts[key] = ImageFont.truetype(FONTS[bold], round(size*SCALE))
    return fonts[key]

def length(s, size, bold=False):
    return draw.textlength(s, font=font(size, bold))/SCALE

def wrap(s, maxw, size, bold=False):
    result, current = [], ""
    for word in s.split():
        candidate = (current + " " + word).strip()
        if current and length(candidate, size, bold) > maxw:
            result.append(current)
            current = word
        else:
            current = candidate
    if current:
        result.append(current)
    assert all(length(t, size, bold) <= maxw for t in result), (s, maxw)
    return result

def text(x, top, s, size=24, bold=False, center=False, color=INK, maxw=None):
    tw = length(s, size, bold)
    if maxw is not None:
        assert tw <= maxw, (s, tw, maxw)
    left = x-tw/2 if center else x
    assert 0 <= left and left+tw <= W and top >= 0 and top+size*1.25 < H, s
    draw.text((round(left*SCALE), round(top*SCALE)), s, fill=color, font=font(size,bold), anchor="lt")
    svg.append(f'<text x="{x}" y="{top}" font-family="Arial, sans-serif" font-size="{size}" font-weight="{700 if bold else 400}" text-anchor="{"middle" if center else "start"}" dominant-baseline="text-before-edge" fill="{color}">{escape(s)}</text>')
    texts.append({"text": s, "bounds": [left, top, left+tw, top+size*1.2]})

def multiline(x, cy, s, maxw, size=24, bold=False, color=INK):
    lines = wrap(s, maxw, size, bold)
    leading = size * 1.28
    top = cy-len(lines)*leading/2
    for i, label in enumerate(lines):
        text(x, top+i*leading, label, size, bold, True, color, maxw)
    return len(lines)*leading

def line(points, color=ORANGE, width=2):
    draw.line([(round(x*SCALE),round(y*SCALE)) for x,y in points], fill=color, width=max(1,round(width*SCALE)))
    svg.append('<polyline points="'+' '.join(f'{x},{y}' for x,y in points)+f'" fill="none" stroke="{color}" stroke-width="{width}"/>')

def rect(x,y,w,h,fill,stroke=ORANGE,r=0,width=2):
    bounds = [round(v*SCALE) for v in (x,y,x+w,y+h)]
    draw.rounded_rectangle(bounds, radius=round(r*SCALE), fill=fill, outline=stroke, width=max(1,round(width*SCALE)))
    svg.append(f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="{r}" fill="{fill}" stroke="{stroke}" stroke-width="{width}"/>')

def arrow(x1,cy,x2,label,source,target,flow):
    assert x1 != x2
    line([(x1,cy),(x2,cy)],width=2.4)
    sign = 1 if x2>x1 else -1
    tri = [(x2,cy),(x2-sign*13,cy-6),(x2-sign*13,cy+6)]
    draw.polygon([(round(x*SCALE),round(y*SCALE)) for x,y in tri],fill=ORANGE)
    svg.append('<polygon points="'+' '.join(f'{x},{y}' for x,y in tri)+f'" fill="{ORANGE}"/>')
    available = abs(x2-x1)-34
    labels = wrap(label, available, 24)
    assert len(labels) <= 2, label
    leading = 31
    top = cy-9-len(labels)*leading
    for i,s in enumerate(labels):
        text((x1+x2)/2,top+i*leading,s,24,center=True,maxw=available)
    edges.append({"source":source,"target":target,"data":label,"arrowheads":1,"points":[[x1,cy],[x2,cy]],"direction":flow["direction"]})

text(60,28,"SponsorFlow — Level 1 Data Flow Diagram",48,True)
text(60,92,"Decomposition of process 0  |  Gane–Sarson notation  |  Manual decisions and controlled handoff",25,color=MUTED)
text(STORE_X,164,"LOGICAL DATA STORES",25,True)
text(PROC_X+PROC_W/2,164,"PROCESSES",25,True,True)
text(ACTOR_X,164,"EXTERNAL ENTITIES",25,True)

page_bounds = []
for index,(p,top,height,stores,actors) in enumerate(layout):
    bandfill = "#FAFBFC" if index%2 else "#FFFFFF"
    rect(25,top,W-50,height,bandfill,"#E3E7EB",0,1)
    proc_top, proc_h = top+18,height-36
    rect(PROC_X,proc_top,PROC_W,proc_h,PROCESS_FILL,r=24,width=2.4)
    pc = proc_top+proc_h/2
    title_lines = wrap(p["name"], PROC_W-54, 31, True)
    title_h = len(title_lines)*40
    text(PROC_X+PROC_W/2,pc-title_h/2-48,p["id"],32,True,True)
    for j,label in enumerate(title_lines):
        text(PROC_X+PROC_W/2,pc-title_h/2+j*40,label,31,True,True,maxw=PROC_W-54)
    nodes.append({"id":p["id"],"kind":"process","bounds":[PROC_X,proc_top,PROC_X+PROC_W,proc_top+proc_h]})

    sy = top+(height-group_height(stores))/2
    for sid,flows in stores.items():
        group_h = len(flows)*PITCH
        bt,bh = sy+4,group_h-8
        # Open-right rectangle; left identifier partition is part of the data-store symbol.
        line([(STORE_X+STORE_W,bt),(STORE_X,bt),(STORE_X,bt+bh),(STORE_X+STORE_W,bt+bh)],width=2.2)
        line([(STORE_X+80,bt),(STORE_X+80,bt+bh)],width=1.6)
        multiline(STORE_X+40,bt+bh/2,sid,66,25,True)
        multiline(STORE_X+80+(STORE_W-80)/2,bt+bh/2,data["stores"][sid],STORE_W-112,24,True)
        nodes.append({"id":sid,"process":p["id"],"kind":"store","bounds":[STORE_X,bt,STORE_X+STORE_W,bt+bh]})
        for j,f in enumerate(flows):
            cy = sy+j*PITCH+PITCH-18
            assert bt < cy < bt+bh and proc_top < cy < proc_top+proc_h
            if f["direction"] == "in":
                arrow(STORE_X+STORE_W,cy,PROC_X,f["data"],sid,p["id"],f)
            else:
                arrow(PROC_X,cy,STORE_X+STORE_W,f["data"],p["id"],sid,f)
        sy += group_h+GROUP_GAP

    ey = top+(height-group_height(actors))/2
    for entity,flows in actors.items():
        group_h = len(flows)*PITCH
        bt,bh = ey+4,group_h-8
        rect(ACTOR_X,bt,ACTOR_W,bh,ENTITY_FILL)
        multiline(ACTOR_X+ACTOR_W/2,bt+bh/2,entity,ACTOR_W-40,29,True)
        nodes.append({"id":entity,"process":p["id"],"kind":"entity","bounds":[ACTOR_X,bt,ACTOR_X+ACTOR_W,bt+bh]})
        for j,f in enumerate(flows):
            cy = ey+j*PITCH+PITCH-18
            assert bt < cy < bt+bh and proc_top < cy < proc_top+proc_h
            if f["direction"] == "in":
                arrow(ACTOR_X,cy,PROC_X+PROC_W,f["data"],entity,p["id"],f)
            else:
                arrow(PROC_X+PROC_W,cy,ACTOR_X,f["data"],p["id"],entity,f)
        ey += group_h+GROUP_GAP
    page_bounds.append((top,top+height))

text(60,H-148,"Arrowheads point to data recipients. Repeated symbols denote the same actor or logical store; stores may group physical tables.",24,color=MUTED)
text(60,H-107,"Accounting access requires SLE-FHE verification, individual Approved/Ongoing status, Sponsor confirmation and explicit FASSG forwarding.",24,color=MUTED)
text(W/2,H-55,"Figure 3.X. SponsorFlow Level 1 Data Flow Diagram",28,True,True)
svg.append("</svg>")
svg_text = "\n".join(svg)
tree = ET.fromstring(svg_text)
assert len(tree.findall("{http://www.w3.org/2000/svg}polygon")) == len(edges)
expected_edges = sum(len(p["store_flows"])+len(p["external_flows"]) for p in processes)
assert len(edges) == expected_edges
for e in edges:
    assert (e["source"] in by_id) != (e["target"] in by_id)
    assert e["arrowheads"] == 1

# Reject overlapping label rectangles. Each connection is horizontal, with one label above it.
overlaps = []
for i,a in enumerate(texts):
    ax1,ay1,ax2,ay2 = a["bounds"]
    for b in texts[i+1:]:
        bx1,by1,bx2,by2 = b["bounds"]
        if min(ax2,bx2)>max(ax1,bx1)+1 and min(ay2,by2)>max(ay1,by1)+1:
            overlaps.append([a["text"],b["text"]])
assert not overlaps, overlaps

base = OUT / "SponsorFlow_Level_1_Corrected"
Path(str(base)+".svg").write_text(svg_text,encoding="utf-8")
im.save(str(base)+".png",dpi=(300,300))
im.save(str(base)+".pdf",resolution=300)
preview = im.copy()
preview.thumbnail((1800,2400),Image.Resampling.LANCZOS)
preview.save(OUT/"SponsorFlow_Level_1_Preview.png")

# Two-page PDF for readable manuscript insertion (1.0–4.0, then 5.0–8.0).
split = (page_bounds[3][1]+page_bounds[4][0])/2
pages = []
for start,stop,label in [(0,split,"Part 1"),(split,H,"Part 2")]:
    part=im.crop((0,round(start*SCALE),im.width,round(stop*SCALE)))
    part.save(OUT/f"SponsorFlow_Level_1_{label.replace(' ','_')}.png",dpi=(300,300))
    pages.append(part)
pages[0].save(OUT/"SponsorFlow_Level_1_Two_Pages.pdf",save_all=True,append_images=pages[1:],resolution=220)

data["direction_convention"] = "in = store/entity to process; out = process to store/entity"
(OUT/"DFD_Flow_Definitions.json").write_text(json.dumps(data,indent=2,ensure_ascii=False),encoding="utf-8")
(OUT/"Rendered_Connections.json").write_text(json.dumps(edges,indent=2,ensure_ascii=False),encoding="utf-8")
source_files = ["app/Http/Controllers/Sponsor/ApprovalUploadController.php","app/Http/Controllers/Fassg/GeneratedBatchController.php","app/Models/SponsorshipProgram.php"]
validation={"processes":len(processes),"external_entities":len(ENTITIES),"logical_data_stores":len(data["stores"]),"flows":len(edges),"one_recipient_arrowhead_per_flow":True,"required_read_and_update_flows":True,"text_overlaps":len(overlaps),"svg_xml_valid":True,"png_dimensions":list(im.size),"application_bootstrap":False,"database_access":False,"source_sha256":{f:hashlib.sha256((ROOT/f).read_bytes()).hexdigest() for f in source_files}}
(OUT/"Validation.json").write_text(json.dumps(validation,indent=2),encoding="utf-8")
print(json.dumps(validation,indent=2))
