# CSS Cleanup Notes

## Removed Styles (Not Used in Current Plugin)

### 1. **Formidable Forms Styles** (Lines 229-254, 330-356)
- `.frm_style_formidable-style` classes
- `.frm_trigger`, `.frm_icon_font`, `.frm_arrow_icon`
- `.frm_forms`, `.frm_submit`, `.frm_message`
- `.frm_hidden_field_qr`
- **Reason**: These appear to be from a Formidable Forms plugin integration that's no longer used

### 2. **Unused Form Field Styles** (Lines 159-192)
- `.hiddenform`
- `input#field_85e80a` and `label#field_85e80a_label`
- `div#frm_field_269_container` and `div#frm_field_193_container`
- `label#field_obamy_label`
- **Reason**: Specific field IDs from old form implementation

### 3. **External Login Widget** (Line 343-345)
- `#bdt-user-login3aaf9bda`
- **Reason**: Appears to be from a specific login widget not in use

### 4. **Unused Booking/API Styles** (Lines 358-410)
- `.qapi_booking_single`, `.qapi_booking_top`, `.qapi_booking_bottom`
- `.qapi_ref`, `.qapi_status`, `.qapi_booking_info`
- `.wcc_info_link`
- `.qapi_resortname`, `.qapi_resort_information`, `.qapi_dates`
- **Reason**: Appears to be from a different booking API integration

### 5. **Unused Animation/Slideshow Styles** (Lines 416-656)
- `.sn_slideshow`, `.sn_slide-wrapper`, `.sn_slide`
- Extensive `@keyframes slide` animations
- `div#crossfade` and related crossfade animations
- **Reason**: Old slideshow implementation not used (current uses simpler `.slideshow`)

### 6. **Miscellaneous Unused Styles**
- `.btn_app` (Line 313-317) - Appears to be for mobile app buttons
- `.cashback_ico` (Line 318-329) - Hardcoded image URL to external site
- `.cashback_block` (Line 287-293) - Related to cashback feature not implemented
- `.sn_area` and `.sn_area h6` (Lines 295-306) - Area display not used
- `.sn_heading` class and related (Lines 691-712) - Not found in current PHP
- `.sn_book_now` (Lines 731-745) - Different book now button not used
- `.sn_similar_match`, `.sn_direct_match` (Lines 669-673) - Search result types not used
- `form#form_querystock` (Lines 244-254) - Old form implementation
- `p.elementor-icon-box-description` (Lines 803-805) - Elementor specific
- `div#iframesn-container iframe` (Lines 806-808) - Specific iframe not used
- `.wprc-copy-link label` (Lines 795-797) - Copy link feature not present

## Optimizations Made

1. **Reorganized sections** for better maintainability
2. **Removed duplicate styles** (multiple color definitions consolidated)
3. **Grouped related styles** together
4. **Kept only actively used classes** based on current PHP implementation
5. **Maintained all responsive styles** that are actually needed

## File Size Reduction
- Original: ~1,381 lines
- Cleaned: ~650 lines
- **Reduction: ~53%**

## Recommendation
Replace `base-stock-styles.css` with `base-stock-styles-cleaned.css` after testing to ensure no visual regressions.