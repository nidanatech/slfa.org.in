"""Render editorial layout concepts for review; these are not browser captures."""
from pathlib import Path
from PIL import Image, ImageDraw, ImageFont

root = Path(__file__).resolve().parent.parent
out = root / 'design-previews'
W, H = 1440, 1000
navy, gold, paper, ink = '#0b101c', '#d4af37', '#f5f1e9', '#111827'
serif = '/usr/share/fonts/truetype/dejavu/DejaVuSerif.ttf'
serif_b = '/usr/share/fonts/truetype/dejavu/DejaVuSerif-Bold.ttf'
sans = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'
sans_b = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'
def f(path, size): return ImageFont.truetype(path,size)
def text(d,xy,value,size,color,face=sans): d.text(xy,value,font=f(face,size),fill=color)
def cover(im,path,box,shade=.55):
    x,y,x2,y2=box; src=Image.open(root/path).convert('RGB'); w,h=x2-x,y2-y
    scale=max(w/src.width,h/src.height); src=src.resize((int(src.width*scale),int(src.height*scale)))
    a=(src.width-w)//2;b=(src.height-h)//2;src=src.crop((a,b,a+w,b+h))
    src=Image.blend(src,Image.new('RGB',(w,h),navy),shade);im.paste(src,(x,y))
def header(im,active):
    d=ImageDraw.Draw(im);d.rectangle((0,0,W,85),fill=navy)
    crest=Image.open(root/'public/images/favvicon.png').convert('RGBA').resize((50,50))
    im.paste(crest,(78,18),crest);text(d,(138,20),'SLFA',31,'#f79236',sans_b)
    x=564
    for link in ['HOME','ABOUT','MEMBERSHIP','NEWS & EVENTS','GALLERY','CONTACT']:
        text(d,(x,36),link,11,gold if link==active else '#d0d2d7',sans_b)
        x+=d.textlength(link,font=f(sans_b,11))+27
    d.line((0,84,W,84),fill='#3c342a',width=1)
def about():
    im=Image.new('RGB',(W,H),paper);cover(im,'public/images/sigiriya-rock.jpg',(0,85,W,600),.62);header(im,'ABOUT');d=ImageDraw.Draw(im)
    text(d,(80,154),'SRI LANKAN FRIENDSHIP ASSOCIATION OF SOUTH INDIA',12,gold,sans_b)
    text(d,(75,214),'A community built on',60,'white',serif_b)
    text(d,(75,292),'connection',69,gold,serif)
    text(d,(80,401),'Friendship, cultural gatherings, and mutual support',19,'#e4e6eb')
    text(d,(80,434),'for people of Sri Lankan origin in South India.',19,'#e4e6eb')
    d.line((80,521,247,521),fill=gold,width=2);text(d,(80,534),'DISCOVER OUR PURPOSE  →',12,gold,sans_b)
    d.rectangle((0,600,W,H),fill=paper)
    text(d,(80,666),'WHY WE EXIST',12,'#9a7714',sans_b)
    text(d,(75,707),'Friendship with',42,ink,serif_b)
    text(d,(75,765),'purpose',49,'#800000',serif)
    d.line((720,676,1360,676),fill='#b7a676',width=2)
    text(d,(720,694),'OUR MISSION',12,'#9a7714',sans_b)
    text(d,(720,729),'Bring people together',28,ink,serif_b)
    text(d,(720,779),'Meet, preserve cultural ties, and support one another.',16,'#4b5563')
    d.line((720,842,1360,842),fill='#b7a676',width=2)
    text(d,(720,859),'OUR VISION',12,'#9a7714',sans_b)
    text(d,(720,894),'Keep connections growing',27,ink,serif_b)
    text(d,(720,944),'Lasting relationships across generations.',16,'#4b5563')
    im.save(out/'about-plan.png')
def happenings():
    im=Image.new('RGB',(W,H),navy);cover(im,'public/images/hero-ocean-bg-slfa.jpg',(0,85,W,510),.58);header(im,'NEWS & EVENTS');d=ImageDraw.Draw(im)
    text(d,(80,149),'THE COMMUNITY, IN MOTION',12,gold,sans_b)
    text(d,(75,215),'News & Events',68,'white',serif_b)
    text(d,(80,325),'Gatherings ahead, stories from the association,',19,'#e4e6eb')
    text(d,(80,357),'and moments worth remembering.',19,'#e4e6eb')
    d.line((80,445,240,445),fill=gold,width=2);text(d,(80,455),'UPCOMING EVENTS  ↓',12,gold,sans_b)
    d.rectangle((0,510,W,810),fill=navy)
    text(d,(80,546),'COME TOGETHER',12,gold,sans_b)
    text(d,(75,578),'Upcoming events',42,'white',serif_b)
    d.line((80,667,1360,667),fill='#b7984b',width=2)
    cover(im,'public/images/cta/gallery27.jpg',(780,682,1360,785),.42)
    text(d,(80,691),'NEXT GATHERING',12,gold,sans_b)
    text(d,(80,724),'The next gathering is being planned',27,'white',serif_b)
    text(d,(80,766),'Dates and RSVP details will appear when announced.',15,'#cbd5e1')
    d.rectangle((0,810,W,H),fill=paper)
    text(d,(80,849),'FROM THE ASSOCIATION',12,'#9a7714',sans_b)
    text(d,(75,883),'News & activities',40,ink,serif_b)
    d.line((80,956,1360,956),fill='#b7a676',width=2)
    im.save(out/'news-events-plan.png')
about();happenings()
