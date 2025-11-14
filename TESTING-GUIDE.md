# Advanced Product Designer - Testing Guide

## 🎯 Quick Start Testing

### 1. Health Check (5 minutes)
Run this first to verify your installation:

1. Go to **APD Dashboard → Health Check**
2. Click **"Run Health Check"**
3. Review results:
   - ✅ **Green (Success)**: Everything working
   - ⚠️ **Yellow (Warning)**: May need attention
   - ❌ **Red (Error)**: Needs immediate fix

**Target Score**: 80%+ for stable operation

---

## 🧪 Core Feature Tests

### Test 1: Product Creation Flow
**Time**: 5 minutes  
**Purpose**: Verify products can be created and configured

**Steps**:
1. Go to **APD Dashboard → Products**
2. Click **"Add New Product"**
3. Fill in:
   - Product name
   - Price (e.g., $50.00)
   - Select a template
   - Upload thumbnail image
   - Upload SVG logo (if applicable)
   - Check "Customizable" checkbox
4. Click **Publish**
5. Visit product list page
6. Verify product appears

**Expected Result**: Product shows on list with correct price and thumbnail

---

### Test 2: Product Detail Page
**Time**: 3 minutes  
**Purpose**: Verify product detail page works

**Steps**:
1. Go to product list page
2. Click on any product card
3. Verify you see:
   - Product image
   - Product name
   - Price
   - Quantity selector (+/-)
   - Three buttons: "Add to cart", "Check out", "Customize this product"
4. Test quantity buttons work
5. Click "Add to cart"

**Expected Result**: 
- All buttons appear correctly
- "Added to cart!" message shows
- No "$50.00 - Solid" text on buttons

---

### Test 3: Customization Flow
**Time**: 10 minutes  
**Purpose**: Verify customizer works end-to-end

**Steps**:
1. Click a product's **"Customize"** button
2. Customizer should open with template
3. Try these actions:
   - Change text in text fields
   - Change colors
   - Select different material texture
   - Upload custom logo (if available)
4. Click **"Add to Cart"**
5. Go to cart page

**Expected Result**: 
- Customizer loads without errors
- Changes reflect in preview
- Product added to cart with customization
- Preview image shows in cart

---

### Test 4: Cart Functionality
**Time**: 5 minutes  
**Purpose**: Verify cart operations work

**Steps**:
1. Add 2-3 products to cart (mix of customized and non-customized)
2. Go to **Cart** page
3. Verify:
   - All products show
   - Customized products show preview image
   - Non-customized products show product thumbnail
   - Prices are correct
4. Test quantity change (+/-)
5. Test "Remove" button
6. Verify total updates correctly

**Expected Result**: 
- All cart operations work smoothly
- Correct preview images show
- Math is accurate

---

### Test 5: Checkout Process
**Time**: 5 minutes  
**Purpose**: Verify order placement works

**Steps**:
1. Add product to cart
2. Go to **Checkout** page
3. Fill in form:
   - Name
   - Email
   - Phone
   - Address
4. Click **"Place Order"**
5. Verify redirect to Thank You page
6. Check admin for new order

**Expected Result**: 
- Order created successfully
- Email confirmation sent
- Order appears in admin

---

## 🔧 Technical Tests

### Test 6: AJAX Functionality
**Time**: 2 minutes  
**Purpose**: Verify JavaScript/AJAX working

**Steps**:
1. Open browser console (F12)
2. Add product to cart
3. Check console for:
   - No JavaScript errors
   - AJAX responses are successful
4. Look for errors in console

**Expected Result**: No errors, all AJAX calls return success

---

### Test 7: Template Preview
**Time**: 3 minutes  
**Purpose**: Verify template rendering

**Steps**:
1. Go to product list
2. Check product cards
3. Verify template previews load (not just fallback images)
4. Wait 5 seconds for all previews to render

**Expected Result**: 
- Template previews render
- Fallback images show only if template fails

---

### Test 8: File Uploads
**Time**: 5 minutes  
**Purpose**: Verify media uploads work

**Steps**:
1. Edit a product
2. Click **"Upload Thumbnail"** button
3. Select an image from media library
4. Verify image appears
5. Try **"Upload Logo"** button
6. Upload an SVG file
7. Verify it only accepts SVG

**Expected Result**: 
- WordPress media selector opens
- Images save correctly
- Logo accepts only SVG files

---

## 🐛 Debug Mode Testing

### Enable Debug Mode
1. Go to **APD Dashboard → Debug Log**
2. Click **"Enable Debug Mode"**
3. Perform any action (add to cart, customize, etc.)
4. Return to Debug Log page
5. Click **"Refresh"**
6. Review log entries

**What to Look For**:
- Any ERROR messages
- Repeated warnings
- Failed AJAX calls
- Missing files

---

## 📊 Performance Checks

### Page Load Speed
1. Open product list page
2. Open browser DevTools → Network tab
3. Reload page
4. Check:
   - Total load time < 3 seconds
   - No failed requests (red)
   - JS/CSS files loading

### Cart Operations Speed
1. Add item to cart
2. Check Network tab
3. Verify AJAX call completes < 1 second

---

## ✅ Daily Health Check Routine

Run this quick 5-minute check daily:

1. **Visit Product List** → Products load?
2. **Add to Cart** → Cart icon updates?
3. **View Cart** → Items show correctly?
4. **Check Admin** → New orders appearing?
5. **Run Health Check** → Score > 80%?

---

## 🚨 Common Issues & Fixes

### Issue: Button shows "$50.00 - Solid"
**Fix**: Clear browser cache, verify product-list.php uses `apd-product-list-cta-btn` class

### Issue: Cart empty after adding items
**Fix**: Check PHP session is started (Health Check → Session Management)

### Issue: No product previews
**Fix**: 
1. Verify templates assigned to products
2. Check Debug Log for errors
3. Clear template cache

### Issue: Orders not saving
**Fix**: 
1. Check file permissions on uploads directory
2. Verify database tables exist
3. Check Debug Log for PHP errors

### Issue: Checkout not redirecting
**Fix**: 
1. Verify Thank You page exists (Health Check → Pages)
2. Check Debug Log for errors
3. Test with debug mode enabled

---

## 📝 Testing Checklist

Print this and check off after each test:

- [ ] Health Check runs (80%+ score)
- [ ] Products can be created
- [ ] Product detail page works
- [ ] Customizer opens and works
- [ ] Cart add/remove works
- [ ] Cart shows correct previews
- [ ] Checkout completes successfully
- [ ] Orders appear in admin
- [ ] No JavaScript console errors
- [ ] File uploads work
- [ ] Debug log shows no critical errors

---

## 🔄 Weekly Maintenance

Every week, run:

1. **Health Check** - Document score
2. **Clear Debug Log** - After reviewing
3. **Test Checkout** - Place test order
4. **Check Order Count** - Verify all processed
5. **Backup Database** - Export WP database

---

## 📞 Getting Help

If tests fail:

1. **Enable Debug Mode** - Capture exact errors
2. **Run Health Check** - Identify specific issues
3. **Check Debug Log** - Look for error patterns
4. **Review Console** - Browser F12 for JS errors
5. **Screenshot Issue** - Visual proof helps diagnosis

---

## 🎓 Advanced Testing

### Load Testing
1. Add 10+ products to cart
2. Change quantities rapidly
3. Verify cart stays stable

### Browser Compatibility
Test on:
- [ ] Chrome
- [ ] Firefox
- [ ] Safari
- [ ] Edge

### Mobile Testing
1. Open site on mobile device
2. Test full flow:
   - Browse products
   - Customize
   - Add to cart
   - Checkout

---

## 📈 Success Metrics

Your plugin is healthy when:

✅ Health Check score > 80%  
✅ 0 critical errors in Debug Log  
✅ All core features tested and working  
✅ Cart operations < 1 second  
✅ Page loads < 3 seconds  
✅ No JavaScript console errors  
✅ Orders processing successfully  

---

**Last Updated**: November 14, 2025  
**Plugin Version**: 2.0.0
