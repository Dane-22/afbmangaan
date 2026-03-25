# Ministries Section Implementation Documentation

## Overview
This document outlines the implementation of the ministries section for the AFB Santol website, featuring a 3-column × 2-row grid layout with expandable functionality to reveal additional ministries.

## Requirements
- Display 7 ministries total: Music Ministry, Pastoral Care, Youth and Children's Ministry, Usher Ministry, Deacon Ministry, Dance Ministry, Multimedia Ministry
- Initial layout: 3 columns × 2 rows (6 ministries visible)
- "See More" button to reveal the 7th ministry (Multimedia Ministry)
- Replaceable images for each ministry
- Center the Multimedia Ministry when revealed

## Files Modified

### 1. `index.php`
**Purpose:** Main HTML structure for the ministries section

**Key Changes:**
- Replaced existing ministry cards with new 7 ministries
- Added image placeholders using Picsum Photos with unique seeds
- Implemented responsive card structure with image and content sections
- Added "See More Ministries" button with dynamic text and icon
- Added `ministry-card--centered` class to Multimedia Ministry for centering

**HTML Structure:**
```html
<section class="ministries-section" id="ministries">
    <div class="section__header">
        <span class="section__eyebrow">Serve With Us</span>
        <h2 class="section__title">Our Ministries</h2>
    </div>
    
    <div class="ministries__grid" id="ministriesGrid">
        <!-- 6 visible ministries -->
        <!-- 1 hidden ministry (Multimedia) -->
    </div>
    
    <div class="ministries__cta">
        <button class="btn btn--outline btn--large" id="seeMoreMinistries">
            <span id="seeMoreText">See More Ministries</span>
            <i class="ph ph-arrow-down" id="seeMoreIcon"></i>
        </button>
    </div>
</section>
```

### 2. `style.css`
**Purpose:** Styling for the ministries section layout and interactions

**Key Changes:**
- Updated `.ministries__grid` to use `grid-template-columns: repeat(3, 1fr)` for exact 3-column layout
- Added `.ministry-card__image` styles with hover zoom effects
- Added `.ministry-card--centered` class for centering the Multimedia Ministry
- Implemented responsive design for mobile and tablet views
- Added `.ministries__cta` styles for the button section

**CSS Grid Layout:**
```css
.ministries__grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.ministry-card--centered {
    grid-column: 2; /* Centers the card in the middle column */
}
```

**Responsive Breakpoints:**
- Desktop: 3 columns
- Tablet (≤768px): 2 columns
- Mobile (≤480px): 1 column

### 3. `animations.js`
**Purpose:** Interactive functionality and animations for the ministries section

**Key Changes:**
- Added `initSeeMoreMinistries()` function for expand/collapse functionality
- Updated `initMinistryCards()` to handle new card structure with images
- Added fallback mechanism for visibility if animations fail
- Implemented smooth animations for showing/hiding additional ministries

**JavaScript Functions:**

#### `initMinistryCards()`
- Sets initial animation state for all ministry cards
- Handles scroll-triggered animations for card entrance
- Added fallback visibility after 2 seconds if animations fail

#### `initSeeMoreMinistries()`
- Manages the "See More/Less" toggle functionality
- Controls visibility of the 7th ministry (Multimedia Ministry)
- Updates button text and icon dynamically
- Implements smooth scroll to reveal hidden content
- Uses `Array.from(allCards).slice(6)` to reliably identify hidden cards

**Animation Logic:**
```javascript
// Initially hide cards beyond the first 6
const visibleCount = 6; // 2 rows × 3 columns
const hiddenCards = Array.from(allCards).slice(visibleCount);

// Toggle visibility with animations
if (isExpanded) {
    // Show hidden cards with staggered animation
    hiddenCards.forEach((card, index) => {
        gsap.fromTo(card, 
            { opacity: 0, y: 30 },
            { opacity: 1, y: 0, duration: 0.6, delay: index * 0.1 }
        );
    });
} else {
    // Hide cards with fade out animation
    gsap.to(hiddenCards, { opacity: 0, y: 30, duration: 0.4 });
}
```

## Implementation Details

### Ministry Cards Structure
Each ministry card contains:
- **Image:** 300×200px placeholder image with hover zoom effect
- **Content:** Title and description
- **Hover Effects:** Card lift and image zoom

### Image Strategy
- Used Picsum Photos with unique seeds for each ministry
- Format: `https://picsum.photos/seed/[ministry-name]/300/200.jpg`
- Seeds ensure consistent images for each ministry
- `loading="lazy"` attribute for performance optimization

### Visibility Control
- **Initial State:** First 6 ministries visible, 7th hidden
- **Trigger:** "See More Ministries" button click
- **Animation:** Smooth fade-in with staggered timing
- **Reversal:** "See Less Ministries" hides the additional ministry

### Centering Logic
- Multimedia Ministry uses `grid-column: 2` to center in 3-column layout
- Responsive design adjusts centering for smaller screens
- Mobile: Stacks vertically (grid-column: 1)

## Troubleshooting & Fixes

### Issue 1: Ministries Not Visible
**Problem:** Cards had `opacity: 0` by default and relied on animations
**Solution:** 
- Changed default CSS to `opacity: 1`
- Added fallback mechanism in JavaScript
- Implemented proper animation state management

### Issue 2: "See More" Button Not Functioning
**Problem:** Unreliable card selection using CSS attribute selectors
**Solution:**
- Replaced `document.querySelectorAll('.ministry-card[style*="display: none"]')` 
- With `Array.from(allCards).slice(6)` for reliable card identification
- Removed inline styles from HTML for cleaner implementation

### Issue 3: Third Row Visibility
**Problem:** All 7 ministries showing in 3 rows
**Solution:**
- Confirmed `visibleCount = 6` in JavaScript
- Added console logging for debugging
- Ensured proper hiding of 7th card on page load

## Features Implemented

### ✅ Core Requirements
- [x] 7 ministries with specified names
- [x] 3-column × 2-row initial layout
- [x] "See More" button functionality
- [x] Replaceable images for each ministry
- [x] Centered Multimedia Ministry when revealed

### ✅ Enhanced Features
- [x] Smooth animations and transitions
- [x] Hover effects on cards and images
- [x] Responsive design for all screen sizes
- [x] Accessibility considerations (alt text, semantic HTML)
- [x] Performance optimization (lazy loading)
- [x] Fallback mechanisms for animation failures

### ✅ Interactive Elements
- [x] Dynamic button text/icon updates
- [x] Smooth scroll to revealed content
- [x] Staggered animation timing
- [x] Toggle functionality (expand/collapse)

## Browser Compatibility
- **Modern Browsers:** Full support (Chrome, Firefox, Safari, Edge)
- **Animations:** GSAP with ScrollTrigger for smooth performance
- **CSS Grid:** Supported in all modern browsers
- **Responsive Design:** Mobile-first approach with breakpoints

## Performance Considerations
- **Image Loading:** Lazy loading for ministry images
- **Animation Performance:** Hardware acceleration with `will-change`
- **JavaScript Efficiency:** Event delegation and proper cleanup
- **CSS Optimization:** Efficient selectors and minimal repaints

## Future Enhancements
- Add actual ministry images to replace placeholders
- Implement individual ministry detail pages
- Add filtering or search functionality
- Include ministry contact information
- Add ministry-specific icons instead of generic images

## Conclusion
The ministries section successfully implements all requirements with a clean, responsive design and smooth user experience. The expandable functionality provides an elegant way to showcase all ministries while maintaining a clean initial layout.
