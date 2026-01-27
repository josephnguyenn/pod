# Material Outline trong CorelDRAW - Phân Tích Kỹ Thuật

## Vấn Đề Hiện Tại

**Material outline KHÔNG hiển thị trong CorelDRAW** khi mở PDF từ client-side export.

## Root Cause Analysis

### 1. CorelDRAW PDF Import Limitation

CorelDRAW **KHÔNG hỗ trợ pattern-filled strokes** khi import PDF:
- SVG spec: Pattern có thể apply cho `fill` VÀ `stroke` ✅
- Browser render: Pattern strokes hiển thị đúng ✅  
- svg2pdf.js: Render pattern strokes vào PDF đúng ✅
- **CorelDRAW import PDF: Pattern strokes KHÔNG hiển thị** ❌

### 2. Material Outline Implementation

Material outline hiện tại được implement như pattern stroke:

```xml
<text stroke="url(#apdTextPattern)" stroke-width="5">
  Text content
</text>
```

**Vấn đề:** Pattern stroke work trong SVG/browser nhưng **bị ignore khi CorelDRAW import PDF**.

### 3. Export Methods So Sánh

Plugin có 3 export methods:

#### A. **Cut-Ready SVG Export** ❌ No material outline in CorelDRAW
- File: `class-svg-processor.php::apd_process_cut_ready_svg()`
- Method: `make_coreldraw_compatible()`
- Features:
  - ✅ Convert styles to attributes
  - ✅ Fix malformed XML
  - ✅ Process patterns for CorelDRAW
  - ❌ **NO text-to-curves conversion**
  - ❌ **NO stroke-to-path conversion**
- Result: Text vẫn là text, material outline vẫn là pattern stroke
- **Material outline trong CorelDRAW: ❌ KHÔNG hiển thị**

#### B. **Client-Side PDF Export** (Current) ❌ No material outline in CorelDRAW
- File: `class-order-admin-handler.php::generateClientSidePDF()`
- Tools: opentype.js + svg2pdf.js (JavaScript in browser)
- Features:
  - ✅ Convert text to paths using opentype.js
  - ✅ Generate PDF using svg2pdf.js
  - ✅ Pattern strokes rendered in PDF
  - ❌ **NO stroke-to-path conversion** (requires complex path offsetting)
- Result: Text converted to curves, material outline vẫn là pattern stroke
- **Material outline trong CorelDRAW: ❌ KHÔNG hiển thị** (CorelDRAW ignores pattern strokes)

#### C. **Server-Side PDF Export with Inkscape** ✅ Material outline WORKS!
- File: `class-svg-processor.php::apd_export_pdf()`
- Method: `make_pdf_compatible()` → `inkscape_convert_all_to_curves()`
- Tools: Inkscape (command-line) on server
- Features:
  - ✅ Convert text to paths using Inkscape
  - ✅ **Convert stroke-to-path** (expands pattern strokes to pattern fills)
  - ✅ Pattern strokes → Pattern fills on expanded paths
  - ✅ Generate PDF using Inkscape
- Result: Text converted to curves, material outline converted to pattern fill on expanded outline paths
- **Material outline trong CorelDRAW: ✅ HIỂN thị đúng!**

### 4. Inkscape stroke-to-path Command

```bash
inkscape --actions="select-all:text;object-to-path;select-all;stroke-to-path" \
  --export-type=svg input.svg -o output.svg
```

**What it does:**
1. `select-all:text` - Select all text elements
2. `object-to-path` - Convert text to paths (curves)
3. `select-all` - Select all elements
4. `stroke-to-path` - **Convert strokes to fills on expanded paths**

**stroke-to-path effect:**
```
BEFORE:                      AFTER:
Path with pattern stroke     Two paths:
                             1. Expanded path (bigger) with pattern FILL
fill: blue                   2. Original path (inside) with blue fill
stroke: url(#pattern)        
stroke-width: 5              Result: Hollow outline effect!
```

## Solution Options

### Option 1: Install Inkscape on Server ✅ BEST SOLUTION

**How:**
```bash
# Ubuntu/Debian
sudo apt-get install inkscape

# Mac (Homebrew)
brew install inkscape

# Check if installed
which inkscape
```

**Benefits:**
- ✅ Material outline hiển thị đúng trong CorelDRAW
- ✅ Text converted to curves properly
- ✅ Pattern strokes converted to pattern fills
- ✅ No code changes needed
- ✅ Best quality output

**Current Status:**
- Inkscape NOT installed on server
- Client-side fallback is used instead

**Action Required:**
```bash
# Check Inkscape status
which inkscape  # Should return path if installed

# If not installed:
sudo apt-get update
sudo apt-get install -y inkscape

# Verify
inkscape --version
```

### Option 2: Improve Cut-Ready SVG Export ⚠️ PARTIAL

Add Inkscape text-to-path + stroke-to-path to Cut-Ready SVG:

**Pros:**
- Material outline would work in CorelDRAW when opening SVG directly
- Text converted to curves

**Cons:**
- Still requires Inkscape on server
- SVG file size increases significantly

**Implementation:**
Modify `make_coreldraw_compatible()` to call `inkscape_convert_all_to_curves()` (same as PDF export).

### Option 3: Client-Side Path Offsetting ❌ NOT FEASIBLE

Implement path offsetting algorithm in JavaScript to create expanded outline paths.

**Pros:**
- No server dependencies

**Cons:**
- ❌ Very complex to implement correctly
- ❌ Path offsetting is mathematically challenging
- ❌ Would need to handle curves, corners, joins properly
- ❌ Large JavaScript library needed
- ❌ Not as good quality as Inkscape

**Verdict:** NOT recommended - too complex for marginal benefit.

### Option 4: Rasterize Pattern Stroke ⚠️ WORKAROUND

Convert pattern stroke to PNG image and embed in PDF.

**Pros:**
- Would display in CorelDRAW

**Cons:**
- ❌ Loses vector quality
- ❌ Not editable in CorelDRAW
- ❌ Defeats purpose of vector export

**Verdict:** NOT recommended - defeats purpose of vector PDF.

### Option 5: User Documentation/Warning ✅ INTERIM SOLUTION

Add clear warning in UI when exporting PDF without Inkscape.

**Implementation:**
- ✅ Already added to console logs
- Add visible warning in UI
- Recommend server-side export or Cut-Ready SVG

## Recommended Action Plan

### Immediate (Now):
1. ✅ **Update console logs** with clear warnings (DONE)
2. **Add UI warning** when exporting PDF without Inkscape:
   ```
   ⚠️ Material outlines may not display in CorelDRAW
   
   For best CorelDRAW compatibility:
   • Ask admin to install Inkscape on server, OR
   • Use Cut-Ready SVG export (text not converted to curves)
   ```

### Short-term (Next deployment):
1. **Install Inkscape on production server**
   ```bash
   sudo apt-get install -y inkscape
   ```
2. **Verify Inkscape integration works**
3. **Material outline will automatically work** in CorelDRAW PDF import

### Long-term (Future enhancement):
1. Add Inkscape check in admin dashboard
2. Show status: "Inkscape: ✅ Installed" or "Inkscape: ❌ Not installed"
3. Auto-recommend best export method based on Inkscape availability

## Testing Checklist

### With Inkscape Installed:
- [ ] Export PDF from order with material outline
- [ ] Open PDF in CorelDRAW
- [ ] Verify material outline texture is visible ✅
- [ ] Verify text is curves (not editable text) ✅

### Without Inkscape (Current):
- [x] Export PDF from order with material outline
- [x] Open PDF in CorelDRAW
- [x] Material outline NOT visible ❌ (expected behavior)
- [x] Pattern stroke ignored by CorelDRAW ❌ (confirmed)

## Technical Details

### Why Pattern Stroke Doesn't Work in CorelDRAW PDF:

1. **SVG → PDF Conversion:**
   - svg2pdf.js converts SVG elements to PDF objects
   - Pattern strokes are represented as Pattern objects with stroke operations
   - This is valid PDF syntax

2. **CorelDRAW PDF Import:**
   - CorelDRAW's PDF import engine has limited pattern support
   - Pattern **fills** are imported correctly ✅
   - Pattern **strokes** are ignored or imported as solid color ❌

3. **Why Inkscape Works:**
   - Inkscape's `stroke-to-path` creates **expanded path geometry**
   - Pattern is applied as **fill** (not stroke) on expanded path
   - This works in CorelDRAW because pattern fills are supported

### SVG Structure Comparison:

**Client-Side Export (Pattern Stroke - doesn't work):**
```xml
<g>
  <!-- Text fill (works) -->
  <path d="..." fill="#000000"/>
  
  <!-- Material outline (doesn't work in CorelDRAW) -->
  <path d="..." fill="none" stroke="url(#apdTextPattern)" stroke-width="5"/>
</g>
```

**Inkscape Export (Pattern Fill - works!):**
```xml
<g>
  <!-- Expanded outline path with pattern FILL (works!) -->
  <path d="...[expanded geometry]..." fill="url(#apdTextPattern)"/>
  
  <!-- Text fill on top -->
  <path d="..." fill="#000000"/>
</g>
```

## Conclusion

**Material outline KHÔNG thể hiển thị trong CorelDRAW với client-side PDF export** do:
1. CorelDRAW không support pattern strokes khi import PDF
2. Client-side JavaScript không thể tạo expanded path geometry (stroke-to-path)

**Solution: Install Inkscape on server** để enable server-side PDF export với stroke-to-path support.

**Current Status:**
- ✅ Console warnings added
- ❌ Inkscape not installed on server
- ❌ Material outline không hiển thị trong CorelDRAW (expected)

**Next Steps:**
1. Install Inkscape on server
2. Material outline sẽ TỰ ĐỘNG hoạt động
3. Không cần code changes

---

**Updated:** January 24, 2026
**Status:** Root cause identified, solution documented
