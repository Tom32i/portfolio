import bpy, bmesh, os, numpy as np
from mathutils import Vector
from mathutils.geometry import delaunay_2d_cdt, intersect_point_tri_2d, barycentric_transform

SIMPLIFY_PX = float(os.environ.get("SIMPLIFY_PX", "1.5"))
OUT = os.environ["OUT"]

img = bpy.data.images["Image_0"]
W, H = img.size
px = np.empty(W*H*4, dtype=np.float32); img.pixels.foreach_get(px)
alpha = px.reshape(H, W, 4)[..., 3]          # row 0 = v 0

# light blur so marching squares gives smooth sub-pixel edges on the binary mask
def blur(a, r=1):
    k = np.array([1, 2, 1], np.float32); k /= k.sum()
    p = np.pad(a, 1, mode='edge')
    a = (p[:, :-2]*k[0] + p[:, 1:-1]*k[1] + p[:, 2:]*k[2])
    p = np.pad(a, ((1, 1), (0, 0)), mode='edge')
    return p[:-2]*k[0] + p[1:-1]*k[1] + p[2:]*k[2]

def marching_squares(a, iso=0.5):
    """Return closed contours as lists of (x, y) pixel-centre coords. `a` must be zero-padded."""
    h, w = a.shape
    segs = {}
    def interp(p1, p2, v1, v2):
        t = (iso - v1) / (v2 - v1)
        return (p1[0] + t*(p2[0]-p1[0]), p1[1] + t*(p2[1]-p1[1]))
    b = a >= iso
    code = (b[:-1, :-1]*1 | b[:-1, 1:]*2 | b[1:, 1:]*4 | b[1:, :-1]*8)
    ys, xs = np.nonzero((code > 0) & (code < 15))
    out = []
    for y, x in zip(ys, xs):
        c = code[y, x]
        v = (a[y, x], a[y, x+1], a[y+1, x+1], a[y+1, x])
        P = ((x, y), (x+1, y), (x+1, y+1), (x, y+1))
        E = [interp(P[i], P[(i+1) % 4], v[i], v[(i+1) % 4]) if b_ != b2 else None
             for i, (b_, b2) in enumerate(zip((v[0]>=iso, v[1]>=iso, v[2]>=iso, v[3]>=iso),
                                               (v[1]>=iso, v[2]>=iso, v[3]>=iso, v[0]>=iso)))]
        idx = [i for i in range(4) if E[i] is not None]
        if len(idx) == 2:
            out.append(((y, x, idx[0]), (y, x, idx[1]), E[idx[0]], E[idx[1]]))
        else:  # saddle: pair by centre value
            cv = sum(v) / 4
            pairs = ((0, 1), (2, 3)) if (cv >= iso) == (v[0] >= iso) else ((0, 3), (1, 2))
            # choose pairing so segments separate the right corners
            if (cv >= iso) != (v[0] >= iso):
                pairs = ((3, 0), (1, 2))
            else:
                pairs = ((0, 1), (2, 3))
            for i, j in pairs:
                out.append(((y, x, i), (y, x, j), E[i], E[j]))
    # edge keys shared between neighbouring cells
    def ekey(y, x, e):
        return {0: ('h', y, x), 1: ('v', y, x+1), 2: ('h', y+1, x), 3: ('v', y, x)}[e]
    adj = {}
    pos = {}
    for k1, k2, p1, p2 in out:
        a1, a2 = ekey(*k1), ekey(*k2)
        pos[a1], pos[a2] = p1, p2
        adj.setdefault(a1, []).append(a2)
        adj.setdefault(a2, []).append(a1)
    seen = set(); contours = []
    for start in adj:
        if start in seen: continue
        loop = [start]; seen.add(start); prev = None; cur = start
        while True:
            nxt = [n for n in adj[cur] if n != prev and n not in seen]
            if not nxt: break
            prev, cur = cur, nxt[0]; seen.add(cur); loop.append(cur)
        if len(loop) > 8:
            contours.append([pos[k] for k in loop])
    return contours

def rdp_closed(pts, eps):
    pts = np.asarray(pts)
    def rdp(p):
        if len(p) < 3: return p
        a, b = p[0], p[-1]; d = b - a; n = np.hypot(*d)
        dist = np.abs(np.cross(d, p - a)) / n if n > 0 else np.hypot(*(p - a).T)
        i = int(np.argmax(dist))
        if dist[i] > eps:
            return np.vstack([rdp(p[:i+1])[:-1], rdp(p[i:])])
        return np.vstack([a, b])
    # split the loop at its two farthest-apart points
    i0 = 0; i1 = int(np.argmax(np.hypot(*(pts - pts[0]).T)))
    A = rdp(pts[i0:i1+1]); B = rdp(np.vstack([pts[i1:], pts[:1]]))
    return np.vstack([A[:-1], B[:-1]])

def point_in_polys(p, polys):
    x, y = p; inside = False
    for poly in polys:
        xs, ys = poly[:, 0], poly[:, 1]
        xj, yj = np.roll(xs, 1), np.roll(ys, 1)
        hit = ((ys > y) != (yj > y)) & (x < (xj - xs) * (y - ys) / (yj - ys + 1e-30) + xs)
        inside ^= bool(np.count_nonzero(hit) % 2)
    return inside

contour_cache = {}
def leaf_contours(u0, u1, v0, v1):
    key = (round(u0, 4), round(u1, 4), round(v0, 4), round(v1, 4))
    if key in contour_cache: return contour_cache[key]
    x0, x1 = int(np.floor(u0*W)) , int(np.ceil(u1*W))
    y0, y1 = int(np.floor(v0*H)), int(np.ceil(v1*H))
    crop = blur(alpha)[y0:y1, x0:x1]
    a = np.pad(crop, 1)                       # zero border → closed contours
    polys = []
    for c in marching_squares(a):
        c = rdp_closed(c, SIMPLIFY_PX)
        if len(c) < 3: continue
        area = 0.5*abs(np.dot(c[:,0], np.roll(c[:,1],1)) - np.dot(c[:,1], np.roll(c[:,0],1)))
        if area < float(os.environ.get("MIN_AREA_PX", "0")): continue
        uv = np.empty_like(c)
        uv[:, 0] = (c[:, 0] - 1 + x0 + 0.5) / W   # pixel centre → uv
        uv[:, 1] = (c[:, 1] - 1 + y0 + 0.5) / H
        uv[:, 0] = np.clip(uv[:, 0], u0, u1); uv[:, 1] = np.clip(uv[:, 1], v0, v1)
        polys.append(uv)
    contour_cache[key] = polys
    return polys

def rebuild(ob):
    me = ob.data
    uvl = me.uv_layers.active.data
    # per-corner uv/co triangles of the source grid
    tris = []
    me.calc_loop_triangles()
    for lt in me.loop_triangles:
        tris.append(([Vector(uvl[l].uv) for l in lt.loops],
                     [me.vertices[v].co.copy() for v in lt.vertices]))
    us = np.array([d.uv for d in uvl])
    u0, v0 = us.min(0); u1, v1 = us.max(0)
    polys = leaf_contours(u0, u1, v0, v1)

    # CDT input: contour loops + the source grid edges (so every output tri sits in one grid tri)
    verts = []; edges = []
    for poly in polys:
        base = len(verts)
        verts += [Vector(p) for p in poly]
        n = len(poly); edges += [(base+i, base+(i+1) % n) for i in range(n)]
    if os.environ.get("GRID_EDGES", "1") == "1":
        for uvt, _ in tris:
            base = len(verts); verts += uvt
            edges += [(base, base+1), (base+1, base+2), (base+2, base)]
    else:  # grid verts as loose interior points: keeps the bend, few extra verts
        seen = set()
        for uvt, _ in tris:
            for u in uvt:
                k = (round(u.x, 5), round(u.y, 5))
                if k not in seen and point_in_polys(k, polys):
                    seen.add(k); verts.append(u.copy())
    out_v, out_e, out_f, *_ = delaunay_2d_cdt(verts, edges, [], 0, 1e-7)

    keep = []
    for f in out_f:
        c = sum((out_v[i] for i in f), Vector((0, 0))) / len(f)
        if point_in_polys((c.x, c.y), polys):
            keep.append(f)

    def to3d(p):
        for uvt, cot in tris:
            if intersect_point_tri_2d(p, *uvt):
                return barycentric_transform(p.to_3d(), *(u.to_3d() for u in uvt), *cot)
        # numerical fallback: nearest tri
        best = min(tris, key=lambda t: (sum(t[0], Vector((0, 0)))/3 - p).length)
        return barycentric_transform(p.to_3d(), *(u.to_3d() for u in best[0]), *best[1])

    used = sorted({i for f in keep for i in f}); remap = {o: n for n, o in enumerate(used)}
    bm = bmesh.new()
    bv = [bm.verts.new(to3d(out_v[i])) for i in used]
    uvlay = bm.loops.layers.uv.new("UVMap")
    smooth = me.polygons[0].use_smooth
    for f in keep:
        try:
            face = bm.faces.new([bv[remap[i]] for i in f])
        except ValueError:
            continue
        face.smooth = smooth
        for loop, i in zip(face.loops, f):
            loop[uvlay].uv = out_v[i]
    bmesh.ops.remove_doubles(bm, verts=bm.verts, dist=1e-6)
    # make winding consistent with the source normal
    src_n = sum((p.normal for p in me.polygons), Vector()).normalized()
    bm.normal_update()
    if sum((f.normal for f in bm.faces), Vector()).dot(src_n) < 0:
        bmesh.ops.reverse_faces(bm, faces=bm.faces)
    new = bpy.data.meshes.new(me.name.replace(".001", "") + "_cut")
    bm.to_mesh(new); bm.free()
    for m in me.materials: new.materials.append(m)
    old = ob.data; ob.data = new
    if old.users == 0: bpy.data.meshes.remove(old)
    return len(new.vertices), len(new.polygons)

for i in range(7):
    ob = bpy.data.objects[f"monstera_leaf_{i}"]
    print(ob.name, "->", rebuild(ob))

# material: geometry now carries the shape → no alpha clip, flat leaf green
mat = bpy.data.materials["monstera_leaf.001"]
nt = mat.node_tree
bsdf = nt.nodes["Principled BSDF"]
green = nt.nodes["Mix"].inputs["B"].default_value[:]
for l in list(nt.links):
    if l.to_node == bsdf and l.to_socket.name in ("Alpha", "Base Color"):
        nt.links.remove(l)
bsdf.inputs["Alpha"].default_value = 1.0
bsdf.inputs["Base Color"].default_value = green
for n in ("Mix", "Math", "Math.001", "Image Texture", "Frame"):
    if n in nt.nodes: nt.nodes.remove(nt.nodes[n])
mat.use_backface_culling = False
if hasattr(mat, "surface_render_method"): mat.surface_render_method = 'DITHERED'
bpy.ops.outliner.orphans_purge(do_recursive=True)
bpy.ops.wm.save_as_mainfile(filepath=OUT, copy=True)
print("saved", OUT)
