/**
 * AFB Mangaan - Divine Narrative Animations
 * GSAP ScrollTrigger Implementation
 * 
 * Features:
 * - Hero zoom-out effect
 * - Multi-layer parallax
 * - Horizontal timeline scroll
 * - Pinned sacred seal
 * - Text stagger reveals with blur
 * - Theme-aware animations
 */

// Register GSAP plugins
gsap.registerPlugin(ScrollTrigger);

// Store ScrollTrigger instances for cleanup
const triggers = [];

/**
 * Initialize all animations when DOM is ready
 */
document.addEventListener('DOMContentLoaded', () => {
    initThemeToggle();
    initHeroAnimation();
    initParallaxLayers();
    initQuoteReveals();
    initChurchCards();
    initHorizontalTimeline();
    initMinistryCards();
    initActivitiesShowcase();
    initSeeMoreMinistries();
    initSacredSeal();
    initNavBackground();
    initSmoothScroll();
    
    // Fallback: Show ministry cards if animations don't work after 2 seconds
    setTimeout(() => {
        const cards = document.querySelectorAll('.ministry-card');
        cards.forEach(card => {
            if (gsap.getProperty(card, "opacity") === 0) {
                gsap.set(card, { opacity: 1, y: 0 });
            }
        });
    }, 2000);
});

function initActivitiesShowcase() {
    const container = document.getElementById('activitiesContainer');
    const track = document.getElementById('activitiesTrack');

    if (!container || !track) return;

    const cards = Array.from(track.querySelectorAll('.activity-card'));
    if (!cards.length) return;

    const clamp01 = gsap.utils.clamp(0, 1);

    function getScrollDistance() {
        return Math.max(0, track.scrollWidth - container.clientWidth);
    }

    const scrollTween = gsap.to(track, {
        x: () => -getScrollDistance(),
        ease: 'none',
        paused: true
    });

    const showcaseTrigger = ScrollTrigger.create({
        trigger: container,
        start: 'top top',
        end: () => `+=${getScrollDistance()}`,
        pin: true,
        scrub: 1,
        anticipatePin: 1,
        animation: scrollTween,
        onUpdate: () => {
            const viewportCenter = window.innerWidth / 2;
            const maxDist = window.innerWidth * 0.55;

            cards.forEach((card) => {
                const rect = card.getBoundingClientRect();
                const cardCenter = rect.left + rect.width / 2;
                const dist = Math.abs(viewportCenter - cardCenter);
                const focus = clamp01(1 - dist / maxDist);

                gsap.set(card, {
                    opacity: 0.35 + focus * 0.65,
                    y: 0,
                    scale: 0.98 + focus * 0.02
                });
            });
        }
    });

    triggers.push(showcaseTrigger);

    ScrollTrigger.refresh();
}

/**
 * Theme Toggle Functionality
 * Handles light/dark mode switching with persistence
 */
function initThemeToggle() {
    const toggle = document.getElementById('themeToggle');
    const icon = document.getElementById('themeIcon');
    const html = document.documentElement;
    
    // Check for saved theme preference or default to 'dark'
    const savedTheme = localStorage.getItem('theme') || 'dark';
    html.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);
    
    toggle.addEventListener('click', () => {
        const currentTheme = html.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        html.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateThemeIcon(newTheme);
        
        // Dispatch custom event for other components
        window.dispatchEvent(new CustomEvent('themechange', { detail: { theme: newTheme } }));
    });
    
    function updateThemeIcon(theme) {
        icon.className = theme === 'dark' ? 'ph ph-moon' : 'ph ph-sun';
    }
}

/**
 * Hero Section Animation
 * - Background zooms from scale 1.5 to 1 as user scrolls
 * - Text elements stagger in on page load
 */
function initHeroAnimation() {
    const heroBg = document.getElementById('heroBg');
    const heroLines = document.querySelectorAll('.hero__line');
    const heroEyebrow = document.querySelector('.hero__eyebrow');
    const heroSubtitle = document.querySelector('.hero__subtitle');
    const heroCta = document.querySelector('.hero__cta');
    
    // Hero background zoom-out on scroll
    const zoomTrigger = ScrollTrigger.create({
        trigger: '#hero',
        start: 'top top',
        end: 'bottom top',
        scrub: 1,
        onUpdate: (self) => {
            const scale = 1.5 - (self.progress * 0.5);
            gsap.set(heroBg, { scale: scale });
        }
    });
    triggers.push(zoomTrigger);
    
    // Hero content entrance animation
    const entranceTl = gsap.timeline({ delay: 0.3 });
    
    entranceTl
        .to(heroEyebrow, {
            opacity: 1,
            y: 0,
            duration: 0.8,
            ease: 'power2.out'
        })
        .to(heroLines, {
            opacity: 1,
            y: 0,
            filter: 'blur(0px)',
            duration: 1,
            stagger: 0.15,
            ease: 'power2.out'
        }, '-=0.4')
        .to(heroSubtitle, {
            opacity: 1,
            y: 0,
            duration: 0.8,
            ease: 'power2.out'
        }, '-=0.6')
        .to(heroCta, {
            opacity: 1,
            y: 0,
            duration: 0.8,
            ease: 'power2.out'
        }, '-=0.4');
}

/**
 * Multi-Layer Parallax System
 * - Each parallax layer moves at different speeds
 * - Creates depth illusion behind quote sections
 * - Uses will-change: transform for 60fps performance
 */
function initParallaxLayers() {
    const parallaxLayers = document.querySelectorAll('.parallax-layer');
    
    parallaxLayers.forEach((layer, index) => {
        const speed = parseFloat(layer.dataset.speed) || -0.15;
        
        const parallaxTrigger = ScrollTrigger.create({
            trigger: layer.closest('.quote-section'),
            start: 'top bottom',
            end: 'bottom top',
            scrub: 1,
            onUpdate: (self) => {
                const yPos = self.progress * 100 * speed;
                gsap.set(layer, { yPercent: yPos });
            }
        });
        
        triggers.push(parallaxTrigger);
    });
}

/**
 * Quote Section Text Reveals
 * - Staggered blur-to-clear text reveals
 * - Citation fade-in with slight delay
 * - Divider line animation
 */
function initQuoteReveals() {
    const quoteSections = document.querySelectorAll('.quote-section');
    
    quoteSections.forEach((section) => {
        const text = section.querySelector('.quote__text');
        const cite = section.querySelector('.quote__cite');
        const divider = section.querySelector('.quote__divider');
        
        const quoteTl = gsap.timeline({
            scrollTrigger: {
                trigger: section,
                start: 'top 70%',
                end: 'top 30%',
                scrub: 1,
                toggleActions: 'play reverse play reverse'
            }
        });
        
        quoteTl
            .fromTo(text, 
                { opacity: 0, y: 30, filter: 'blur(5px)' },
                { opacity: 1, y: 0, filter: 'blur(0px)', duration: 1 }
            )
            .fromTo(cite,
                { opacity: 0, y: 20 },
                { opacity: 1, y: 0, duration: 0.8 },
                '-=0.5'
            );
        
        // Animate divider if it exists
        if (divider) {
            const lines = divider.querySelectorAll('.divider__line');
            const icon = divider.querySelector('.divider__icon');
            
            gsap.set(divider, { opacity: 1 });
            
            quoteTl
                .fromTo(lines,
                    { scaleX: 0 },
                    { scaleX: 1, duration: 0.6, stagger: 0.1 },
                    '-=0.3'
                )
                .fromTo(icon,
                    { scale: 0, rotation: -180 },
                    { scale: 1, rotation: 0, duration: 0.5, ease: 'back.out(1.7)' },
                    '-=0.3'
                );
        }
        
        if (quoteTl.scrollTrigger) {
            triggers.push(quoteTl.scrollTrigger);
        }
    });
}

/**
 * Church Cards Stagger Animation
 * - Cards fade in from bottom with stagger
 * - Subtle hover scale effect
 */
function initChurchCards() {
    const cards = document.querySelectorAll('.church-card');
    
    cards.forEach((card, index) => {
        const cardTrigger = ScrollTrigger.create({
            trigger: card,
            start: 'top 85%',
            onEnter: () => {
                gsap.to(card, {
                    opacity: 1,
                    y: 0,
                    duration: 0.8,
                    delay: index * 0.15,
                    ease: 'power2.out'
                });
            },
            once: true
        });
        
        triggers.push(cardTrigger);
    });
}

/**
 * Horizontal Timeline Scroll
 * - Pinned section that scrolls horizontally on vertical scroll
 * - Cards stagger in as they enter viewport
 * - Smooth scrubbing for premium feel
 * - Arrow navigation for manual control
 */
function initHorizontalTimeline() {
    const container = document.getElementById('timelineContainer');
    const track = document.getElementById('timelineTrack');
    const items = document.querySelectorAll('.timeline__item');
    const prevBtn = document.getElementById('timelinePrev');
    const nextBtn = document.getElementById('timelineNext');
    
    if (!container || !track) return;
    
    // Calculate total scroll distance
    const totalWidth = track.scrollWidth - window.innerWidth;
    const itemWidth = 400 + 64; // card width + gap
    let currentIndex = 0;
    const maxIndex = items.length - 1;
    
    // Update arrow button states
    function updateArrowButtons() {
        if (prevBtn) prevBtn.disabled = currentIndex === 0;
        if (nextBtn) nextBtn.disabled = currentIndex === maxIndex;
    }
    
    // Navigate to specific item
    function navigateToItem(index) {
        currentIndex = Math.max(0, Math.min(index, maxIndex));
        
        // Calculate the target scroll position
        // The timeline is pinned, so we need to scroll to the start of the container
        // plus the proportional distance through the horizontal scroll
        const targetProgress = currentIndex / maxIndex;
        const scrollStart = container.offsetTop;
        const scrollDistance = totalWidth;
        const targetScroll = scrollStart + (targetProgress * scrollDistance);
        
        gsap.to(window, {
            duration: 0.8,
            scrollTo: { y: targetScroll, autoKill: false },
            ease: 'power2.inOut',
            onComplete: () => {
                // Update the track position immediately to match
                const xPos = -targetProgress * totalWidth;
                gsap.set(track, { x: xPos });
            }
        });
        
        updateArrowButtons();
    }
    
    // Arrow button event listeners
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            navigateToItem(currentIndex - 1);
        });
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            navigateToItem(currentIndex + 1);
        });
    }
    
    // Pin the timeline section and scroll horizontally
    const horizontalTrigger = ScrollTrigger.create({
        trigger: container,
        start: 'top top',
        end: () => `+=${totalWidth}`,
        pin: true,
        scrub: 1,
        anticipatePin: 1,
        onUpdate: (self) => {
            const xPos = -self.progress * totalWidth;
            gsap.set(track, { x: xPos });
            
            // Update current index based on scroll progress
            currentIndex = Math.round(self.progress * maxIndex);
            updateArrowButtons();
        },
        onEnter: () => {
            // Show sacred seal when entering timeline
            gsap.to('#sacredSeal', { 
                x: 0, 
                duration: 0.5, 
                ease: 'power2.out',
                onStart: () => document.getElementById('sacredSeal').classList.add('visible')
            });
            updateArrowButtons();
        },
        onLeave: () => {
            // Hide sacred seal when leaving timeline
            gsap.to('#sacredSeal', { 
                x: 150, 
                duration: 0.5, 
                ease: 'power2.in',
                onComplete: () => document.getElementById('sacredSeal').classList.remove('visible')
            });
        },
        onLeaveBack: () => {
            // Hide when scrolling back past start
            gsap.to('#sacredSeal', { 
                x: 150, 
                duration: 0.5, 
                ease: 'power2.in',
                onComplete: () => document.getElementById('sacredSeal').classList.remove('visible')
            });
        }
    });
    
    triggers.push(horizontalTrigger);
    
    // Individual item animations
    items.forEach((item, index) => {
        const itemTrigger = ScrollTrigger.create({
            trigger: item,
            start: 'top 85%',
            end: 'top 20%',
            onEnter: () => {
                gsap.to(item, {
                    opacity: 1,
                    x: 0,
                    duration: 0.6,
                    ease: 'power2.out'
                });
            },
            once: true
        });
        
        triggers.push(itemTrigger);
    });
}

/**
 * Ministry Cards Stagger Animation
 * - Grid items fade in with stagger
 * - Image zoom effect on hover
 */
function initMinistryCards() {
    const cards = document.querySelectorAll('.ministry-card');
    
    // Set initial state for animation
    cards.forEach(card => {
        gsap.set(card, { opacity: 0, y: 30 });
        card.classList.add('animated');
    });
    
    cards.forEach((card, index) => {
        const cardTrigger = ScrollTrigger.create({
            trigger: card,
            start: 'top 85%',
            onEnter: () => {
                gsap.to(card, {
                    opacity: 1,
                    y: 0,
                    duration: 0.6,
                    delay: index * 0.1,
                    ease: 'power2.out'
                });
            },
            once: true
        });
        
        triggers.push(cardTrigger);
    });
}

/**
 * See More Ministries Functionality
 * - Shows/hides additional ministry cards
 * - Updates button text and icon
 */
function initSeeMoreMinistries() {
    const seeMoreBtn = document.getElementById('seeMoreMinistries');
    const seeMoreText = document.getElementById('seeMoreText');
    const seeMoreIcon = document.getElementById('seeMoreIcon');

    if (!seeMoreBtn) return;
    
    // Find the hidden Multimedia Ministry card
    const hiddenCard = document.querySelector('.ministry-card--hidden');

    if (!hiddenCard) return;
    
    let isExpanded = false;
    
    seeMoreBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        isExpanded = !isExpanded;
        
        if (isExpanded) {
            // Show the hidden Multimedia Ministry card and center it
            hiddenCard.style.display = 'block';
            hiddenCard.classList.add('ministry-card--centered');
            
            gsap.fromTo(hiddenCard,
                { opacity: 0, y: 30 },
                { 
                    opacity: 1, 
                    y: 0, 
                    duration: 0.6,
                    ease: 'power2.out'
                }
            );
            
            // Update button
            seeMoreText.textContent = 'See Less Ministries';
            seeMoreIcon.className = 'ph ph-arrow-up';
            
        } else {
            // Hide the Multimedia Ministry card
            gsap.to(hiddenCard, {
                opacity: 0,
                y: 30,
                duration: 0.4,
                ease: 'power2.in',
                onComplete: () => {
                    hiddenCard.style.display = 'none';
                    hiddenCard.classList.remove('ministry-card--centered');
                }
            });
            
            // Update button
            seeMoreText.textContent = 'See More Ministries';
            seeMoreIcon.className = 'ph ph-arrow-down';
        }
    });
}

/**
 * Sacred Seal (Pinned Guide)
 * - Follows user during long-form content
 * - Appears after hero, disappears at footer
 */
function initSacredSeal() {
    const seal = document.getElementById('sacredSeal');
    
    // Initial state - hidden
    gsap.set(seal, { x: 150 });
    
    // Show after hero section
    const sealTrigger = ScrollTrigger.create({
        trigger: '#mission',
        start: 'top 80%',
        onEnter: () => {
            if (!seal.classList.contains('timeline-active')) {
                seal.classList.add('visible');
                gsap.to(seal, { x: 0, duration: 0.5, ease: 'power2.out' });
            }
        },
        onLeaveBack: () => {
            seal.classList.remove('visible');
            gsap.to(seal, { x: 150, duration: 0.5, ease: 'power2.in' });
        }
    });
    
    triggers.push(sealTrigger);
    
    // Click handler for seal
    seal.addEventListener('click', () => {
        // Smooth scroll to contact/donate section
        gsap.to(window, {
            duration: 1,
            scrollTo: { y: '#contact', autoKill: false },
            ease: 'power2.inOut'
        });
    });
}

/**
 * Navigation Background
 * - Nav gains solid background on scroll
 * - Smooth transition between transparent and solid
 */
function initNavBackground() {
    const nav = document.querySelector('.nav');
    
    const navTrigger = ScrollTrigger.create({
        trigger: 'body',
        start: '100px top',
        onEnter: () => {
            gsap.to(nav, {
                backgroundColor: 'var(--bg)',
                boxShadow: '0 2px 20px rgba(0,0,0,0.1)',
                duration: 0.3
            });
        },
        onLeaveBack: () => {
            gsap.to(nav, {
                backgroundColor: 'transparent',
                boxShadow: 'none',
                duration: 0.3
            });
        }
    });
    
    triggers.push(navTrigger);
}

/**
 * Smooth Scroll for Anchor Links
 * - Intercepts anchor link clicks
 * - Animates scroll with GSAP for smoothness
 */
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            
            if (target) {
                const offsetTop = target.offsetTop;
                
                gsap.to(window, {
                    duration: 1.2,
                    scrollTo: { y: offsetTop, autoKill: false },
                    ease: 'power2.inOut'
                });
            }
        });
    });
}

/**
 * Cleanup function for page unload
 * - Kills all ScrollTrigger instances
 * - Prevents memory leaks
 */
window.addEventListener('beforeunload', () => {
    triggers.forEach(trigger => trigger.kill());
    ScrollTrigger.getAll().forEach(trigger => trigger.kill());
});

/**
 * Refresh ScrollTrigger on window resize
 * - Recalculates positions for responsive layouts
 */
let resizeTimer;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
        ScrollTrigger.refresh();
    }, 250);
});

/**
 * Theme change handler
 * - Refreshes ScrollTrigger when theme changes
 * - Ensures proper recalculation with new colors
 */
window.addEventListener('themechange', () => {
    // Small delay to allow CSS transitions to complete
    setTimeout(() => {
        ScrollTrigger.refresh();
    }, 600);
});
