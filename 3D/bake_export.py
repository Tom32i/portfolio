"""
Bake scene1.blend for the web and export public/3d/scene.glb.

  ATLAS=2048 FLOOR=1024 SAMPLES=1024 \
  /Applications/Blender.app/Contents/MacOS/Blender --background scene1.blend --python bake_export.py

scene1.blend is never modified: the baked state is saved to scene1_baked.blend.
Output contract (see the portfolio one-pager handoff):
  - root meshes: laptop, coffee_cup, gamepad, longboard, plant  (baked colour+light atlas, sRGB)
  - lid_display: child of laptop, flat #8ed9cf, no map (render unlit)
  - floor: 2x2 m, RGBA map = lighting factor (white = page coral) with radial alpha fade
  - Y-up, metres, un-rotated layout (viewer applies the -0.35 rad rest angle to objects AND floor)
"""
import bpy, bmesh, os, math, numpy as np
from mathutils import Vector, Matrix

HERE = os.path.dirname(bpy.data.filepath)
ATLAS = int(os.environ.get("ATLAS", "2048"))
FLOOR_RES = int(os.environ.get("FLOOR", "1024"))
SAMPLES = int(os.environ.get("SAMPLES", "1024"))
GLB = os.environ.get("GLB", os.path.join(HERE, "..", "public", "3d", "scene.glb"))
BLEND_OUT = os.environ.get("BLEND_OUT", os.path.join(HERE, "scene1_baked.blend"))
TEX_DIR = os.path.join(os.path.dirname(BLEND_OUT), "baked")
GROUPS = ["laptop", "coffee_cup", "gamepad", "longboard", "plant"]
SCREEN = "lid_display"
LEAF_THICKNESS = 0.0015

sc = bpy.context.scene
vl = bpy.context.view_layer
D = bpy.data

def hex_lin(h):
    h = h.lstrip('#'); c = [int(h[i:i+2], 16)/255 for i in (0, 2, 4)]
    return tuple((v/12.92 if v <= 0.04045 else ((v+0.055)/1.055)**2.4) for v in c)

def select_only(objs, active=None):
    for o in objs: o.hide_select = False           # e.g. the floor is locked in the source file
    for o in vl.objects: o.select_set(False)
    for o in objs: o.select_set(True)
    vl.objects.active = active or (objs[0] if objs else None)

# ---------------------------------------------------------------- render device
sc.render.engine = 'CYCLES'
try:
    prefs = bpy.context.preferences.addons['cycles'].preferences
    prefs.compute_device_type = 'METAL'; prefs.get_devices()
    for d in prefs.devices: d.use = True
    sc.cycles.device = 'GPU'
except Exception as e:
    print("GPU unavailable, using CPU:", e)
sc.cycles.samples = SAMPLES
sc.cycles.use_denoising = False

# ---------------------------------------------------------------- 1. leaves: give them thickness
dg = bpy.context.evaluated_depsgraph_get()
for o in [o for o in D.objects if o.name.startswith("monstera_leaf")]:
    m = o.modifiers.new("thickness", 'SOLIDIFY'); m.thickness = LEAF_THICKNESS; m.offset = 0
    dg.update()
    o.data = D.meshes.new_from_object(o.evaluated_get(dg)); o.modifiers.clear()

# ---------------------------------------------------------------- 2. one mesh per group
screen = D.objects[SCREEN]
screen_mw = screen.matrix_world.copy()
merged = {}
part_counter = [0]
for g in GROUPS:
    grp = D.objects[g]
    meshes = [o for o in grp.children_recursive if o.type == 'MESH' and o.name != SCREEN]
    for o in meshes:                                  # single-user data so join never touches shared meshes
        if o.data.users > 1: o.data = o.data.copy()
    for k, o in enumerate(meshes):                    # remember the source part of every face (for UV welding)
        a = o.data.attributes.get("part") or o.data.attributes.new("part", 'INT', 'FACE')
        a.data.foreach_set("value", np.full(len(o.data.polygons), part_counter[0] + k, np.int32))
    part_counter[0] += len(meshes)
    act = meshes[0]
    with bpy.context.temp_override(active_object=act, selected_objects=meshes,
                                   selected_editable_objects=meshes, object=act):
        bpy.ops.object.join()
    mw = act.matrix_world.copy(); act.parent = None; act.matrix_world = mw
    # bake world transform into vertices, origin at bottom-centre of the group
    act.data.transform(mw); act.matrix_world = Matrix.Identity(4)
    vs = np.array([v.co[:] for v in act.data.vertices])
    origin = Vector(((vs[:, 0].min()+vs[:, 0].max())/2, (vs[:, 1].min()+vs[:, 1].max())/2, vs[:, 2].min()))
    act.data.transform(Matrix.Translation(-origin)); act.location = origin
    merged[g] = act
# drop every empty of the old hierarchy, then take the group names
for o in [o for o in D.objects if o.type == 'EMPTY']: D.objects.remove(o)
for g, o in merged.items(): o.name = g; o.data.name = g
screen.parent = merged["laptop"]; screen.matrix_world = screen_mw

objs = list(merged.values())
print("merged:", {g: (len(o.data.vertices), len(o.data.polygons), len(o.data.materials)) for g, o in merged.items()})

# ---------------------------------------------------------------- 2b. cull faces no site camera can ever see
# Every site camera looks down at ~41° (direction (0, .66, .75) in three.js) from any azimuth
# (turntable / turning icons), ±0.06 rad pointer tilt. A face that is back-facing for all of them
# (bottoms, undersides) is dropped: on a closed mesh it is always hidden behind front faces anyway.
# The dropped faces stay as render-only occluders during the bakes, so shadows and bounce light
# are unchanged, and are deleted before export.
CULL = os.environ.get("CULL", "1") == "1"
CULL_MARGIN = math.sin(math.radians(float(os.environ.get("CULL_MARGIN_DEG", "3"))))
occluders = []
def camera_positions():
    """(points, directions): finite camera positions + directions at infinity (orthographic limit),
    Blender frame (Z up). Back-facing test is linear in distance along a ray, so both ends suffice."""
    base = math.atan2(0.66, 0.75)
    elevs = [base - 0.07, base, base + 0.07]
    azis = np.radians(np.arange(0, 360, 2))
    dirs = np.array([(math.cos(e)*math.sin(a), -math.cos(e)*math.cos(a), math.sin(e)) for e in elevs for a in azis])
    allv = np.concatenate([np.array([v.co[:] for v in o.data.vertices]) + np.array(o.location[:]) for o in objs])
    lo, hi = allv.min(0), allv.max(0); size = hi - lo
    half = math.radians(28) / 2
    pts = []
    # hero: pivot = bottom-centre of the layout box, closest fit = vertical fov, zoom 0.66
    R = max(math.hypot(size[0], size[1]) / 2, size[2] * 0.8)
    pivot = np.array([(lo[0]+hi[0])/2, (lo[1]+hi[1])/2, lo[2]])
    pts.append(pivot + dirs * (R / math.sin(half) * 0.66))
    # icons: each object alone, sphere fit around its own box centre
    for o in objs:
        v = np.array([v.co[:] for v in o.data.vertices]) + np.array(o.location[:])
        l, h = v.min(0), v.max(0); s = h - l
        r = math.hypot(math.hypot(s[0], s[1]) / 2, s[2] / 2)
        pts.append((l + h) / 2 + dirs * (r / math.sin(half)))
    return np.concatenate(pts), dirs
def bvh_of(obs):
    from mathutils.bvhtree import BVHTree
    verts, polys, owner = [], [], []
    for o in obs:
        off = len(verts); loc = o.location
        verts += [v.co + loc for v in o.data.vertices]
        polys += [[off + i for i in p.vertices] for p in o.data.polygons]
        owner += [(o.name, p.index) for p in o.data.polygons]
    return BVHTree.FromPolygons(verts, polys), owner
def seen(o, fi, cams, bvh, owner):
    """True if a ray from any camera reaches face fi of o first (a back face still visible: inverted
    normals, open shells)."""
    me = o.data; loc = o.location; p = me.polygons[fi]
    c = p.center + loc; vs = [me.vertices[i].co + loc for i in p.vertices]
    samples = [c] + [c.lerp(v, 0.8) for v in vs]
    for cam in cams:
        cam = Vector(cam)
        for s in samples:
            d = s - cam; dist = d.length
            hit, _, idx, hd = bvh.ray_cast(cam, d / dist, dist + 1e-4)
            if hit is None or owner[idx] == (o.name, fi) or hd > dist - 1e-5: return True
    return False
if CULL:
    cam_pts, cam_dirs = camera_positions()
    n_az = len(cam_dirs)                              # rows per camera set in cam_pts: hero, then one per object
    scene_bvh = bvh_of(objs)
    stats = {}
    for o in objs:
        me = o.data
        loc = np.array(o.location[:])
        nrm = np.empty(len(me.polygons)*3, np.float32); me.polygons.foreach_get("normal", nrm); nrm = nrm.reshape(-1, 3)
        co = np.empty(len(me.vertices)*3, np.float32); me.vertices.foreach_get("co", co); co = co.reshape(-1, 3) + loc
        cen = np.empty(len(me.polygons)*3, np.float32); me.polygons.foreach_get("center", cen); cen = cen.reshape(-1, 3) + loc
        area = np.empty(len(me.polygons), np.float32); me.polygons.foreach_get("area", area)
        # orthographic limit
        vis = (nrm @ cam_dirs.T > -CULL_MARGIN).any(1)
        # finite cameras: test the face plane (any point of a planar face gives the same sign)
        for i in np.where(~vis)[0]:
            d = cam_pts - cen[i]; d /= np.linalg.norm(d, axis=1, keepdims=True)
            if (d @ nrm[i] > -CULL_MARGIN).any(): vis[i] = True
        # back-facing everywhere, but maybe still in sight (inverted normals): ray test from the cameras
        k = objs.index(o)
        hero_cams = cam_pts[:n_az][::3]
        icon_cams = cam_pts[(k+1)*n_az:(k+2)*n_az][::3]
        own_bvh = bvh_of([o])
        rescued = 0
        for i in np.where(~vis)[0]:
            if seen(o, int(i), hero_cams, *scene_bvh) or seen(o, int(i), icon_cams, *own_bvh):
                vis[i] = True; rescued += 1
        print(f"  {o.name}: {rescued} back faces kept, still visible", flush=True)
        drop = np.where(~vis)[0]
        stats[o.name] = (len(drop), len(me.polygons), round(float(area[drop].sum() / max(area.sum(), 1e-9)) * 100, 1))
        if not len(drop): continue
        # split: dropped faces → occluder object, kept faces stay in o
        occ = o.copy(); occ.data = me.copy(); occ.name = o.name + "__occluder"; sc.collection.objects.link(occ)
        for ob, remove in ((o, drop), (occ, np.where(vis)[0])):
            bm = bmesh.new(); bm.from_mesh(ob.data); bm.faces.ensure_lookup_table()
            bmesh.ops.delete(bm, geom=[bm.faces[i] for i in remove], context='FACES')
            bm.to_mesh(ob.data); bm.free()
        occluders.append(occ)
    print("culled faces (dropped, total, % area):", stats)
if os.environ.get("CULL_ONLY") == "1": raise SystemExit

# ---------------------------------------------------------------- 3. UVs: one shared atlas
# The meshes are "triangle soup" (every face has its own vertices, custom normals keep the
# shading), so unwrapping them directly makes one island per face, most of them sub-pixel.
# Unwrap welded copies instead (real islands), then copy the UVs back corner by corner:
# the shipped geometry and its normals stay untouched.
for o in objs:
    while o.data.uv_layers: o.data.uv_layers.remove(o.data.uv_layers[0])
    o.data.uv_layers.new(name="UVMap")
proxies = {}
for o in objs:
    me = o.data.copy(); me.name = o.name + "__uvproxy"
    fid = me.attributes.new("fid", 'INT', 'FACE'); fid.data.foreach_set("value", np.arange(len(me.polygons), dtype=np.int32))
    bm = bmesh.new(); bm.from_mesh(me)
    part = bm.faces.layers.int.get("part")
    by_part = {}
    for f in bm.faces: by_part.setdefault(f[part], set()).update(f.verts)
    for vs in by_part.values():                   # weld inside each source part only: touching parts stay separate islands
        bmesh.ops.remove_doubles(bm, verts=list(vs), dist=1e-5)
    bm.to_mesh(me); bm.free()
    p = D.objects.new(me.name, me); sc.collection.objects.link(p); p.matrix_world = o.matrix_world
    proxies[o.name] = p
pobjs = list(proxies.values())
select_only(pobjs)
sc.tool_settings.use_uv_select_sync = True      # headless: otherwise no UV counts as selected
bpy.ops.object.mode_set(mode='EDIT')
bpy.ops.mesh.select_all(action='SELECT')
bpy.ops.uv.smart_project(angle_limit=math.radians(float(os.environ.get("UV_ANGLE", "66"))), island_margin=8/ATLAS,
                         area_weight=0.0, correct_aspect=True, scale_to_bounds=False)
bpy.ops.uv.pack_islands(rotate=True, margin=8/ATLAS, shape_method='CONCAVE')
bpy.ops.object.mode_set(mode='OBJECT')
missing = {}
for o in objs:
    pm = proxies[o.name].data; om = o.data
    puv = pm.uv_layers.active.data; ouv = om.uv_layers.active.data
    fids = np.empty(len(pm.polygons), np.int32); pm.attributes["fid"].data.foreach_get("value", fids)
    by_face = {}
    for pf, f in zip(pm.polygons, fids):
        by_face[int(f)] = {tuple(np.round(pm.vertices[pm.loops[li].vertex_index].co, 5)): puv[li].uv.copy() for li in pf.loop_indices}
    miss = 0
    for f in om.polygons:
        m = by_face.get(f.index)
        for li in f.loop_indices:
            key = tuple(np.round(om.vertices[om.loops[li].vertex_index].co, 5))
            if m and key in m: ouv[li].uv = m[key]
            else:
                # zero-area face (collapsed by the weld): give it a single uv point so it can't paint
                # over the atlas. New UV layers default to a full 0..1 square per face!
                miss += 1
                ouv[li].uv = next(iter(m.values())) if m else (0.0, 0.0)
        if not m:
            for li in f.loop_indices: ouv[li].uv = (0.0, 0.0)
    missing[o.name] = miss
print("uv transfer, corners without exact match:", missing)
def uv_report():
    boxes = {}
    for o in objs:
        uv = np.empty(len(o.data.loops)*2, np.float32); o.data.uv_layers.active.data.foreach_get("uv", uv); uv = uv.reshape(-1, 2)
        boxes[o.name] = (uv.min(0).round(3).tolist(), uv.max(0).round(3).tolist())
    return boxes
def uv_overlap(res=ATLAS):
    """pixels touched by faces of more than one object (sampled at triangle centroids + vertices)"""
    owner = np.full((res, res), -1, np.int32); clash = np.zeros((res, res), bool)
    for k, o in enumerate(objs):
        me = o.data; me.calc_loop_triangles()
        uv = np.empty(len(me.loops)*2, np.float32); me.uv_layers.active.data.foreach_get("uv", uv); uv = uv.reshape(-1, 2)
        tl = np.empty(len(me.loop_triangles)*3, np.int32); me.loop_triangles.foreach_get("loops", tl); tri = uv[tl.reshape(-1, 3)]
        pts = np.concatenate([tri.mean(1), tri[:, 0]*.6+tri[:, 1]*.2+tri[:, 2]*.2, tri[:, 1]*.6+tri[:, 0]*.2+tri[:, 2]*.2, tri[:, 2]*.6+tri[:, 0]*.2+tri[:, 1]*.2])
        px = np.clip((pts*res).astype(int), 0, res-1)
        cur = owner[px[:, 1], px[:, 0]]
        clash[px[:, 1], px[:, 0]] |= (cur != -1) & (cur != k)
        owner[px[:, 1], px[:, 0]] = k
    return int(clash.sum()), int((owner >= 0).sum())
print("uv overlap px / used px:", uv_overlap())
def island_stats(res=ATLAS):
    from bpy_extras.bmesh_utils import bmesh_linked_uv_islands
    out = {}
    for o in pobjs:
        bm = bmesh.new(); bm.from_mesh(o.data); uvl = bm.loops.layers.uv.active
        areas = []
        for isl in bmesh_linked_uv_islands(bm, uvl):
            a = 0
            for f in isl:
                uv = [l[uvl].uv for l in f.loops]
                a += 0.5*abs(sum(uv[i].x*uv[i-1].y - uv[i-1].x*uv[i].y for i in range(len(uv))))
            areas.append(a*res*res)
        a = np.array(areas); bm.free()
        out[o.name] = dict(islands=len(a), under16px=int((a < 16).sum()), median=round(float(np.median(a)), 1))
    return out
print("islands:", island_stats())
for p in pobjs:
    me = p.data; D.objects.remove(p); D.meshes.remove(me)
if os.environ.get("UV_ONLY") == "1": raise SystemExit

# ---------------------------------------------------------------- 4. bake objects: diffuse colour x (direct + indirect)
atlas = D.images.new("baked_objects", ATLAS, ATLAS, alpha=False)
mats = {m for o in objs for m in o.data.materials if m}
bake_nodes = []
for m in mats:
    n = m.node_tree.nodes.new("ShaderNodeTexImage"); n.image = atlas; n.name = "__bake"
    m.node_tree.nodes.active = n; bake_nodes.append((m, n))
bk = sc.render.bake
bk.use_pass_direct = bk.use_pass_indirect = bk.use_pass_color = True
bk.margin = 8; bk.margin_type = 'EXTEND'; bk.use_clear = True; bk.target = 'IMAGE_TEXTURES'
# Bake ONE joined copy: baking several objects into a shared image runs the margin pass per
# object, and each object's margin then overwrites the neighbouring islands of the others.
copies = []
for o in objs:
    c = o.copy(); c.data = o.data.copy(); sc.collection.objects.link(c); copies.append(c)
with bpy.context.temp_override(active_object=copies[0], selected_objects=copies,
                               selected_editable_objects=copies, object=copies[0]):
    bpy.ops.object.join()
bake_proxy = copies[0]
for o in objs: o.hide_render = True                 # the proxy stands in for them during the bake
select_only([bake_proxy])
print("baking objects…", flush=True)
bpy.ops.object.bake(type='DIFFUSE')
for o in objs: o.hide_render = False
me = bake_proxy.data; D.objects.remove(bake_proxy); D.meshes.remove(me)
os.makedirs(TEX_DIR, exist_ok=True)
atlas.filepath_raw = os.path.join(TEX_DIR, "objects.png"); atlas.file_format = 'PNG'; atlas.save()

# ---------------------------------------------------------------- 5. bake floor: lighting only, normalised so open floor = 1
floor = D.objects["floor"]
fmat = floor.data.materials[0]
flt = D.images.new("floor_light", FLOOR_RES, FLOOR_RES, alpha=False, float_buffer=True)
fn = fmat.node_tree.nodes.new("ShaderNodeTexImage"); fn.image = flt; fmat.node_tree.nodes.active = fn
bk.use_pass_color = False
select_only([floor])
print("baking floor…", flush=True)
bpy.ops.object.bake(type='DIFFUSE')
px = np.array(flt.pixels[:], dtype=np.float32).reshape(FLOOR_RES, FLOOR_RES, 4)
# open-floor level = brightest robust value along the plane border (far from every object)
border = np.concatenate([px[2:8, :, :3].reshape(-1, 3), px[-8:-2, :, :3].reshape(-1, 3),
                         px[:, 2:8, :3].reshape(-1, 3), px[:, -8:-2, :3].reshape(-1, 3)])
open_level = np.median(border, axis=0)
shade = np.clip(px[..., :3] / open_level, 0, 1)
yy, xx = np.mgrid[0:FLOOR_RES, 0:FLOOR_RES]
r = np.hypot((xx + .5) / FLOOR_RES * 2 - 1, (yy + .5) / FLOOR_RES * 2 - 1)
t = np.clip((r - 0.70) / (0.97 - 0.70), 0, 1); alpha = 1 - t*t*(3 - 2*t)
fimg = D.images.new("baked_floor", FLOOR_RES, FLOOR_RES, alpha=True)
fimg.pixels.foreach_set(np.dstack([shade, alpha]).astype(np.float32).ravel())
fimg.filepath_raw = os.path.join(TEX_DIR, "floor.png"); fimg.file_format = 'PNG'; fimg.save()
print("floor open level", open_level.round(4), "min shade", shade.min(axis=(0, 1)).round(3))

# ---------------------------------------------------------------- 6. export materials
def principled(name, color=None, image=None, alpha_from_image=False):
    m = D.materials.new(name); nt = m.node_tree; nt.nodes.clear()
    b = nt.nodes.new("ShaderNodeBsdfPrincipled"); o = nt.nodes.new("ShaderNodeOutputMaterial")
    nt.links.new(b.outputs[0], o.inputs[0])
    b.inputs["Roughness"].default_value = 1.0
    b.inputs["Specular IOR Level"].default_value = 0.0
    if color: b.inputs["Base Color"].default_value = (*color, 1)
    if image:
        t = nt.nodes.new("ShaderNodeTexImage"); t.image = image
        nt.links.new(t.outputs["Color"], b.inputs["Base Color"])
        if alpha_from_image:
            nt.links.new(t.outputs["Alpha"], b.inputs["Alpha"]); m.surface_render_method = 'BLENDED'
    return m
baked_mat = principled("baked", image=atlas)
for o in objs:
    o.data.materials.clear(); o.data.materials.append(baked_mat)
    for p in o.data.polygons: p.material_index = 0
screen.data.materials.clear(); screen.data.materials.append(principled("screen", color=hex_lin('#8ed9cf')))
floor.data.materials.clear(); floor.data.materials.append(principled("floor_shadow", image=fimg, alpha_from_image=True))
if not floor.data.uv_layers: raise SystemExit("floor has no UVs")

# keep only what ships
for o in [o for o in D.objects if o.type in ('LIGHT', 'CAMERA')]:
    pass  # stay in the .blend for reference, excluded from the GLB below
floor.hide_select = True
for occ in occluders:                               # bake-only geometry, never shipped
    me = occ.data; D.objects.remove(occ); D.meshes.remove(me)
bpy.ops.outliner.orphans_purge(do_recursive=True)
bpy.context.preferences.filepaths.save_version = 0
bpy.ops.wm.save_as_mainfile(filepath=BLEND_OUT, copy=True)
print("saved", BLEND_OUT)

# ---------------------------------------------------------------- 7. GLB
ship = objs + [screen, floor]
select_only(ship)
os.makedirs(os.path.dirname(GLB), exist_ok=True)
bpy.ops.export_scene.gltf(filepath=GLB, export_format='GLB', use_selection=True, export_yup=True,
                          export_apply=True, export_image_format='WEBP', export_image_quality=90,
                          export_cameras=False, export_lights=False, export_materials='EXPORT')
print("exported", GLB, os.path.getsize(GLB) // 1024, "KB")
