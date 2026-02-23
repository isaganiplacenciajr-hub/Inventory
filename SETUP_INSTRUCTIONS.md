# Setup Instructions - Modal Implementation with Supplier Category

## Quick Start

### Step 1: Apply Database Migration

**Option A: Via PhpMyAdmin**
1. Open PhpMyAdmin
2. Select your `isagani_inventory` database
3. Go to SQL tab
4. Copy and paste the following SQL:

```sql
ALTER TABLE tbl_product ADD COLUMN supplier_category VARCHAR(200) DEFAULT NULL;
```

5. Click Execute

**Option B: Via Command Line (MySQL)**
```bash
mysql -u root -p isagani_inventory < ui/migrations/002_add_supplier_category.sql
```

**Option C: Automatic (in PHP)**
The system will automatically attempt to add the column on first form submission if it doesn't exist.

---

### Step 2: Verify Files Are in Place

The following files should exist in your installation:

✓ **New API Files:**
- `ui/api/save_product.php` - Product save endpoint
- `ui/api/get_product_details.php` - Product fetch endpoint

✓ **Updated Files:**
- `ui/addproduct.php` - Modal-based form
- `ui/productlist.php` - Modal list with Supplier Category column
- `ui/viewproduct.php` - Updated to display Supplier Category

✓ **Migration Files:**
- `ui/migrations/002_add_supplier_category.sql` - Database migration

✓ **Documentation:**
- `MODAL_IMPLEMENTATION_GUIDE.md` - Detailed implementation documentation

---

### Step 3: Test the Implementation

#### Test Add Product
1. Go to "Add Stock" from the main menu
2. Click "Add New Product" button
3. Form should open in modal popup
4. Notice the new "Supplier Category" field
5. Fill in all fields including Supplier Category
6. Upload product image (optional)
7. Click "Save Product"
8. Should see success notification

#### Test Product List
1. Go to "Product List" page
2. Look for new "Supplier Category" column in table
3. Values should display correctly (or "N/A" if empty)
4. Click "Add New Product" button (top of page)
5. Modal should open

#### Test View/Edit Product
1. In Product List, click View button (eye icon) for any product
2. Details modal should open
3. Check that Supplier Category is displayed
4. Click Edit button
5. Edit modal should open with all data pre-filled
6. Modify Supplier Category if desired
7. Click Save Product
8. Changes should save successfully

#### Test Delete
1. In Product List, click Delete button (trash icon)
2. Confirmation dialog should appear
3. Click Yes to delete
4. Product should be removed from table

---

## Features Overview

### 1. **Modal-Based Add Product**
- Non-intrusive form in popup modal
- Category-based auto-filling of prices and details
- Real-time validation
- Image upload support

### 2. **New "Supplier Category" Field**
- Added to Add Product form
- Displayed in Product List table
- Visible in Product Details modal
- Visible in View Product page

### 3. **Enhanced Product List**
- "Add New Product" button at top
- New "Supplier Category" column
- View product details in modal (eye icon)
- Delete product with confirmation (trash icon)
- DataTable features (sorting, search, pagination)

### 4. **Product Details Modal**
- Clean, organized display
- All product information visible
- Product image display
- Edit button to switch to edit form

---

## Troubleshooting

### Issue: Database column not found error
**Solution:** Run the migration SQL manually:
```sql
ALTER TABLE tbl_product ADD COLUMN supplier_category VARCHAR(200) DEFAULT NULL;
```

### Issue: Modal not opening
**Solution:** 
- Clear browser cache (Ctrl+F5)
- Verify jQuery and Bootstrap are loading (check console)
- Verify `data-toggle="modal"` attributes are present

### Issue: File upload not working
**Solution:**
- Verify `/ui/productimages/` directory exists and is writable
- Check file size is less than 1MB
- Verify file format is: jpg, jpeg, png, or gif

### Issue: Products not showing Supplier Category
**Solution:**
- Verify database migration was applied
- Check database column exists: SELECT * FROM tbl_product LIMIT 1;
- Products added before migration won't have Supplier Category until updated

### Issue: AJAX calls failing
**Solution:**
- Check browser console for errors
- Verify API files exist at:
  - `ui/api/save_product.php`
  - `ui/api/get_product_details.php`
- Check file permissions

---

## File Modifications Reference

### Database Changes
- Added `supplier_category VARCHAR(200)` column to `tbl_product` table

### New Files Created
1. **ui/api/save_product.php** (API endpoint)
   - Handles POST requests for product save
   - Returns JSON responses
   - Automatically creates missing columns on first run

2. **ui/api/get_product_details.php** (API endpoint)
   - Handles GET requests for product details
   - Returns product JSON data
   - Used by view/edit modals

3. **ui/migrations/002_add_supplier_category.sql** (Migration)
   - SQL migration script
   - Adds supplier_category column

4. **MODAL_IMPLEMENTATION_GUIDE.md** (Documentation)
   - Complete implementation details

### Files Updated
1. **ui/addproduct.php**
   - Converted to modal-based interface
   - Added Supplier Category input field
   - AJAX form submission
   - Category auto-fill logic preserved

2. **ui/productlist.php**
   - Added "Add New Product" modal button
   - Added "Supplier Category" column to table
   - Changed View action to modal-based
   - Added View Product modal
   - All AJAX-based operations

3. **ui/viewproduct.php**
   - Added Supplier Category field display
   - Maintained backward compatibility
   - Safe HTML escaping

---

## Key Features Preserved

✓ Category-based auto-filling
✓ Auto-calculation of prices
✓ Product image upload
✓ Stock tracking
✓ Expiry date management
✓ Product delete functionality
✓ Activity logging
✓ Responsive design
✓ DataTable functionality
✓ Toast notifications

---

## User Interface Flow

### Add Stock Menu Item
1. Click "Add Stock" in main menu
2. Click "Add New Product" button
3. Modal opens with form
4. Fill in details including new "Supplier Category"
5. Submit via modal button

### Product List
1. View all products in table
2. See Supplier Category in table column
3. Click Add New Product button (modal)
4. Click View button (eye) for product details modal
5. Edit from view modal
6. Delete with confirmation

### Product Details
1. Standalone page: /ui/viewproduct.php?id=X
2. Shows all details including Supplier Category
3. Or via modal in product list

---

## Rollback Instructions (if needed)

If you need to revert changes:

1. **Remove column from database:**
```sql
ALTER TABLE tbl_product DROP COLUMN supplier_category;
```

2. **Restore original files** from your backup

Note: The system is designed to gracefully handle missing columns, so older files may work without reverting database changes.

---

## Support & Documentation

For detailed information, see:
- `MODAL_IMPLEMENTATION_GUIDE.md` - Full technical documentation
- `ui/migrations/` - Database migration scripts
- `ui/api/` - API endpoint documentation

---

**Status:** ✅ Ready for Production
**Date:** February 23, 2026
