# Homeowner Custom Request Form Fields

## 📋 Complete Field Reference

This document lists all existing fields in the custom request form on the homeowner dashboard.

---

## 🏗️ Basic Information Section

### • Plot Size (sq ft) *
- **Field Name:** `plot_size`
- **Type:** Number input
- **Required:** Yes
- **Validation:** Minimum 100
- **Placeholder:** "e.g., 1200"

### • Building Size (sq ft)
- **Field Name:** `building_size`
- **Type:** Number input
- **Required:** No
- **Validation:** Minimum 100
- **Placeholder:** "e.g., 800"

### • Budget Range (₹) *
- **Field Name:** `budget_range`
- **Type:** Select dropdown
- **Required:** Yes
- **Options:**
  - ₹5-10 Lakhs
  - ₹10-20 Lakhs
  - ₹20-30 Lakhs
  - ₹30-50 Lakhs
  - ₹50-75 Lakhs
  - ₹75 Lakhs - 1 Crore
  - ₹1-2 Crores
  - ₹2-5 Crores
  - ₹5+ Crores
  - Custom Amount

### • Custom Budget Amount
- **Field Name:** `custom_budget`
- **Type:** Number input
- **Required:** When "Custom Amount" selected
- **Validation:** Minimum 0, Step 10,000
- **Placeholder:** "Enter custom budget amount in rupees"

---

## 🏞️ Site Details Section

### • Plot Shape
- **Field Name:** `plot_shape`
- **Type:** Select dropdown
- **Required:** No
- **Options:**
  - Rectangular
  - Square
  - L-shaped
  - U-shaped
  - Triangular
  - Irregular
  - Corner Plot
  - Trapezoidal
  - Other

### • Number of Floors *
- **Field Name:** `num_floors`
- **Type:** Select dropdown
- **Required:** Yes
- **Options:**
  - 1 Floor (Ground Floor Only)
  - 2 Floors (G+1)
  - 3 Floors (G+2)
  - 4 Floors (G+3)
  - 5 Floors (G+4)
  - 6+ Floors

### • Topography
- **Field Name:** `topography`
- **Type:** Select dropdown
- **Required:** No
- **Options:**
  - Flat
  - Slightly Sloped
  - Moderately Sloped
  - Steeply Sloped
  - Rocky
  - Sandy
  - Clayey
  - Mixed Terrain
  - Other

### • Local Development Laws / Restrictions
- **Field Name:** `development_laws`
- **Type:** Text input
- **Required:** No
- **Placeholder:** "e.g., Setbacks, FSI/FAR, height limits"

---

## 👨‍👩‍👧‍👦 Family & Design Preferences Section

### • Family Needs
- **Field Name:** `family_needs`
- **Type:** Multi-select dropdown (Array)
- **Required:** No
- **Options:**
  - Elder-friendly
  - Work-from-home
  - Kids play area
  - Pet-friendly
  - Wheelchair accessible
  - Home office
  - Guest accommodation
  - Storage space
  - Garden/Outdoor space
  - Security features
  - Energy efficient
  - Low maintenance

### • Rooms
- **Field Name:** `rooms`
- **Type:** Multi-select dropdown (Array)
- **Required:** No
- **Options:**
  - 1 Bedroom
  - 2 Bedrooms
  - 3 Bedrooms
  - 4 Bedrooms
  - 5+ Bedrooms
  - 1 Bathroom
  - 2 Bathrooms
  - 3 Bathrooms
  - 4+ Bathrooms
  - Living Room
  - Dining Room
  - Kitchen
  - Study Room
  - Puja Room
  - Guest Room
  - Store Room
  - Balcony
  - Terrace
  - Garage
  - Utility Area

### • House Aesthetic / Style
- **Field Name:** `aesthetic`
- **Type:** Select dropdown
- **Required:** No
- **Options:**
  - Modern
  - Contemporary
  - Traditional
  - Minimalist
  - Luxury
  - Mediterranean
  - Colonial
  - Victorian
  - Art Deco
  - Scandinavian
  - Industrial
  - Rustic
  - Farmhouse
  - Craftsman
  - Tudor
  - Ranch
  - Cape Cod
  - Other

### • Style Preferences (AI Matching)
- **Field Name:** `style_preferences`
- **Type:** Multi-select button grid (Object)
- **Required:** No
- **Options:**
  - Modern 🏢
  - Contemporary ✨
  - Minimalist ⚪
  - Traditional 🏛️
  - Luxury 💎
  - Sustainable 🌱
  - Eco-friendly ♻️
  - Natural 🌿
  - Aesthetic 🎨
  - Functional ⚙️
  - Elegant 👑
  - Innovative 💡

### • AI-Powered House Style Recommendations
- **Field Name:** `ai_suggested_style`
- **Type:** AI Component Integration
- **Required:** No
- **Description:** Provides personalized style suggestions based on form data

---

## 📍 Location & Timeline Section

### • State *
- **Field Name:** `location`
- **Type:** Select dropdown with optgroups
- **Required:** Yes
- **Options:** All Indian states organized by regions:
  - **South India:** Andhra Pradesh, Karnataka, Kerala, Tamil Nadu, Telangana, Puducherry, Lakshadweep, Andaman and Nicobar Islands
  - **North India:** Delhi, Haryana, Himachal Pradesh, Jammu and Kashmir, Ladakh, Punjab, Rajasthan, Uttar Pradesh, Uttarakhand, Chandigarh
  - **East India:** Bihar, Jharkhand, Odisha, West Bengal
  - **West India:** Goa, Gujarat, Maharashtra, Dadra and Nagar Haveli and Daman and Diu
  - **Central India:** Chhattisgarh, Madhya Pradesh
  - **Northeast India:** Arunachal Pradesh, Assam, Manipur, Meghalaya, Mizoram, Nagaland, Sikkim, Tripura

### • District *
- **Field Name:** `district`
- **Type:** Dynamic select dropdown
- **Required:** When state is selected
- **Options:** Populated based on selected state

### • Panchayat / Municipality *
- **Field Name:** `panchayat_municipality`
- **Type:** Dynamic select dropdown
- **Required:** For Kerala state only
- **Options:** Populated based on selected district (Kerala specific)

### • Timeline
- **Field Name:** `timeline`
- **Type:** Select dropdown
- **Required:** No
- **Options:**
  - 0-6 months
  - 6-12 months
  - 12-18 months
  - 18-24 months

---

## 📝 Additional Information Section

### • Requirements / Customization Notes
- **Field Name:** `requirements`
- **Type:** Textarea (4 rows)
- **Required:** Yes for library layout customization, No for custom requests
- **Placeholder:** 
  - Library: "Describe any modifications you'd like to make to the selected layout: room changes, additional features, material preferences, etc."
  - Custom: "Any other preferences or constraints"

---

## 🔧 Technical Fields (Internal)

### • Selected Layout ID
- **Field Name:** `selected_layout_id`
- **Type:** Hidden/Internal field
- **Required:** No
- **Description:** Used when customizing library layouts

### • Layout Type
- **Field Name:** `layout_type`
- **Type:** Hidden/Internal field
- **Required:** No
- **Options:** 'custom' or 'library'
- **Description:** Determines form behavior and validation

---

## 👨‍💼 Architect Selection Section

### • Selected Architect IDs
- **Field Name:** `selectedArchitectId`
- **Type:** Integrated component (Array)
- **Required:** No
- **Description:** Allows selection of multiple architects with AI recommendations

### • Message to Architect
- **Field Name:** `assignMessage`
- **Type:** Textarea (2 rows)
- **Required:** No
- **Placeholder:** "Add any notes for the architect"

---

## 📊 Field Summary

| **Category** | **Field Count** | **Required Fields** |
|--------------|-----------------|-------------------|
| Basic Information | 4 | 2 |
| Site Details | 4 | 1 |
| Family & Design | 4 | 0 |
| Location & Timeline | 4 | 1-3 (varies) |
| Additional Info | 1 | 0-1 (varies) |
| Technical | 2 | 0 |
| Architect Selection | 2 | 0 |
| **TOTAL** | **21** | **4-7** |

---

## 🎯 Form Features

### ✅ Advanced UI Components
- Multi-select dropdowns with Ctrl/Cmd support
- Dynamic field population based on selections
- AI-powered style recommendations
- Integrated architect selection with recommendations
- Conditional field display and validation

### ✅ Validation & UX
- Real-time form validation
- Required field indicators (*)
- Contextual placeholders and help text
- Error handling and user feedback
- Form state persistence

### ✅ Smart Features
- AI-based architect matching
- Location-specific field population
- Budget range with custom option
- Style preference visualization
- Integrated architect communication

---

## 📋 Usage Notes

1. **Required Fields:** Marked with asterisk (*) - minimum viable form submission
2. **Multi-select Fields:** Use Ctrl/Cmd + click for multiple selections
3. **Dynamic Fields:** District and Panchayat/Municipality populate based on state selection
4. **AI Features:** Style preferences enable AI architect recommendations
5. **Form Types:** Behavior changes based on custom vs library layout requests
6. **Validation:** Client-side validation with server-side verification
7. **Persistence:** Form data maintained during session for better UX

This comprehensive form captures all essential information needed for architects to create customized house plans that meet specific homeowner requirements, budget constraints, and design preferences.