<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AFB Santol — A Sacred Journey of Faith</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- GSAP -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollToPlugin.min.js"></script>
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="nav">
        <div class="nav__brand">
            <i class="ph ph-church nav__logo"></i>
            <span class="nav__title">AFB SANTOL</span>
        </div>
        <div class="nav__actions">
            <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                <i class="ph ph-moon" id="themeIcon"></i>
            </button>
            <a href="login.php" class="btn btn--primary">Sign In</a>
        </div>
    </nav>

    <!-- Sacred Seal / Donate Pinned Guide -->
    <div class="sacred-seal" id="sacredSeal">
        <div class="seal__content">
            <i class="ph ph-heart-handshake seal__icon"></i>
            <span class="seal__text">Support</span>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="hero" id="hero">
        <div class="hero__bg-container">
            <div class="hero__bg" id="heroBg"></div>
            <div class="hero__overlay"></div>
        </div>
        <div class="hero__content">
            <div class="hero__eyebrow">Est. 1975</div>
            <h1 class="hero__title">
                <span class="hero__line">Where Faith</span>
                <span class="hero__line">Meets Purpose</span>
            </h1>
            <p class="hero__subtitle">A sacred community walking in divine light, serving two congregations with unwavering devotion.</p>
            <div class="hero__cta">
                <a href="#mission" class="btn btn--light btn--large">
                    <span>Begin Your Journey</span>
                    <i class="ph ph-arrow-down"></i>
                </a>
            </div>
        </div>
        <div class="hero__scroll-indicator">
            <span class="scroll__text">Scroll to Explore</span>
            <div class="scroll__line"></div>
        </div>
    </section>

    <!-- Mission Quote Section (Parallax 1) -->
    <section class="quote-section" id="mission">
        <div class="parallax-layer" data-speed="-0.15">
            <div class="parallax-bg parallax-bg--1"></div>
        </div>
        <div class="quote__content">
            <blockquote class="quote__text">
                "For where two or three gather in my name, there am I with them."
            </blockquote>
            <cite class="quote__cite">— Matthew 18:20</cite>
            <div class="quote__divider">
                <span class="divider__line"></span>
                <i class="ph ph-cross divider__icon"></i>
                <span class="divider__line"></span>
            </div>
        </div>
    </section>

    <!-- Two Churches Section -->
    <section class="churches-section" id="churches">
        <div class="section__header">
            <span class="section__eyebrow">Our Congregations</span>
            <h2 class="section__title">Two Hearts, One Spirit</h2>
        </div>
        
        <div class="churches__grid">
            <!-- AFB Mangaan -->
            <article class="church-card" id="churchMangaan">
                <div class="church-card__image">
                    <div class="church-card__placeholder">
                        <i class="ph ph-church"></i>
                    </div>
                </div>
                <div class="church-card__content">
                    <span class="church-card__label">Main Sanctuary</span>
                    <h3 class="church-card__name">AFB Santol</h3>
                    <p class="church-card__desc">Our founding congregation, where generations have gathered to worship, learn, and serve. A beacon of faith in the community for nearly five decades.</p>
                    <ul class="church-card__meta">
                        <li><i class="ph ph-map-pin"></i> Mangaan, Barangay Proper</li>
                        <li><i class="ph ph-clock"></i> Sundays at 9:00 AM</li>
                        <li><i class="ph ph-users"></i> 200+ Active Members</li>
                    </ul>
                </div>
            </article>

            <!-- AFB Lettac Sur -->
            <article class="church-card" id="churchLettac">
                <div class="church-card__image">
                    <div class="church-card__placeholder church-card__placeholder--secondary">
                        <i class="ph ph-buildings"></i>
                    </div>
                </div>
                <div class="church-card__content">
                    <span class="church-card__label">Branch Fellowship</span>
                    <h3 class="church-card__name">AFB Lettac Sur</h3>
                    <p class="church-card__desc">A growing fellowship extending our mission of love and service. Where new connections blossom and faith communities flourish.</p>
                    <ul class="church-card__meta">
                        <li><i class="ph ph-map-pin"></i> Lettac Sur, Barangay Extension</li>
                        <li><i class="ph ph-clock"></i> Sundays at 9:00 AM</li>
                        <li><i class="ph ph-users"></i> 100+ Active Members</li>
                    </ul>
                </div>
            </article>
        </div>
    </section>

    <!-- Scripture Section (Parallax 2) -->
    <section class="quote-section quote-section--alternate">
        <div class="parallax-layer" data-speed="-0.2">
            <div class="parallax-bg parallax-bg--2"></div>
        </div>
        <div class="quote__content">
            <blockquote class="quote__text quote__text--large">
                "Let your light shine before others, that they may see your good deeds and glorify your Father in heaven."
            </blockquote>
            <cite class="quote__cite">— Matthew 5:16</cite>
        </div>
    </section>

    <!-- Horizontal Timeline Section -->
    <section class="timeline-section" id="history">
        <div class="timeline__header">
            <span class="section__eyebrow">Our Journey</span>
            <h2 class="section__title">A Legacy of Faith</h2>
        </div>
        
        <div class="timeline__container" id="timelineContainer">
            <button class="timeline__arrow timeline__arrow--left" id="timelinePrev" aria-label="Previous timeline item">
                <i class="ph ph-caret-left"></i>
            </button>
            <button class="timeline__arrow timeline__arrow--right" id="timelineNext" aria-label="Next timeline item">
                <i class="ph ph-caret-right"></i>
            </button>
            <div class="timeline__track" id="timelineTrack">
                <!-- Timeline Item 1 -->
                <div class="timeline__item">
                    <div class="timeline__year">1975</div>
                    <div class="timeline__card">
                        <div class="timeline__icon">
                            <i class="ph ph-seedling"></i>
                        </div>
                        <h3 class="timeline__title">Humble Beginnings</h3>
                        <p class="timeline__desc">A small group of faithful believers gathered under a humble roof, planting the seed of what would become a thriving spiritual community.</p>
                    </div>
                </div>

                <!-- Timeline Item 2 -->
                <div class="timeline__item">
                    <div class="timeline__year">1985</div>
                    <div class="timeline__card">
                        <div class="timeline__icon">
                            <i class="ph ph-house"></i>
                        </div>
                        <h3 class="timeline__title">Sanctuary Built</h3>
                        <p class="timeline__desc">Through collective sacrifice and divine providence, the first permanent sanctuary was erected, establishing a home for worship.</p>
                    </div>
                </div>

                <!-- Timeline Item 3 -->
                <div class="timeline__item">
                    <div class="timeline__year">2005</div>
                    <div class="timeline__card">
                        <div class="timeline__icon">
                            <i class="ph ph-users-three"></i>
                        </div>
                        <h3 class="timeline__title">Community Expansion</h3>
                        <p class="timeline__desc">Outreach programs flourished as the church extended its mission beyond walls, serving the needy and welcoming seekers.</p>
                    </div>
                </div>

                <!-- Timeline Item 4 -->
                <div class="timeline__item">
                    <div class="timeline__year">2018</div>
                    <div class="timeline__card">
                        <div class="timeline__icon">
                            <i class="ph ph-buildings"></i>
                        </div>
                        <h3 class="timeline__title">Lettac Sur Branch</h3>
                        <p class="timeline__desc">Responding to growing needs, AFB Lettac Sur was established, bringing the message of hope to a new community.</p>
                    </div>
                </div>

                <!-- Timeline Item 5 -->
                <div class="timeline__item">
                    <div class="timeline__year">2024</div>
                    <div class="timeline__card">
                        <div class="timeline__icon">
                            <i class="ph ph-qr-code"></i>
                        </div>
                        <h3 class="timeline__title">Digital Transformation</h3>
                        <p class="timeline__desc">Embracing modern stewardship with an advanced attendance and analytics system, honoring tradition while innovating for tomorrow.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Ministry Section (Parallax 3) -->
    <section class="quote-section">
        <div class="parallax-layer" data-speed="-0.1">
            <div class="parallax-bg parallax-bg--3"></div>
        </div>
        <div class="quote__content">
            <blockquote class="quote__text">
                "Now you are the body of Christ, and each one of you is a part of it."
            </blockquote>
            <cite class="quote__cite">— 1 Corinthians 12:27</cite>
        </div>
    </section>

    <!-- Ministries Grid -->
    <section class="ministries-section" id="ministries">
        <div class="section__header">
            <span class="section__eyebrow">Serve With Us</span>
            <h2 class="section__title">Our Ministries</h2>
        </div>
        
        <div class="ministries__grid" id="ministriesGrid">
            <!-- Music Ministry -->
            <div class="ministry-card">
                <div class="ministry-card__image">
                    <img src="https://picsum.photos/seed/music-ministry/300/200.jpg" alt="Music Ministry" loading="lazy">
                </div>
                <div class="ministry-card__content">
                    <h3 class="ministry-card__title">Music Ministry</h3>
                    <p class="ministry-card__desc">Leading worship through sacred music and songs that uplift the spirit and glorify God.</p>
                </div>
            </div>

            <!-- Pastoral Care -->
            <div class="ministry-card">
                <div class="ministry-card__image">
                    <img src="https://picsum.photos/seed/pastoral-care/300/200.jpg" alt="Pastoral Care" loading="lazy">
                </div>
                <div class="ministry-card__content">
                    <h3 class="ministry-card__title">Pastoral Care</h3>
                    <p class="ministry-card__desc">Providing spiritual guidance, counseling, and support to our church family in times of need.</p>
                </div>
            </div>

            <!-- Youth and Children's Ministry -->
            <div class="ministry-card">
                <div class="ministry-card__image">
                    <img src="https://picsum.photos/seed/youth-children/300/200.jpg" alt="Youth and Children's Ministry" loading="lazy">
                </div>
                <div class="ministry-card__content">
                    <h3 class="ministry-card__title">Youth and Children's Ministry</h3>
                    <p class="ministry-card__desc">Nurturing young hearts and minds in faith, creating a foundation for lifelong spiritual growth.</p>
                </div>
            </div>

            <!-- Usher Ministry -->
            <div class="ministry-card">
                <div class="ministry-card__image">
                    <img src="https://picsum.photos/seed/usher-ministry/300/200.jpg" alt="Usher Ministry" loading="lazy">
                </div>
                <div class="ministry-card__content">
                    <h3 class="ministry-card__title">Usher Ministry</h3>
                    <p class="ministry-card__desc">Welcoming and assisting congregants with warmth and hospitality, ensuring order during services.</p>
                </div>
            </div>

            <!-- Deacon Ministry -->
            <div class="ministry-card">
                <div class="ministry-card__image">
                    <img src="https://picsum.photos/seed/deacon-ministry/300/200.jpg" alt="Deacon Ministry" loading="lazy">
                </div>
                <div class="ministry-card__content">
                    <h3 class="ministry-card__title">Deacon Ministry</h3>
                    <p class="ministry-card__desc">Serving the church community through practical support, compassion, and dedicated service.</p>
                </div>
            </div>

            <!-- Dance Ministry -->
            <div class="ministry-card">
                <div class="ministry-card__image">
                    <img src="https://picsum.photos/seed/dance-ministry/300/200.jpg" alt="Dance Ministry" loading="lazy">
                </div>
                <div class="ministry-card__content">
                    <h3 class="ministry-card__title">Dance Ministry</h3>
                    <p class="ministry-card__desc">Expressing worship through movement and dance, bringing joy and creative praise to our services.</p>
                </div>
            </div>

            <!-- Multimedia Ministry (Hidden until "See More" is clicked) -->
            <div class="ministry-card ministry-card--hidden" style="display: none;">
                <div class="ministry-card__image">
                    <img src="https://picsum.photos/seed/multimedia-ministry/300/200.jpg" alt="Multimedia Ministry" loading="lazy">
                </div>
                <div class="ministry-card__content">
                    <h3 class="ministry-card__title">Multimedia Ministry</h3>
                    <p class="ministry-card__desc">Enhancing worship experiences through technology, visual media, and sound engineering.</p>
                </div>
            </div>
        </div>

        <!-- See More Button -->
        <div class="ministries__cta">
            <button class="btn btn--outline btn--large" id="seeMoreMinistries">
                <span id="seeMoreText">See More Ministries</span>
                <i class="ph ph-arrow-down" id="seeMoreIcon"></i>
            </button>
        </div>
    </section>

    <section class="activities-section" id="activities">
        <div class="section__header">
            <span class="section__eyebrow">Connect & Grow</span>
            <h2 class="section__title">Our Activities</h2>
        </div>

        <div class="activities__container" id="activitiesContainer">
            <div class="activities__track" id="activitiesTrack">
                <article class="activity-card">
                    <div class="activity-card__media">
                        <img src="https://picsum.photos/seed/youth-collide/900/600.jpg" alt="Youth Collide" loading="lazy">
                        <div class="activity-card__overlay"></div>
                    </div>
                    <div class="activity-card__body">
                        <div class="activity-card__meta">
                            <span class="activity-card__tag">Monthly Gathering</span>
                            <span class="activity-card__year">2026</span>
                        </div>
                        <h3 class="activity-card__title">Youth Collide</h3>
                        <p class="activity-card__desc">A monthly gathering for youth and young adults (ages 12–32) to build friendships, grow in faith, and be encouraged.</p>
                    </div>
                </article>

                <article class="activity-card">
                    <div class="activity-card__media">
                        <img src="https://picsum.photos/seed/sunday-service/900/600.jpg" alt="Weekly Sunday Service" loading="lazy">
                        <div class="activity-card__overlay"></div>
                    </div>
                    <div class="activity-card__body">
                        <div class="activity-card__meta">
                            <span class="activity-card__tag">Weekly</span>
                            <span class="activity-card__year">Every Sunday</span>
                        </div>
                        <h3 class="activity-card__title">Weekly Sunday Service</h3>
                        <p class="activity-card__desc">A weekly time of worship, the Word, and fellowship as one church family.</p>
                    </div>
                </article>

                <article class="activity-card">
                    <div class="activity-card__media">
                        <img src="https://picsum.photos/seed/prayer-meeting/900/600.jpg" alt="Prayer Meeting Every Friday" loading="lazy">
                        <div class="activity-card__overlay"></div>
                    </div>
                    <div class="activity-card__body">
                        <div class="activity-card__meta">
                            <span class="activity-card__tag">Prayer</span>
                            <span class="activity-card__year">Every Friday</span>
                        </div>
                        <h3 class="activity-card__title">Prayer Meeting Every Friday</h3>
                        <p class="activity-card__desc">A rotating prayer gathering per sitio, coming together to pray and intercede for families, the community, and the church.</p>
                    </div>
                </article>

                <article class="activity-card">
                    <div class="activity-card__media">
                        <img src="https://picsum.photos/seed/senior-crew/900/600.jpg" alt="Senior Crew Meeting" loading="lazy">
                        <div class="activity-card__overlay"></div>
                    </div>
                    <div class="activity-card__body">
                        <div class="activity-card__meta">
                            <span class="activity-card__tag">Professionals</span>
                            <span class="activity-card__year">Community</span>
                        </div>
                        <h3 class="activity-card__title">Senior Crew Meeting</h3>
                        <p class="activity-card__desc">A gathering for working professionals and senior young people to connect, learn, and strengthen one another in faith and purpose.</p>
                    </div>
                </article>

                <article class="activity-card">
                    <div class="activity-card__media">
                        <img src="https://picsum.photos/seed/life-group/900/600.jpg" alt="Life Group" loading="lazy">
                        <div class="activity-card__overlay"></div>
                    </div>
                    <div class="activity-card__body">
                        <div class="activity-card__meta">
                            <span class="activity-card__tag">Small Groups</span>
                            <span class="activity-card__year">Ongoing</span>
                        </div>
                        <h3 class="activity-card__title">Life Group</h3>
                        <p class="activity-card__desc">Small-group gatherings that create a safe space to talk about life, share testimonies, and grow through prayer and Scripture.</p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <!-- Final Quote -->
    <section class="quote-section quote-section--final">
        <div class="parallax-layer" data-speed="-0.25">
            <div class="parallax-bg parallax-bg--4"></div>
        </div>
        <div class="quote__content">
            <blockquote class="quote__text quote__text--center">
                "The light shines in the darkness, and the darkness has not overcome it."
            </blockquote>
            <cite class="quote__cite">— John 1:5</cite>
            <a href="#contact" class="btn btn--light btn--large" style="margin-top: 3rem;">
                <span>Join Our Community</span>
                <i class="ph ph-arrow-right"></i>
            </a>
        </div>
    </section>

    <!-- Contact/CTA Section -->
    <section class="cta-section" id="contact">
        <div class="cta__content">
            <span class="cta__eyebrow">Your Journey Begins Here</span>
            <h2 class="cta__title">Walk With Us</h2>
            <p class="cta__desc">Whether you're seeking community, searching for meaning, or looking to serve, there's a place for you in our family.</p>
            
            <div class="cta__actions">
                <a href="attendance.php" class="btn btn--primary btn--large">
                    <i class="ph ph-calendar-check"></i>
                    <span>Take Attendance</span>
                </a>
                <a href="dashboard.php" class="btn btn--outline btn--large">
                    <i class="ph ph-sign-in"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <div class="cta__info">
                <div class="info__item">
                    <i class="ph ph-map-pin"></i>
                    <span>Mangaan & Lettac Sur</span>
                </div>
                <div class="info__item">
                    <i class="ph ph-clock"></i>
                    <span>Sunday Service</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer__brand">
            <i class="ph ph-church"></i>
            <span>AFB Santol</span>
        </div>
        <p class="footer__text">A Divine Light in Our Community Since 1975</p>
        <div class="footer__socials">
            <a href="#" aria-label="Facebook"><i class="ph ph-facebook-logo"></i></a>
            <a href="#" aria-label="Instagram"><i class="ph ph-instagram-logo"></i></a>
            <a href="#" aria-label="YouTube"><i class="ph ph-youtube-logo"></i></a>
        </div>
        <p class="footer__copyright">© 2026 AFB Santol Attendance & Analytics System. All rights reserved.</p>
    </footer>

    <script src="animations.js"></script>
</body>
</html>
