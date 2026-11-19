from PIL import Image, ImageDraw, ImageFont
from pathlib import Path

root = Path(__file__).resolve().parent.parent
out = root / 'design-previews'
W, H = 1440, 1000
navy = '#0b101c'; gold = '#d4af37'; paper = '#f5f1e9'; ink = '#101827'
serif = '/usr/share/fonts/truetype/dejavu/DejaVuSerif.ttf'
serif_b = '/usr/share/fonts/truetype/dejavu/DejaVuSerif-Bold.ttf'
sans = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'
sans_b = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'
def font(path, size): return ImageFont.truetype(path, size)
def text(draw, xy, value, size, color, face=sans, **kw): draw.text(xy, value, font=font(face,size), fill=color, **kw)
def panel(draw, box, fill, outline=None, radius=26): draw.rounded_rectangle(box, radius, fill, outline=outline, width=1)
def background(image, name, top=85, bottom=520):
    source = Image.open(root / 'public' / 'images' / name).convert('RGB')
    source.thumbnail((W, bottom-top))
    crop = Image.open(root / 'public' / 'images' / name).convert('RGB')
    scale = max(W/crop.width,(bottom-top)/crop.height)
    crop = crop.resize((int(crop.width*scale),int(crop.height*scale)))
    left=(crop.width-W)//2; up=(crop.height-(bottom-top))//2
    crop=crop.crop((left,up,left+W,up+bottom-top))
    overlay=Image.new('RGB',crop.size,navy)
    crop=Image.blend(crop,overlay,.68)
    image.paste(crop,(0,top))
def header(im, active):
    d=ImageDraw.Draw(im);d.rectangle((0,0,W,85),fill=navy)
    flag=Image.open(root/'public/images/slfa-bg.png').convert('RGB').resize((46,46))
    im.paste(flag,(80,20));d.ellipse((78,18,128,68),outline=gold,width=2)
    text(d,(137,20),'SLFA',31,'#f79236',sans_b)
    links=['HOME','ABOUT','MEMBERSHIP','NEWS & EVENTS','GALLERY','CONTACT']
    x=550
    for link in links:
        text(d,(x,36),link,11,gold if link==active else '#d0d2d7',sans_b)
        x+=d.textlength(link,font=font(sans_b,11))+29

def preview_about():
    im=Image.new('RGB',(W,H),paper);background(im,'sigiriya-rock.jpg');header(im,'ABOUT');d=ImageDraw.Draw(im)
    text(d,(80,148),'ABOUT THE ASSOCIATION',13,gold,sans_b)
    text(d,(76,201),'Friendship across',69,'white',serif_b)
    text(d,(76,282),'borders',69,gold,serif)
    text(d,(80,385),'A community for friendship, cultural connection,',20,'#e2e4ea')
    text(d,(80,417),'and mutual support across South India.',20,'#e2e4ea')
    panel(d,(80,464,308,515),gold,radius=25);text(d,(103,481),'EXPLORE MEMBERSHIP',12,navy,sans_b)
    panel(d,(894,250,1355,475),'#111825',outline='#66645c',radius=28)
    text(d,(930,280),'OUR STORY',12,gold,sans_b)
    text(d,(930,322),'A place to meet, celebrate,',23,'white',serif_b)
    text(d,(930,356),'and look out for one another.',23,'white',serif_b)
    text(d,(930,417),'Community • Culture • Connection',14,'#c8c9cb')
    d.rectangle((0,520,W,H),fill=paper)
    text(d,(80,593),'WHY WE EXIST',13,'#9a7714',sans_b)
    text(d,(78,628),'A community with purpose',46,ink,serif_b)
    for x,label,title,lines in [(80,'01 / MISSION','Bring people together',['Meet, preserve cultural ties, and support','one another in South India.']),(730,'02 / VISION','Keep connections growing',['Lasting social, cultural, and professional','relationships across generations.'])]:
        panel(d,(x,714,x+625,950),'#ffffff',outline='#d8d0c3')
        text(d,(x+36,750),label,12,'#9a7714',sans_b)
        text(d,(x+36,801),title,27,ink,serif_b)
        for i,line in enumerate(lines):text(d,(x+36,863+i*28),line,16,'#5f6874')
    im.save(out/'about-plan.png')

def preview_happenings():
    im=Image.new('RGB',(W,H),navy);background(im,'hero-ocean-bg-slfa.jpg',bottom=470);header(im,'NEWS & EVENTS');d=ImageDraw.Draw(im)
    text(d,(80,143),'THE COMMUNITY, IN MOTION',13,gold,sans_b)
    text(d,(76,203),'News & Events',73,'white',serif_b)
    text(d,(80,315),'Gatherings ahead, stories from our members, and',20,'#e2e4ea')
    text(d,(80,347),'moments worth remembering.',20,'#e2e4ea')
    panel(d,(80,404,284,455),gold,radius=25);text(d,(107,421),'EXPLORE EVENTS',12,navy,sans_b)
    d.rectangle((0,470,W,790),fill=navy)
    text(d,(80,510),'COME TOGETHER',13,gold,sans_b)
    text(d,(78,544),'Upcoming events',45,'white',serif_b)
    panel(d,(80,615,1360,754),'#211b16',outline=gold,radius=28)
    panel(d,(112,640,277,670),gold,radius=15);text(d,(130,648),'NEXT GATHERING',11,navy,sans_b)
    text(d,(112,683),'The next gathering is being planned',26,'white',serif_b)
    text(d,(112,724),'Dates and RSVP details will appear here once announced.',15,'#cdd0d5')
    d.rectangle((0,790,W,H),fill=paper)
    text(d,(80,835),'FROM THE ASSOCIATION',13,'#9a7714',sans_b)
    text(d,(78,871),'Latest news',43,ink,serif_b)
    text(d,(80,937),'Community stories and announcements appear below.',17,'#5f6874')
    im.save(out/'news-events-plan.png')

preview_about();preview_happenings()
