# Site Details Not Showing in Estimation Form - Troubleshooting Guide

## Issue Description
The contractor estimation form is not showing plot size, building size, and other site details even after implementing the enhancements. The fields appear empty in the form.

## Root Cause Analysis
The issue is likely in the data flow chain from `layout_requests` → `contractor_layout_sends` → `contractor inbox` → `estimation form`.

## Troubleshooting Steps

### Step 1: Check Database Tables
**File to run:** `test_contractor_inbox_data_flow.php`

This will check:
- ✅ Layout requests exist with site details
- ✅ Contractor layout sends have the enhanced payload
- ✅ Data flow from source to destination

**Expected Results:**
- Layout requests should have plot_size, building_size, budget_range, etc.
- Contractor layout sends should have layout_request_details in payload
- Data should flow correctly through the chain

### Step 2: Test Enhanced APIs
**File to run:** `test_send_layout_with_site_details.html`

This will:
- ✅ Send a test layout with site details to contractor
- ✅ Verify contractor inbox receives the data
- ✅ Test estimation form data mapping

### Step 3: Debug Estimation Form Data
**File to run:** `debug_estimation_form_data.html`

This will:
- ✅ Check what data is actually in contractor inbox
- ✅ Verify data mapping logic
- ✅ Show expected vs actual data flow

## Common Issues and Fixes

### Issue 1: No Layout Requests with Site Details
**Symptoms:** Layout requests table is empty or missing site details
**Fix:** 
1. Go to homeowner dashboard
2. Create a new layout request
3. Fill in all site details (plot size, building size, budget range, etc.)
4. Submit the request

### Issue 2: Old Layout Sends Without Enhanced Data
**Symptoms:** Contractor layout sends exist but payload doesn't have layout_request_details
**Fix:**
1. Re-send a layout from homeowner to contractor
2. This will trigger the enhanced `send_to_contractor.php` API
3. New payload should include layout_request_details

### Issue 3: Contractor Inbox Not Updated
**Symptoms:** Contractor inbox API doesn't return site details
**Fix:**
1. Verify the enhanced `get_inbox.php` is deployed
2. Check if layout_request_details are being extracted from payload
3. Ensure all site detail fields are being returned

### Issue 4: Estimation Form Not Receiving Data
**Symptoms:** Form fields remain empty despite inbox having data
**Fix:**
1. Check browser console for EstimationForm debug logs
2. Verify the useEffect is triggering when inboxItem changes
3. Ensure formData is being updated correctly

## Enhanced Files Verification

### 1. Enhanced `send_to_contractor.php`
**Key Changes:**
- ✅ Fetches layout_request_details from database
- ✅ Includes comprehensive site details in payload
- ✅ Prioritizes layout request data over other sources

**Verification:**
```php
// Should include this in payload:
'layout_request_details' => $layout_request_details,
'plot_size' => $final_plot_size,
'building_size' => $final_building_size,
```

### 2. Enhanced `get_inbox.php`
**Key Changes:**
- ✅ Extracts layout_request_details from payload
- ✅ Returns individual site detail fields
- ✅ Provides structured data for estimation form

**Verification:**
```php
// Should return these fields:
'plot_size' => $plot_size,
'building_size' => $building_size,
'budget_range' => $budget_range,
'timeline' => $timeline,
'num_floors' => $num_floors,
// ... etc
```

### 3. Enhanced `EstimationForm.jsx`
**Key Changes:**
- ✅ Added console logging for debugging
- ✅ Enhanced data mapping from inbox item
- ✅ Added plot_size and building_size to basic info section
- ✅ Added comprehensive site details section

**Verification:**
```javascript
// Should populate these fields:
plot_size: inboxItem.plot_size || layoutRequestDetails.plot_size || '',
building_size: inboxItem.building_size || layoutRequestDetails.building_size || '',
```

## Quick Fix Steps

### If Site Details Are Still Not Showing:

1. **Run Database Check:**
   ```
   Open: test_contractor_inbox_data_flow.php
   Look for: Layout requests with site details
   ```

2. **Re-send Layout to Contractor:**
   ```
   1. Go to homeowner dashboard
   2. Select a layout or design
   3. Send to contractor (this triggers enhanced API)
   ```

3. **Verify Contractor Inbox:**
   ```
   Open: debug_estimation_form_data.html
   Click: "Debug Contractor Inbox"
   Look for: plot_size, building_size, layout_request_details
   ```

4. **Test Estimation Form:**
   ```
   1. Go to contractor dashboard
   2. Open estimation form from inbox item
   3. Check basic info section for plot size and building size
   4. Check site details section for comprehensive information
   ```

## Debug Console Logs

When opening the estimation form, you should see these console logs:
```
🔍 EstimationForm: Initializing with inbox item: {object}
🔍 EstimationForm: Payload: {object}
🔍 EstimationForm: Layout request details: {object}
🔍 EstimationForm: Parsed requirements: {object}
🔍 EstimationForm: Setting form data: {object}
```

If these logs are missing, the inbox item is not being passed correctly.

## Expected Form Appearance

After fixes, the estimation form should show:

**Basic Project Information Section:**
- Project Name: ✅ Populated
- Client Name: ✅ Populated  
- Location: ✅ Populated
- Timeline: ✅ Populated
- **Plot Size: ✅ Populated** ← Should show value
- **Building Size: ✅ Populated** ← Should show value

**Site Details Section:**
- 📐 Site Specifications (plot size, building size, floors, orientation)
- 💰 Budget & Timeline (budget range, allocation, timeline)
- 🎨 Design Preferences (style, aesthetic, materials)
- 🏠 Requirements (family needs, rooms, development laws)

## Contact Points for Issues

If issues persist after following this guide:
1. Check browser console for JavaScript errors
2. Check server logs for PHP errors
3. Verify database connections and table structures
4. Ensure all enhanced files are properly deployed

## Status Verification

✅ **Working Correctly When:**
- Plot size and building size appear in basic info section
- Site details section shows comprehensive information
- Console logs show proper data flow
- No JavaScript errors in browser console

❌ **Still Has Issues When:**
- Form fields remain empty
- Console shows "NOT SET" for site details
- No layout_request_details in inbox payload
- JavaScript errors in browser console