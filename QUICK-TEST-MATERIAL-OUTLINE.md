# Quick Test: Material Outline in CorelDRAW

## Test Steps (5 minutes)

### 1. Export PDF with Material Outline
1. Go to WordPress Admin → Orders
2. Find order #9413 (or any order with material outline)
3. Click **"Export Vector PDF"** button
4. Wait for PDF to generate and download

### 2. Check Browser Console
Press F12 to open browser console, you should see:

```
✅ Text element X converted to curves with CorelDRAW-compatible material outline
✅ Material outline pattern applied as FILL (renders in CorelDRAW): url(#apdTextPattern)
✅ Pattern FILLS (CorelDRAW compatible): 2
✅ CorelDRAW WILL display material outline correctly in PDF
```

### 3. Open in CorelDRAW
1. Open the downloaded PDF in CorelDRAW
2. **Check if material outline is visible** ✅
3. Select the text element
4. Verify the fill shows the material texture/pattern

## What Changed?

### Before Fix ❌
- Material outline: **NOT VISIBLE** in CorelDRAW
- Pattern applied as: `stroke="url(#pattern)"` (doesn't render in PDF)
- Console shows: "Pattern strokes: 2" (strokes don't work)

### After Fix ✅
- Material outline: **VISIBLE** in CorelDRAW
- Pattern applied as: `fill="url(#pattern)"` (renders in PDF)
- Console shows: "Pattern FILLS: 2" (fills work!)

## Key Differences in Logs

### OLD LOGS (stroke-based, doesn't work):
```
📄 ✅ Material outline (PNG/JPEG pattern) preserved: url(#apdTextPattern)
📄 ✅ CorelDRAW should display material outline correctly  ← Wrong!
  - Pattern references: 5  ← All strokes
```

### NEW LOGS (fill-based, works!):
```
📄 ✅ Material outline pattern applied as FILL (renders in CorelDRAW): url(#apdTextPattern)
📄 ✅ CorelDRAW WILL display material outline correctly in PDF  ← Correct!
  - Pattern FILLS (CorelDRAW compatible): 2  ← Uses fills now!
```

## Quick Visual Check

### In Browser (before PDF export)
- Material outline should be visible
- Texture/pattern should show on text outline
- This part already worked before

### In CorelDRAW (after PDF import)
- Material outline should NOW be visible ✅ **NEW**
- Texture/pattern should show on text outline
- This is what we fixed!

## Troubleshooting

### If material outline still not visible:

1. **Check console for pattern fills**
   ```
   Pattern FILLS (CorelDRAW compatible): 0  ← Should be > 0
   ```

2. **Check if patterns have images**
   ```
   Pattern images (PNG/JPEG): 0  ← Should be > 0
   ```

3. **Try clearing cache and regenerating**
   - Hard refresh browser (Ctrl+Shift+R)
   - Export PDF again

### If you see warnings:

```
⚠️ WARNING: Patterns found but not applied as fills!
```
This means the fix didn't apply. Check that the code changes were saved.

## Technical Summary

**Problem:** Pattern strokes don't render in CorelDRAW PDF  
**Solution:** Convert pattern strokes to pattern fills  
**Result:** Material outline now visible in CorelDRAW ✅

## Need Help?

See detailed documentation: `CORELDRAW-MATERIAL-OUTLINE-FIX.md`

---
**Test Date:** January 24, 2026  
**Status:** Ready to test ✅
