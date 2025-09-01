# Orlando International Resort - Logo Usage Guide

## Logo Overview

The Orlando International Resort logo features a distinctive design with:

- **Palm Tree**: Symbolizing tropical luxury and relaxation
- **Sun**: Representing warmth, hospitality, and the Kenyan climate
- **Waves**: Signifying the resort experience and flow of service
- **Golden Yellow (#FFD700)**: Primary brand color representing luxury and warmth
- **White**: Secondary color for contrast and clarity

## Logo Files Available

### 1. Primary Logo Files

- `images/orlando-logo.svg` - Main logo for web use (200x200px)
- `images/orlando-logo-large.svg` - High-resolution version for print (400x400px)
- `images/favicon.svg` - Favicon for browser tabs (32x32px)

### 2. Usage Guidelines

#### Web Usage

- **Navigation**: Use `orlando-logo.svg` at 50-60px height
- **Banner/Hero**: Use `orlando-logo.svg` at 80-100px height
- **Sidebar**: Use `orlando-logo.svg` at 60px height
- **Favicon**: Use `favicon.svg` for browser tabs

#### Print Usage

- **Business Cards**: Use `orlando-logo-large.svg` at minimum 1 inch height
- **Letterheads**: Use `orlando-logo-large.svg` at 1.5-2 inch height
- **Brochures**: Use `orlando-logo-large.svg` at 2-3 inch height
- **Signage**: Use `orlando-logo-large.svg` at appropriate scale

### 3. Color Specifications

#### Primary Colors

- **Golden Yellow**: #FFD700 (RGB: 255, 215, 0)
- **White**: #FFFFFF (RGB: 255, 255, 255)
- **Black**: #000000 (RGB: 0, 0, 0) - for backgrounds

#### Background Requirements

- **Light Backgrounds**: Use full-color logo
- **Dark Backgrounds**: Use white version (if available)
- **Minimum Contrast**: Ensure 4.5:1 contrast ratio for accessibility

### 4. Spacing and Sizing

#### Clear Space

- Maintain clear space equal to the height of the palm tree trunk
- Never place text or graphics within this clear space area

#### Minimum Size

- **Web**: 32px height minimum
- **Print**: 0.5 inch height minimum
- **Mobile**: 24px height minimum

### 5. Implementation Examples

#### HTML Implementation

```html
<!-- Navigation Logo -->
<img
  src="images/orlando-logo.svg"
  alt="Orlando International Resorts"
  style="width: 50px; height: 50px;"
/>

<!-- Banner Logo -->
<img
  src="images/orlando-logo.svg"
  alt="Orlando International Resorts"
  style="width: 80px; height: 80px;"
/>

<!-- Favicon -->
<link rel="icon" type="image/svg+xml" href="images/favicon.svg" />
```

#### CSS Implementation

```css
.logo {
  width: 50px;
  height: 50px;
  vertical-align: middle;
}

.banner-logo {
  width: 80px;
  height: 80px;
  margin-bottom: 15px;
}
```

### 6. Brand Consistency

#### Do's

- ✅ Use the official logo files provided
- ✅ Maintain proper proportions when scaling
- ✅ Ensure adequate contrast with backgrounds
- ✅ Use consistent sizing across similar contexts
- ✅ Include alt text for accessibility

#### Don'ts

- ❌ Modify the logo colors
- ❌ Stretch or distort the logo
- ❌ Add effects or filters
- ❌ Use outdated or unofficial versions
- ❌ Place text or graphics too close to the logo

### 7. File Locations in System

#### Admin Panel

- **Header**: `admin/includes/header.php` - Sidebar logo
- **Favicon**: `admin/includes/header.php` - Browser tab icon

#### Guest Pages

- **Home Page**: `index.php` - Navigation and banner logos
- **Booking Page**: `booking.php` - Navigation logo
- **Favicon**: Both pages include favicon reference

### 8. Maintenance

#### Regular Checks

- Verify logo displays correctly on all pages
- Ensure favicon appears in browser tabs
- Check logo scaling on different screen sizes
- Validate accessibility compliance

#### Updates

- Logo files are stored in `images/` directory
- SVG format ensures scalability across all devices
- Updates should maintain brand consistency

## Contact

For logo-related questions or updates, contact the development team.

---

_Last Updated: January 2025_
_Version: 1.0_
