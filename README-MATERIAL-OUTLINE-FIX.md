# Material Outline in CorelDRAW - Hướng Dẫn Giải Quyết

## Tóm Tắt Vấn Đề

**Material outline KHÔNG hiển thị trong CorelDRAW** khi mở PDF file.

## Nguyên Nhân

CorelDRAW không support **pattern-filled strokes** khi import PDF. Material outline hiện đang dùng pattern stroke, nên bị ignore.

## Giải Pháp Chính ✅ 

### Install Inkscape trên Server

```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install -y inkscape

# Verify
inkscape --version
```

**Khi Inkscape được install:**
- ✅ Material outline TỰ ĐỘNG hiển thị trong CorelDRAW
- ✅ Text được convert thành curves đúng cách
- ✅ Pattern strokes được convert thành pattern fills
- ✅ Không cần thay đổi code

## Cách Export để Material Outline Hoạt Động

### ✅ Option 1: Server-Side PDF (WITH Inkscape)
**Best quality - Material outline works!**

1. Admin install Inkscape trên server
2. Export PDF từ Orders page như bình thường
3. Server tự động dùng Inkscape để convert
4. Material outline sẽ hiển thị trong CorelDRAW

**How to check if using Inkscape:**
- Check PHP error logs for: `"Generated PDF using Inkscape"`
- If you see `"Using CLIENT-SIDE"` → Inkscape not available

### ⚠️ Option 2: Client-Side PDF (WITHOUT Inkscape) 
**Current fallback - Material outline DOESN'T work in CorelDRAW**

**Pros:**
- ✅ Text converted to curves
- ✅ Works without server dependencies
- ✅ Material outline displays in PDF viewers

**Cons:**
- ❌ Material outline KHÔNG hiển thị trong CorelDRAW
- ❌ Cannot do stroke-to-path conversion in JavaScript

**Warning message will show:**
```
⚠️ Material Outline Compatibility Warning

Material outlines may not display when opening this PDF in CorelDRAW.

Solutions:
✅ Best: Ask server admin to install Inkscape
✅ Alternative: Use Cut-Ready SVG export button instead
ℹ️ Material outlines will display in PDF viewers correctly
```

### ✅ Option 3: Cut-Ready SVG Export
**Direct CorelDRAW compatibility**

1. Click "Generate Cut-Ready SVG" button
2. Open SVG directly in CorelDRAW
3. Material outline WILL display ✅

**Pros:**
- ✅ Material outline hiển thị
- ✅ No server dependencies needed
- ✅ Direct SVG → CorelDRAW

**Cons:**
- ⚠️ Text NOT converted to curves (still editable text)
- ⚠️ Need to convert text manually in CorelDRAW if needed

## So Sánh Export Methods

| Feature | Client PDF (No Inkscape) | Server PDF (With Inkscape) | Cut-Ready SVG |
|---------|-------------------------|---------------------------|---------------|
| Text to curves | ✅ Yes | ✅ Yes | ❌ No (manual) |
| Material outline in CorelDRAW | ❌ No | ✅ Yes | ✅ Yes |
| Material outline in PDF viewers | ✅ Yes | ✅ Yes | N/A (SVG) |
| Server dependencies | None | Inkscape required | None |
| Best for | PDF viewing only | CorelDRAW editing | Direct CorelDRAW import |

## Testing Instructions

### 1. Check Current Status

Open browser console when exporting PDF and look for:

**If using Inkscape (Good!):**
```
✅ Server-side PDF generated successfully
```

**If using client-side (No material outline):**
```
⚠️ Using CLIENT-SIDE (text→curves via opentype.js)
⚠️ WARNING: Material outlines may not display in CorelDRAW
```

### 2. Install Inkscape (Server Admin)

```bash
# Check if already installed
which inkscape

# If not installed:
sudo apt-get update
sudo apt-get install -y inkscape

# Verify installation
inkscape --version
# Should output: Inkscape 1.x.x
```

### 3. Test After Inkscape Installation

1. Export PDF from order with material outline
2. Check console - should now see: `"Generated PDF using Inkscape"`
3. Open PDF in CorelDRAW
4. **Material outline should be visible!** ✅

## Kỹ Thuật Chi Tiết

### Vì Sao Client-Side Không Thể Fix?

**Material outline cần:**
1. Path được expanded ra (lớn hơn text path)
2. Pattern được fill vào expanded path
3. Text path fill đè lên trên

**Client-side JavaScript:**
- ✅ Có thể convert text → paths (opentype.js)
- ❌ KHÔNG thể expand path geometry (cần complex offsetting algorithm)
- ❌ KHÔNG thể implement stroke-to-path properly

**Inkscape command (server-side):**
```bash
inkscape --actions="select-all:text;object-to-path;select-all;stroke-to-path"
```
- ✅ Convert text to paths
- ✅ Convert strokes to fills on expanded paths
- ✅ Pattern strokes → Pattern fills (CorelDRAW compatible!)

### SVG Structure So Sánh

**Client-Side (Pattern Stroke - Doesn't work):**
```xml
<path d="..." fill="#000" />  <!-- Text fill -->
<path d="..." stroke="url(#pattern)" />  <!-- Material outline - IGNORED by CorelDRAW -->
```

**Inkscape (Pattern Fill - Works!):**
```xml
<path d="...[expanded]..." fill="url(#pattern)" />  <!-- Outline - WORKS! -->
<path d="..." fill="#000" />  <!-- Text fill on top -->
```

## FAQ

### Q: Tại sao không thể fix trong code?
**A:** JavaScript không thể tạo expanded path geometry như Inkscape. Đây là vấn đề kỹ thuật phức tạp về path offsetting.

### Q: Material outline có hiển thị trong PDF viewer không?
**A:** CÓ! Material outline hiển thị đúng trong Adobe Reader, Chrome PDF viewer, etc. Chỉ CorelDRAW mới không support pattern strokes.

### Q: Có cách nào khác không cần Inkscape?
**A:** Dùng **Cut-Ready SVG export** - mở SVG trực tiếp trong CorelDRAW. Nhưng text sẽ không phải curves (cần convert manual).

### Q: Inkscape có miễn phí không?
**A:** CÓ! Inkscape là open-source và hoàn toàn miễn phí.

### Q: Cần quyền admin để install Inkscape?
**A:** CÓ - cần SSH access và sudo privileges trên server.

## Updates Made

### Code Changes ✅
1. **Added console warnings** when using client-side PDF generation
2. **Added UI warning banner** about material outline compatibility
3. **Updated logs** to clearly indicate Inkscape status

### Documentation ✅
1. `MATERIAL-OUTLINE-CORELDRAW-ANALYSIS.md` - Technical analysis
2. `README-MATERIAL-OUTLINE-FIX.md` - This file
3. `CORELDRAW-MATERIAL-OUTLINE-FIX.md` - Previous fix attempt (archived)

## Next Steps

### Immediate:
1. ✅ Code updated with warnings (DONE)
2. ⏳ Test PDF export to see warning messages
3. ⏳ Check if warning banner displays correctly

### Server Admin Action Required:
1. ⏳ Install Inkscape on production server
2. ⏳ Verify Inkscape version: `inkscape --version`
3. ⏳ Test PDF export after installation

### After Inkscape Installation:
1. Material outline will automatically work ✅
2. No further code changes needed ✅
3. Users can export PDFs normally ✅

## Support

**If material outline still doesn't work after installing Inkscape:**

1. Check PHP error logs for Inkscape errors
2. Verify Inkscape is in PATH: `which inkscape`
3. Test Inkscape manually:
   ```bash
   inkscape --version
   inkscape --actions="select-all;stroke-to-path" test.svg -o output.svg
   ```

4. Check file permissions on upload directory

---

**Date:** January 24, 2026  
**Status:** ✅ Issue identified, solution documented, warnings added  
**Action Required:** Install Inkscape on server
