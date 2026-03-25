# Ministries & Activities Implementation Documentation

## Overview
This document outlines the implementation of the ministries and activities sections for the AFB Santol website.

## Requirements Met

### Ministries Section
- **6 ministries visible:** Music Ministry, Pastoral Care, Youth and Children's Ministry, Usher Ministry, Deacon Ministry, Dance Ministry
- **1 hidden ministry:** Multimedia Ministry (revealed via "See More" button)
- **Layout:** 3 columns × 2 rows (6 cards)
- **Features:**
  - "See More Ministries" button reveals hidden Multimedia Ministry
  - Button toggles to "See Less Ministries" when expanded
  - Replaceable placeholder images for each ministry
  - Smooth animations and hover effects
  - Responsive design

### Our Activities Section
- **5 activities:** Youth Collide, Weekly Sunday Service, Prayer Meeting Every Friday, Senior Crew Meeting, Life Group
- **Layout:** Full-viewport horizontal scrollable showcase
- **Features:**
  - Cards fill entire viewport (fullscreen immersive experience)
  - Pinned horizontal scroll (vertical scroll drives horizontal movement)
  - Focus effects: opacity and scale based on card position
  - Hover zoom effects on images
  - Gradient overlays for text readability
  - Responsive for mobile/tablet

## Files Modified

### 1. `index.php`
**Ministries Section:**
- 6 ministry cards in grid layout
- Hidden Multimedia Ministry card with `ministry-card--hidden` class
- "See More Ministries" toggle button
- Replaceable Picsum Photos images

**Activities Section:**
- Full-viewport showcase container (`#activitiesContainer`)
- Horizontal track (`#activitiesTrack`) with 5 activity cards
- Each card contains:
  - Full-bleed image with overlay gradient
  - Meta info (tag + year/schedule)
  - Large title and description
  - Positioned text at bottom

### 2. `style.css`
**Ministries Styles:**
```css
.ministries__grid { grid-template-columns: repeat(3, 1fr); }
.ministry-card--hidden { display: none; } /* Initially hidden */
.ministry-card--centered { grid-column: 2; } /* Centers when revealed */
```

**Activities Styles:**
```css
.activities__container {
    width: 100%;
    height: calc(100vh - 8rem); /* Full viewport */
}

.activities__track {
    display: flex;
    gap: 1rem;
    height: 100%;
}

.activity-card {
    width: calc(100vw - 4rem); /* Nearly full width */
    height: 100%;
    position: relative;
}

.activity-card__media {
    position: absolute;
    inset: 0; /* Full coverage */
}

.activity-card__body {
    position: absolute;
    bottom: 0;
    padding: 3rem 3.5rem;
    z-index: 2;
}
```

### 3. `animations.js`
**Ministries Functionality:**
- `initSeeMoreMinistries()` - Toggles hidden Multimedia Ministry
- Updates button text and icon dynamically
- Smooth fade animations

**Activities Showcase:**
```javascript
function initActivitiesShowcase() {
    // Pinned horizontal scroll
    const showcaseTrigger = ScrollTrigger.create({
        trigger: container,
        start: 'top top',
        end: () => `+=${getScrollDistance()}`,
        pin: true,
        scrub: 1,
        animation: scrollTween,
        onUpdate: () => {
            // Focus effect: opacity + scale based on position
            cards.forEach((card) => {
                gsap.set(card, {
                    opacity: 0.35 + focus * 0.65,
                    y: 0, // No vertical movement
                    scale: 0.98 + focus * 0.02
                });
            });
        }
    });
}
```

## Troubleshooting Fixes Applied

### 1. ScrollTrigger Error (Cannot read properties of undefined)
**Cause:** `containerAnimation` property used incorrectly
**Fix:** Removed `containerAnimation` from timeline item triggers

### 2. GSAP ScrollToPlugin Missing
**Cause:** `gsap.to(window, { scrollTo: ... })` requires plugin
**Fix:** Added `<script src="ScrollToPlugin.min.js">` to HTML

### 3. Ministries Not Visible Initially
**Cause:** Cards had `opacity: 0` by default
**Fix:** Changed CSS to `opacity: 1` with animation initialization

### 4. "See More" Button Not Functioning
**Cause:** Unreliable card selection
**Fix:** Used `document.querySelector('.ministry-card--hidden')` for precise targeting

## Browser Compatibility
- Chrome, Firefox, Safari, Edge (latest)
- GSAP 3.12+ with ScrollTrigger
- Responsive breakpoints: 768px, 480px

## Performance Considerations
- `will-change: transform` on animated elements
- Lazy loading on images
- Hardware acceleration for smooth scrolling

## Future Enhancements
- Replace placeholder images with actual ministry/activity photos
- Add individual detail pages for each ministry/activity
- Include scheduling/calendar integration
- Add filtering or search functionality

## Summary
Both sections now work as requested:
- ✅ Ministries: 6 visible + 1 hidden with toggle button
- ✅ Activities: Full-viewport horizontal scroll showcase with focus effects
- ✅ All animations smooth and responsive
- ✅ Continue scrolling after activities section ends
