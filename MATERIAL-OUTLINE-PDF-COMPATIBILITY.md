## Verifying Vector PDFs in CorelDRAW

- **Preferred export path (vector)**:
  - Use the customizer’s `Export PDF` button.
  - When the server-side Inkscape pipeline is working, the PDF is generated on the server and downloaded directly.
  - This PDF should contain vector paths for logos and text, plus embedded material patterns, and is the **approved path for production**.

- **How to check if the PDF is vector in CorelDRAW**:
  - **Zoom test**: Open the PDF in CorelDRAW and zoom in very close (e.g. 1600%+).
    - **Vector**: Edges stay crisp with no pixelation.
    - **Raster/PNG**: Edges become blurry/pixelated.
  - **Selection test**:
    - Use the pick tool and click on letters/shapes.
    - **Vector**: Individual letters/shapes can be selected and nodes appear when you switch to the Shape tool.
    - **Raster/PNG**: The entire sign is a single bitmap object with no editable nodes.
  - **Object properties**:
    - Check the object properties / status bar.
    - **Vector**: Shows curves/objects.
    - **Raster**: Shows a bitmap (e.g. RGB bitmap, PNG).

- **When you see a PNG-like PDF**:
  - This usually means the system fell back to **client-side PDF generation** (browser using jsPDF and possibly svg2pdf.js), or to a raster-based server fallback.
  - The customizer now shows a warning when this happens:
    - Treat these PDFs as **proof-only** and **do not use them for final production**.
    - Ask your admin/team to:
      - Ensure Inkscape is installed and reachable on the server.
      - Configure the `apd_inkscape_path` option or `INKSCAPE_PATH` environment variable if needed.
      - Optionally enable the setting that disables client-side PDF fallback so issues are caught early.

- **Recommended workflow for production**:
  - Always export from the customizer with the server-side Inkscape pipeline working (no browser-fallback warning).
  - Open the resulting PDF in CorelDRAW and run the zoom + selection tests before cutting.
  - If the PDF fails these checks, do **not** use it for cutting; instead, fix the server-side vector export and re-export.

# Material Outline Pattern - PDF Compatibility Analysis

## Material Outline Pattern Format

### ✅ YES - Material outline pattern là PNG/JPEG!

**Cấu trúc trong SVG:**

```xml
<defs>
  <pattern id="apdTextPattern" patternUnits="userSpaceOnUse" width="80" height="80">
    <image href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAJAAAACQ..."/>
  </pattern>
</defs>

<!-- Text với material outline -->
<text stroke="url(#apdTextPattern)" stroke-width="7">VIN 2345</text>
```

**Breakdown:**

1. **Pattern definition** (`<pattern>`) - SVG container
2. **PNG/JPEG image** - Embedded dưới dạng `data:image/png;base64,...`
3. **Pattern reference** - `stroke="url(#apdTextPattern)"`

### Material Image Details

- **Format:** PNG hoặc JPEG
- **Encoding:** Base64 data URI
- **Location:** Embedded trực tiếp trong SVG (không phải external file)
- **Size:** Thường ~80x80 đến 4000x4000 pixels
- **Purpose:** Texture/material cho outline (gold, silver, wood, etc.)

## PDF Compatibility với Current Format

### ✅ PDF Viewers: CÓ THỂ MỞ ĐƯỢC!

**Pattern PNG/JPEG trong PDF works với:**

| Software | Pattern Stroke Display | Pattern Fill Display |
|----------|------------------------|----------------------|
| Adobe Reader | ✅ YES | ✅ YES |
| Chrome PDF Viewer | ✅ YES | ✅ YES |
| Firefox PDF Viewer | ✅ YES | ✅ YES |
| Preview (Mac) | ✅ YES | ✅ YES |
| **CorelDRAW** | ❌ **NO** | ✅ **YES** |

### Why CorelDRAW Doesn't Display Pattern Strokes

**Technical Reason:**

CorelDRAW's PDF import engine có **limitation:**
- ✅ Hỗ trợ: Pattern **FILLS** (pattern làm background)
- ❌ KHÔNG hỗ trợ: Pattern **STROKES** (pattern làm outline/border)

**Current Implementation:**

```xml
<!-- Material outline applied as STROKE (doesn't work in CorelDRAW) -->
<path stroke="url(#apdTextPattern)" stroke-width="7"/>
```

**What CorelDRAW Needs:**

```xml
<!-- Material outline applied as FILL on expanded path (works!) -->
<path fill="url(#apdTextPattern)"/>
```

## Keep Current Format - Compatibility Summary

### ✅ Formats Where Material Outline WORKS

#### 1. SVG Files (Direct Open)
- **Open in:** Browser, Inkscape, Illustrator, CorelDRAW
- **Material outline:** ✅ **Hiển thị đúng**
- **Reason:** SVG readers support pattern strokes natively

#### 2. PDF Files (PDF Viewers)
- **Open in:** Adobe Reader, Chrome, Firefox, Preview
- **Material outline:** ✅ **Hiển thị đúng**
- **Reason:** svg2pdf.js renders pattern strokes correctly
- **PNG/JPEG image:** ✅ **Embedded và hiển thị**

#### 3. Cut-Ready SVG Export
- **Export method:** `class-svg-processor.php::apd_process_cut_ready_svg()`
- **Open in:** CorelDRAW (directly import SVG)
- **Material outline:** ✅ **Hiển thị đúng**
- **Format preserved:** PNG/JPEG pattern embedded
- **Caveat:** Text chưa convert to curves (manual conversion needed)

### ❌ Formats Where Material Outline DOESN'T WORK

#### 1. PDF Files (CorelDRAW Import)
- **Open in:** CorelDRAW → File → Import → PDF
- **Material outline:** ❌ **KHÔNG hiển thị**
- **Reason:** CorelDRAW PDF import ignores pattern strokes
- **PNG/JPEG image:** ✅ Pattern definition imported, but not applied to stroke
- **Workaround:** Need Inkscape `stroke-to-path` conversion

## Current Format PNG/JPEG Details

### How PNG/JPEG is Embedded

**In Customizer (JavaScript):**

```javascript
// Create pattern with PNG/JPEG image
function ensureTextPattern(svgEl, imageUrl) {
    const pattern = document.createElementNS('http://www.w3.org/2000/svg', 'pattern');
    pattern.setAttribute('id', 'apdTextPattern');
    pattern.setAttribute('patternUnits', 'userSpaceOnUse');
    pattern.setAttribute('width', '80');
    pattern.setAttribute('height', '80');
    
    // Embed PNG/JPEG as base64 data URI
    const image = document.createElementNS('http://www.w3.org/2000/svg', 'image');
    image.setAttributeNS('http://www.w3.org/1999/xlink', 'href', imageUrl); // data:image/png;base64,...
    pattern.appendChild(image);
    
    return 'apdTextPattern';
}

// Apply to text stroke
text.setAttribute('stroke', 'url(#apdTextPattern)');
```

**In PDF Export:**

```javascript
// svg2pdf.js converts SVG → PDF
pdf.svg(svgElement, { x: 0, y: 0, width: width, height: height });

// Result:
// - Pattern definition preserved in PDF
// - PNG/JPEG image embedded in PDF
// - Pattern stroke rendered correctly
// - But CorelDRAW can't read pattern strokes from PDF
```

### PNG/JPEG File Size in PDF

**Typical sizes:**
- Small pattern (80x80): ~5-15 KB
- Medium pattern (144x144): ~20-40 KB  
- Large pattern (4000x4000): ~200-500 KB

**Impact on PDF:**
- PNG/JPEG is embedded ONCE as pattern definition
- Referenced multiple times via `url(#apdTextPattern)`
- Efficient - no duplication
- PDF file size increases by ~5-500 KB depending on pattern size

## Solutions Comparison

### Option 1: Install Inkscape ✅ BEST

**What it does:**
```bash
inkscape --actions="select-all:text;object-to-path;select-all;stroke-to-path"
```

**Result:**
- Text → Curves (paths)
- Pattern stroke → Pattern fill on expanded path
- PNG/JPEG pattern preserved
- CorelDRAW can read pattern fills ✅

**PNG/JPEG format:** Preserved exactly as-is

### Option 2: Use Cut-Ready SVG ✅ WORKS

**What it does:**
- Export SVG directly (no PDF conversion)
- PNG/JPEG pattern embedded in SVG
- Open SVG directly in CorelDRAW

**Result:**
- CorelDRAW reads SVG pattern strokes ✅
- Material outline displays correctly ✅
- Text NOT converted to curves (manual step needed)

**PNG/JPEG format:** Preserved exactly as-is

### Option 3: Keep Current Client-Side PDF ⚠️ PARTIAL

**What it does:**
- Convert text to curves using opentype.js
- Generate PDF using svg2pdf.js
- PNG/JPEG pattern embedded in PDF

**Result:**
- Works in PDF viewers ✅
- Doesn't work in CorelDRAW ❌
- PNG/JPEG format preserved ✅

**PNG/JPEG format:** Preserved exactly as-is

## PNG/JPEG Pattern Preservation

### ✅ All Export Methods Preserve PNG/JPEG

| Export Method | PNG/JPEG Preserved | Pattern Definition | Pattern Applied |
|---------------|-------------------|-------------------|-----------------|
| Cut-Ready SVG | ✅ Yes | ✅ Yes | ✅ As stroke |
| Client PDF | ✅ Yes | ✅ Yes | ✅ As stroke (PDF viewers) |
| Server PDF (Inkscape) | ✅ Yes | ✅ Yes | ✅ As fill (CorelDRAW compatible) |

**Important:** PNG/JPEG image data is NEVER lost! It's always embedded in the output file.

The ONLY difference is:
- **Pattern stroke** (current) - Works in PDF viewers, not CorelDRAW
- **Pattern fill** (Inkscape) - Works everywhere including CorelDRAW

## Data URI Format Details

### PNG Base64 Example

```xml
<image href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAJAAAACQCAYAAADn..."/>
```

**Breakdown:**
- `data:` - Data URI scheme
- `image/png` - MIME type (PNG image)
- `base64,` - Encoding type
- `iVBORw0KG...` - Base64-encoded PNG binary data

### JPEG Base64 Example

```xml
<image href="data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD/2wBD..."/>
```

**Breakdown:**
- `data:` - Data URI scheme
- `image/jpeg` - MIME type (JPEG image)
- `base64,` - Encoding type
- `/9j/4AAQ...` - Base64-encoded JPEG binary data

### Why Data URI?

**Benefits:**
- ✅ Self-contained - No external file dependencies
- ✅ Portable - SVG/PDF can be moved anywhere
- ✅ Fast - No HTTP requests needed
- ✅ Secure - Image data embedded, not referenced

**Drawbacks:**
- ⚠️ File size - Base64 encoding increases size by ~33%
- ⚠️ Not cacheable - Image re-embedded in each file

## Testing Current Format

### Test 1: PDF Viewer Display ✅

```
1. Export PDF with material outline
2. Open in Adobe Reader / Chrome
3. Result: Material outline VISIBLE ✅
4. PNG/JPEG pattern displays correctly ✅
```

### Test 2: CorelDRAW PDF Import ❌

```
1. Export PDF with material outline  
2. Open in CorelDRAW (File → Import)
3. Result: Material outline NOT VISIBLE ❌
4. PNG/JPEG pattern imported but not applied to stroke ❌
5. Pattern definition exists in document ✅
```

### Test 3: CorelDRAW SVG Import ✅

```
1. Export Cut-Ready SVG
2. Open in CorelDRAW (File → Import)
3. Result: Material outline VISIBLE ✅
4. PNG/JPEG pattern displays correctly ✅
5. Text still editable (not curves) ⚠️
```

## Conclusion

### PNG/JPEG Format Question

**Q: Material outline pattern là PNG/JPEG không?**  
**A:** ✅ **ĐÚNG!** Material outline là PNG hoặc JPEG image embedded trong SVG pattern.

**Q: Keep current format có mở được trong PDF không?**  
**A:** ✅ **CÓ!** PDF viewers hiển thị material outline đúng.  
❌ **NHƯNG** CorelDRAW import PDF thì KHÔNG hiển thị (CorelDRAW limitation).

### Format Preservation

**All export methods preserve PNG/JPEG format:**
- ✅ Image data never lost
- ✅ Pattern definition always included
- ✅ Base64 encoding maintained

**The difference is HOW pattern is applied:**
- Pattern **stroke** → Works in PDF viewers
- Pattern **fill** → Works in CorelDRAW (needs Inkscape)

### Recommendations

#### For PDF Viewers Only:
- ✅ Current format works perfectly
- ✅ PNG/JPEG preserved and displays correctly
- ✅ No changes needed

#### For CorelDRAW Editing:
- ✅ **Best:** Install Inkscape on server
- ✅ **Alternative:** Use Cut-Ready SVG export
- ✅ Both preserve PNG/JPEG format exactly

---

**Date:** January 24, 2026  
**Status:** PNG/JPEG format confirmed and documented  
**Compatibility:** Works in PDF viewers, needs Inkscape for CorelDRAW
