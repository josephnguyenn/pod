# 🚀 APD Quick Monitoring Guide

## Daily Quick Check (2 minutes)

### 1. Visual Check
```
✅ Product list loads
✅ Cart icon shows
✅ No PHP errors on screen
```

### 2. Function Check
```
1. Click product → Detail page opens ✅
2. Add to cart → Success message ✅
3. View cart → Items show ✅
```

### 3. Admin Check
```
Dashboard → APD Dashboard
- Health Check score shown
- Recent orders visible
- No error notices
```

---

## Weekly Deep Check (10 minutes)

### Run Health Check
```bash
APD Dashboard → Health Check → "Run Health Check"
Target: 80%+ score
```

### Review Debug Log
```bash
APD Dashboard → Debug Log
Look for: ERROR or WARNING patterns
Action: If > 100 lines, investigate
```

### Test Full Flow
```bash
1. Browse products (1 min)
2. Customize product (2 min)
3. Add to cart (30 sec)
4. Checkout (2 min)
5. Verify order in admin (1 min)
```

---

## 🚨 Alert Thresholds

### Critical (Fix Immediately)
- Health score < 60%
- Cart not working
- Checkout failing
- Multiple ERROR logs

### Warning (Fix Soon)
- Health score 60-79%
- Slow page loads (>5sec)
- Template previews failing
- Multiple WARNING logs

### Monitor (Check Later)
- Health score 80-89%
- Minor CSS issues
- Non-critical warnings

---

## 🔍 Where to Check Issues

| Issue | Check Location |
|-------|---------------|
| Products not showing | Health Check → Products section |
| Cart problems | Debug Log + Browser console |
| Checkout failing | Debug Log → Search "order" |
| Slow performance | Browser DevTools → Network tab |
| Button text wrong | Clear cache + check console |
| Images not loading | Health Check → File Structure |

---

## 📊 Key Metrics to Track

```
✅ Health Score: _____% (Target: 80%+)
✅ Active Products: _____ (Target: 1+)
✅ Templates: _____ (Target: 1+)
✅ Orders This Week: _____
✅ Cart Success Rate: _____% (Target: 95%+)
✅ Error Count: _____ (Target: 0)
```

---

## 🛠️ Emergency Fixes

### If site is broken:
```bash
1. Enable Debug Mode
   APD Dashboard → Debug Log → Enable

2. Check last entries
   Look for ERROR messages

3. Disable recently changed settings
   APD Dashboard → Settings → Revert changes

4. Clear all caches
   Browser cache + WordPress cache plugins
```

### If checkout broken:
```bash
1. Check pages exist:
   Health Check → Pages section

2. Test AJAX:
   Browser F12 → Console → Look for errors

3. Check session:
   Health Check → Session Management
```

### If cart empty:
```bash
1. Check PHP sessions:
   Health Check → Session Management

2. Test in different browser
   Incognito/Private mode

3. Check Debug Log
   Search for "cart" or "session"
```

---

## 📱 Browser Console Quick Reference

Open: Press **F12** or **Ctrl+Shift+I**

**Look for:**
- ❌ Red errors = Critical
- ⚠️ Yellow warnings = Monitor
- ℹ️ Blue info = Normal

**Common errors to fix:**
- "jQuery is not defined" → Check script loading order
- "apd_ajax is not defined" → Script not enqueued
- 404 errors → Check file paths

---

## ✅ Quick Test Commands

### Test Cart AJAX (Browser Console)
```javascript
// Add item to cart
jQuery.post(apd_ajax.ajax_url, {
    action: 'apd_add_to_cart',
    nonce: apd_ajax.nonce,
    product_id: 123,
    quantity: 1
}, console.log);
```

### Test Get Cart
```javascript
// Get cart contents
jQuery.post(apd_ajax.ajax_url, {
    action: 'apd_get_cart',
    nonce: apd_ajax.nonce
}, console.log);
```

---

## 📞 Support Checklist

Before asking for help, collect:

```
✅ Health Check screenshot
✅ Debug Log (last 50 lines)
✅ Browser console errors
✅ Steps to reproduce issue
✅ WordPress version
✅ PHP version
✅ Active plugins list
```

---

## 🎯 Monthly Audit

Once per month:

1. **Full Health Check** - Document results
2. **Review all products** - Remove unused
3. **Check order history** - Verify all processed
4. **Test all features** - Full testing guide
5. **Update documentation** - Note any changes
6. **Backup database** - Safety first
7. **Review debug logs** - Look for patterns
8. **Performance test** - Check page speeds

---

## 📈 Performance Benchmarks

| Metric | Good | Acceptable | Poor |
|--------|------|------------|------|
| Health Score | 90%+ | 80-89% | <80% |
| Page Load | <2s | 2-4s | >4s |
| Cart Add | <1s | 1-2s | >2s |
| Checkout | <3s | 3-5s | >5s |
| Error Rate | 0% | <1% | >1% |

---

## 🔄 Update Checklist

When updating plugin:

1. ✅ Backup database
2. ✅ Document current settings
3. ✅ Run health check (before)
4. ✅ Update files
5. ✅ Run health check (after)
6. ✅ Test core features
7. ✅ Check debug log
8. ✅ Clear all caches

---

**Print this page and keep it handy!**

**Emergency Contact**: Check TESTING-GUIDE.md for detailed troubleshooting
