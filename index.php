<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CEMO - City Environment Managemeng Office</title>
    
      <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
  </head>
  <body>
    <div class="main-bg" id="home">
      <!-- Falling Leaves Container -->
      <div id="falling-leaves"></div>
    
    <!-- Header -->
    <header class="header">
      <div class="container">
        <nav class="nav">
                    <div class="logo">
                        <div class="logo-icon">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M7 19H6.5a2.5 2.5 0 0 1 0-5H14"/>
                                <path d="m10.5 2-1.5 2"/>
                                <path d="m13.5 2 1.5 2"/>
                                <path d="M12 15V9"/>
                                <path d="m8 8 4-4 4 4"/>
                                <path d="M7 19h10a2 2 0 0 0 1.85-2.77L17 14H7l-1.85 2.77A2 2 0 0 0 7 19Z"/>
                            </svg>
                        </div>
                        <span class="logo-text">CEMO</span>
                    </div>
                    <div class="nav-links">
                        <a href="#home" class="nav-link">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M7 19H6.5a2.5 2.5 0 0 1 0-5H14"/>
                                <path d="m10.5 2-1.5 2"/>
                                <path d="m13.5 2 1.5 2"/>
                                <path d="M12 15V9"/>
                                <path d="m8 8 4-4 4 4"/>
                                <path d="M7 19h10a2 2 0 0 0 1.85-2.77L17 14H7l-1.85 2.77A2 2 0 0 0 7 19Z"/>
                            </svg>
                            Home
                        </a>
                        <a href="#services" class="nav-link">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m17 14 3 3.3a1 1 0 0 1-.7 1.7H4.7a1 1 0 0 1-.7-1.7L7 14h-.3a1 1 0 0 1-.7-1.7l2.6-3a1 1 0 0 1 1.4 0l2.6 3a1 1 0 0 1-.7 1.7H17Z"/>
                                <path d="m2 2 20 20"/>
                            </svg>
                            Solutions
                        </a>
                        <a href="#cta" class="nav-link">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/>
                                <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>
                            </svg>
                            About
                        </a>
                        <a href="#contact" class="nav-link">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/>
                                <path d="m10 15-3-3 3-3"/>
                                <path d="m14 9 3 3-3 3"/>
                            </svg>
                            Contact
                        </a>
                    </div>
                    <a href="login_page/sign-in.php" class="btn btn-primary">Get Started</a>
                </nav>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="hero">
            <div class="container">
                <div class="hero-box">
                    <h1 class="hero-title">
                        <span class="gradient-text">WasteVision AI</span>
                        : Predictive Waste Intelligence for Resilient and Sustainable Urban Futures
                    </h1>   
                    <p class="hero-subtitle">
                        A next-generation IoT waste vehicle tracking and predictive analytics system designed to enhance efficiency, reduce costs, and promote sustainability
                    </p>

                <div class="hero-buttons">
                    <a href="login_page/sign-in.php" class="btn btn-primary btn-lg">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M7 19H6.5a2.5 2.5 0 0 1 0-5H14"/>
                            <path d="m10.5 2-1.5 2"/>
                            <path d="m13.5 2 1.5 2"/>
                            <path d="M12 15V9"/>
                            <path d="m8 8 4-4 4 4"/>
                            <path d="M7 19h10a2 2 0 0 0 1.85-2.77L17 14H7l-1.85 2.77A2 2 0 0 0 7 19Z"/>
                        </svg>
                        Start Monitor Today
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14"/>
                            <path d="m12 5 7 7-7 7"/>
                        </svg>
                    </a>
                    <a href="#contact" class="btn btn-secondary btn-lg">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m17 14 3 3.3a1 1 0 0 1-.7 1.7H4.7a1 1 0 0 1-.7-1.7L7 14h-.3a1 1 0 0 1-.7-1.7l2.6-3a1 1 0 0 1 1.4 0l2.6 3a1 1 0 0 1-.7 1.7H17Z"/>
                            <path d="m2 2 20 20"/>
                        </svg>
                        Learn More
                    </a>
                </div>
            </div>
        </section>
                

        <!-- Services Section -->
        <section id="services" class="services">
            <div class="container">
                <h2 class="section-title">System Capabilities</h2>
                <p class="section-subtitle">
                    End-to-end IoT waste management with real-time monitoring, AI forecasting, and actionable insights
                </p>

                <div class="services-grid">
                    <div class="service-card">
                        <div class="service-icon emerald">
                            <svg class="icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M7 19H6.5a2.5 2.5 0 0 1 0-5H14"/>
                                <path d="m10.5 2-1.5 2"/>
                                <path d="m13.5 2 1.5 2"/>
                                <path d="M12 15V9"/>
                                <path d="m8 8 4-4 4 4"/>
                                <path d="M7 19h10a2 2 0 0 0 1.85-2.77L17 14H7l-1.85 2.77A2 2 0 0 0 7 19Z"/>
                            </svg>
                        </div>
                        <h3 class="service-title">Real-time Vehicle Monitoring</h3>
                        <p class="service-description">
                            Live GPS tracking of collection vehicles with recent trails, last update time, and status on the map
                        </p>
                    </div>

                    <div class="service-card teal">
                        <div class="service-icon teal">
                            <svg class="icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m17 14 3 3.3a1 1 0 0 1-.7 1.7H4.7a1 1 0 0 1-.7-1.7L7 14h-.3a1 1 0 0 1-.7-1.7l2.6-3a1 1 0 0 1 1.4 0l2.6 3a1 1 0 0 1-.7 1.7H17Z"/>
                                <path d="m2 2 20 20"/>
                            </svg>
                        </div>
                        <h3 class="service-title">Route Optimization</h3>
                        <p class="service-description">
                            Optimize routes to reduce travel time with dynamic re-routing based on live conditions
                        </p>
                    </div>

                    <div class="service-card blue">
                        <div class="service-icon blue">
                            <svg class="icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/>
                                <path d="m10 15-3-3 3-3"/>
                                <path d="m14 9 3 3-3 3"/>
                            </svg>
                        </div>
                        <h3 class="service-title">Predictive Waste Forecasting</h3>
                        <p class="service-description">
                            Machine learning forecasts of weekly and monthly waste volumes per barangay to plan collection
                        </p>
                    </div>

                    <div class="service-card">
                        <div class="service-icon emerald">
                            <svg class="icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17.7 7.7a2.5 2.5 0 1 1 1.8 4.3H2"/>
                                <path d="M9.6 4.6A2 2 0 1 1 11 8H2"/>
                                <path d="M12.6 19.4A2 2 0 1 0 14 16H2"/>
                            </svg>
                        </div>
                        <h3 class="service-title">Health Risk Mapping</h3>
                        <p class="service-description">
                            Visualize predicted health risks areas and hotspots using sensor data, ML models, and community reports
                        </p>
                    </div>

                    <div class="service-card teal">
                        <div class="service-icon teal">
                            <svg class="icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/>
                                <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>
                            </svg>
                        </div>
                        <h3 class="service-title">Smart Notifications</h3>
                        <p class="service-description">
                            Real-time alerts for schedule changes, unexpected delays, and request updates for admins and clients
                        </p>
                    </div>

                    <div class="service-card blue">
                        <div class="service-icon blue">
                            <svg class="icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M7 19H6.5a2.5 2.5 0 0 1 0-5H14"/>
                                <path d="m10.5 2-1.5 2"/>
                                <path d="m13.5 2 1.5 2"/>
                                <path d="M12 15V9"/>
                                <path d="m8 8 4-4 4 4"/>
                                <path d="M7 19h10a2 2 0 0 0 1.85-2.77L17 14H7l-1.85 2.77A2 2 0 0 0 7 19Z"/>
                            </svg>
                        </div>
                        <h3 class="service-title">Analytics & Reporting</h3>
                        <p class="service-description">
                            Role-based dashboards, KPIs, and exportable reports for operations, planning, and compliance
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta" id="cta">
            <div class="container">
                <h2 class="cta-title">Ready to Go Green?</h2>
                <p class="cta-subtitle">
                    Join hundreds of businesses already making a positive environmental impact with our innovative waste solutions
                </p>
                <div class="cta-buttons">
                    <a href="#consultation" class="btn btn-white btn-lg">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                        Schedule Consultation
                    </a>
                    <a href="#quote" class="btn btn-outline-white btn-lg">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        Get Free Quote
                    </a>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer" id ="contact">
            <div class="container">
                <div class="footer-grid">
                    <div class="footer-section">
                        <div class="logo" style="margin-bottom: 1rem;">
                            <div class="logo-icon">
                                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M7 19H6.5a2.5 2.5 0 0 1 0-5H14"/>
                                    <path d="m10.5 2-1.5 2"/>
                                    <path d="m13.5 2 1.5 2"/>
                                    <path d="M12 15V9"/>
                                    <path d="m8 8 4-4 4 4"/>
                                    <path d="M7 19h10a2 2 0 0 0 1.85-2.77L17 14H7l-1.85 2.77A2 2 0 0 0 7 19Z"/>
                                </svg>
                            </div>
                            <span class="logo-text" style="color: white;">CEMO</span>
                        </div>
                        <p style="color: #d1fae5;">
                            Leading the way in sustainable waste management solutions for a cleaner tomorrow.
                        </p>
                    </div>
                    
                    <div class="footer-section">
                        <h4>Services</h4>
                        <ul class="footer-links">
                            <li><a href="https://www.google.com/search?q=Smart+Recycling" target="_blank" rel="noopener noreferrer">Smart Recycling</a></li>
                            <li><a href="https://www.google.com/search?q=Organic+Composting" target="_blank" rel="noopener noreferrer">Organic Composting</a></li>
                            <li><a href="https://www.google.com/search?q=Water+Recovery" target="_blank" rel="noopener noreferrer">Water Recovery</a></li>
                            <li><a href="https://www.google.com/search?q=Zero+Emissions" target="_blank" rel="noopener noreferrer">Zero Emissions</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-section">
                        <h4>Company</h4>
                        <ul class="footer-links">
                            <li><a href="#">About Us</a></li>
                            <li><a href="#">Careers</a></li>
                            <li><a href="#">News</a></li>
                            <li><a href="#">Sustainability</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-section">
                        <h4>Contact</h4>
                        <div class="footer-contact">
                            <div class="footer-contact-item">
                                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                </svg>
                                <span>+63 9482367208</span>
                            </div>
                            <div class="footer-contact-item">
                                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                    <polyline points="22,6 12,13 2,6"/>
                                </svg>
                                <span>cemo.waste.tracker@gmail.com</span>
                            </div>
                            <div class="footer-contact-item">
                                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                                <span>Bago City, Negros Occidental</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="footer-bottom">
                    <p>&copy; 2025 CEMO. All rights reserved. Building a sustainable future together.</p>
                </div>
            </div>
        </footer>
    </div>
    <!-- Loading Overlay -->
<div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(6, 78, 59, 0.9); z-index: 9999; justify-content: center; align-items: center; flex-direction: column; color: white; font-family: sans-serif;">
    <div class="loading-spinner"></div>
    <p style="margin-top: 1rem; font-size: 1.2rem;">Loading Your Waste Management Journey...</p>
</div>
</body>
</html>
    <script>
        // Falling Leaves Animation
        function createFallingLeaves() {
            const leavesContainer = document.getElementById('falling-leaves');
            const leafCount = 15;
            
            for (let i = 0; i < leafCount; i++) {
                const leaf = document.createElement('div');
                leaf.className = 'falling-leaf';
                leaf.innerHTML = '🍃';
                
                // Random positioning and timing
                leaf.style.left = Math.random() * 100 + '%';
                leaf.style.animationDelay = Math.random() * 5 + 's';
                leaf.style.animationDuration = (3 + Math.random() * 4) + 's';
                
                leavesContainer.appendChild(leaf);
            }
        }

        // Smooth scrolling for navigation links
        function setupSmoothScrolling() {
            const links = document.querySelectorAll('a[href^="#"]');
            
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href');
                    const targetElement = document.querySelector(targetId);
                    
                    if (targetElement) {
                        targetElement.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        }

        // Initialize animations and interactions
        document.addEventListener('DOMContentLoaded', function() {
            createFallingLeaves();
            setupSmoothScrolling();
        });

        // Add parallax effect to hero section
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const hero = document.querySelector('.hero');
            if (hero) {
                hero.style.transform = `translateY(${scrolled * 0.5}px)`;
            }
        });

        // Intercept "Get Started" and "Start Monitor Today" buttons
        document.addEventListener('DOMContentLoaded', function () {
            const buttons = document.querySelectorAll('a[href="login_page/sign-in.php"]');

            buttons.forEach(button => {
                button.addEventListener('click', function (e) {
                    e.preventDefault(); // Prevent immediate navigation

                    const loadingOverlay = document.getElementById('loading-overlay');
                    loadingOverlay.style.display = 'flex'; // Show loader

                    // After 1.5 seconds, redirect
                    setTimeout(() => {
                        window.location.href = "login_page/sign-in.php";
                    }, 1500);
                });
            });
        });

        // Intercept CTA buttons: Schedule Consultation and Get Free Quote
        document.addEventListener('DOMContentLoaded', function () {
            const consultationBtn = document.querySelector('a[href="#consultation"]');
            const quoteBtn = document.querySelector('a[href="#quote"]');

            function showUnavailableAlert(e) {
                e.preventDefault();
                if (window.Swal && typeof Swal.fire === 'function') {
                    Swal.fire({
                        icon: 'info',
                        title: 'Not available right now',
                        text: 'This feature is currently unavailable. Please check back later.',
                        confirmButtonColor: '#059669'
                    });
                } else {
                    alert('This feature is currently unavailable. Please check back later.');
                }
            }

            if (consultationBtn) consultationBtn.addEventListener('click', showUnavailableAlert);
            if (quoteBtn) quoteBtn.addEventListener('click', showUnavailableAlert);
        });

        // Intercept Company footer links to show not available alert
        document.addEventListener('DOMContentLoaded', function () {
            const footerSections = document.querySelectorAll('.footer .footer-section');
            let companyLinks = [];

            footerSections.forEach(section => {
                const heading = section.querySelector('h4');
                if (heading && heading.textContent.trim().toLowerCase() === 'company') {
                    companyLinks = Array.from(section.querySelectorAll('ul.footer-links a'));
                }
            });

            function showUnavailable(e) {
                e.preventDefault();
                if (window.Swal && typeof Swal.fire === 'function') {
                    Swal.fire({
                        icon: 'info',
                        title: 'Not available right now',
                        text: 'This feature is currently unavailable. Please check back later.',
                        confirmButtonColor: '#059669'
                    });
                } else {
                    alert('This feature is currently unavailable. Please check back later.');
                }
            }

            companyLinks.forEach(link => link.addEventListener('click', showUnavailable));
        });
    </script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            padding-top: 80px; /* Adjust based on header height */
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #064e3b;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Falling Leaves Animation */
        @keyframes fall {
            0% {
                transform: translateY(-100vh) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }

        .falling-leaf {
            position: fixed;
            color: #00f62d;
            opacity: 1.6;
            animation: fall linear infinite;
            pointer-events: none;
            z-index: 10;
            font-size: 16px;
        }

        /* Background */
        .main-bg {
        background: linear-gradient(180deg,rgba(60, 158, 63, 0.82) 0%, rgba(87, 199, 133, 0.22) 66%, rgba(0, 0, 0, 0) 100%),
        url('assets/img/bago.jpg') no-repeat center center/cover; 
      /* color: white; */
      height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      text-align: center;
      padding: 20px;
      position: relative;
    }
        /* Full-width Header */
        .header {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid #d1fae5;
            width: 100vw;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo-icon {
        background: url('assets/img/logo.png') no-repeat center center/cover;
            padding: 0.5rem;
            border-radius: 50%;
            color: white;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: bold;
            color: #064e3b;
        }

        .nav-links {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-right: 1rem;
        margin-left: auto; /* Pushes remaining space toward the button */
    }

        @media (min-width: 768px) {
            .nav-links {
                display: flex;
            }
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            color: #047857;
            text-decoration: none;
            transition: color 0.3s;
            
        }

        .nav-link:hover {
            color: #064e3b;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: #059669;
            color: white;
        }

        .btn-primary:hover {
            background: #047857;
        }

        .btn-secondary {
            background: transparent;
            color: #fefefeff;
            border: 2px solid #16c78fff;
        }

        .btn-secondary:hover {
            background: #ecfdf5;
        }

        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1.125rem;
        }

        .hero-box {
        background: rgba(49, 41, 41, 0.089);
        backdrop-filter: blur(10px);
        border: 1px solid #2128250b;
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
        max-width: max-content; /* Shrink to fit text */
        width: auto;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        position: sticky;
        top: 20px; /* Distance from top when it starts sticking */
        z-index: 30;
        transform: translateY(0);
        transition: transform 0.2s ease;
    }

    /* Optional: Add a subtle entrance animation */
    .hero-box:hover {
        transform: translateY(-2px);
    }

        /* Hero Section */
        .hero {
            padding: 5rem 0;
            text-align: center;
            position: relative;
            z-index: 20;
        }
        .gradient-text {
            background: linear-gradient(135deg,rgba(39, 204, 44, 0.94) 36%, rgba(0, 0, 0, 0) 300%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-title {
            font-size: 1rem;              /* base size for small screens */
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 1rem;
            line-height: 1.2;
            text-align: left;
            max-width: 800px;
            padding-left: 1rem;
        }

        .hero-subtitle {
            font-size: 0.9rem;            /* slightly smaller than title */
            color: #fbfbfb;
            margin-bottom: 2rem;
            max-width: 800px;
            text-align: left;
            margin-right: auto;
            padding-left: 1rem;           /* align with title */
        }

        @media (min-width: 768px) {
            .hero-title {
                font-size: 2rem;
                padding-left: 2rem;
            }

            .hero-subtitle {
                font-size: 1.2rem;
                padding-left: 2rem;
            }
        }

        .hero-buttons {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        justify-content: flex-start;
        margin-bottom: 3rem;
        max-width: 800px;
        padding-left: 1rem;
    }

    .hero-buttons button,
    .hero-buttons a {
        font-size: 0.85rem;
        padding: 0.5rem 1rem;
        border-radius: 0.4rem;
    }

    @media (min-width: 640px) {
        .hero-buttons {
            flex-direction: row;
        }
    }

    @media (max-width: 480px) {
        .hero-buttons {
            max-width: 100%;
            padding-left: 1rem;
            padding-right: 1rem;
            gap: 0.5rem;
        }

        .hero-buttons button,
        .hero-buttons a {
            font-size: 0.75rem;
            padding: 0.4rem 0.8rem;
        }
    }



        /* Stats */
        .stats {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-top: 4rem;
        }

        @media (min-width: 768px) {
            .stats {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .stat {
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-emerald { color: #059669; }
        .stat-teal { color: #0d9488; }
        .stat-blue { color: #2563eb; }

        .stat-label {
            color: #047857;
        }

        /* Services Section */
        .services {
            padding: 5rem 0;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(8px);
            position: relative;
            z-index: 20;
            margin-top: 15rem;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: bold;
            color: #064e3b;
            text-align: center;
            margin-bottom: 1rem;
        }

        .section-subtitle {
            font-size: 1.25rem;
            color: #059669;
            text-align: center;
            margin-bottom: 4rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .services-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        @media (min-width: 768px) {
            .services-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .services-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .service-card {
            background: linear-gradient(135deg, #ecfdf5, #f0fdfa);
            border: 1px solid #d1fae5;
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            transition: box-shadow 0.3s;
        }

        .service-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .service-card.teal {
            background: linear-gradient(135deg, #f0fdfa, #eff6ff);
            border-color: #5eead4;
        }

        .service-card.blue {
            background: linear-gradient(135deg, #eff6ff, #ecfdf5);
            border-color: #93c5fd;
        }

        .service-icon {
            width: 4rem;
            height: 4rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
        }

        .service-icon.emerald { background: #059669; }
        .service-icon.teal { background: #0d9488; }
        .service-icon.blue { background: #2563eb; }

        .service-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #064e3b;
            margin-bottom: 1rem;
        }

        .service-description {
            color: #059669;
        }

        /* CTA Section */
        .cta {
            padding: 5rem 0;
            background: linear-gradient(135deg, #059669, #0d9488);
            text-align: center;
            position: relative;
            z-index: 20;
        }

        .cta-title {
            font-size: 2.5rem;
            font-weight: bold;
            color: white;
            margin-bottom: 1.5rem;
        }

        .cta-subtitle {
            font-size: 1.25rem;
            color: #d1fae5;
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-buttons {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            justify-content: center;
        }

        @media (min-width: 640px) {
            .cta-buttons {
                flex-direction: row;
            }
        }

        .btn-white {
            background: white;
            color: #059669;
        }

        .btn-white:hover {
            background: #ecfdf5;
        }

        .btn-outline-white {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-outline-white:hover {
            background: white;
            color: #059669;
        }

        /* Footer */
        .footer {
            background: #064e3b;
            color: white;
            padding: 3rem 0;
            position: relative;
            z-index: 20;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        @media (min-width: 768px) {
            .footer-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .footer-section h4 {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.5rem;
        }

        .footer-links a {
            color: #d1fae5;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: white;
        }

        .footer-contact {
            color: #d1fae5;
        }

        .footer-contact-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .footer-bottom {
            border-top: 1px solid #047857;
            padding-top: 2rem;
            text-align: center;
            color: #d1fae5;
        }

        /* Icons */
        .icon {
            width: 1rem;
            height: 1rem;
            fill: currentColor;
        }

        .icon-lg {
            width: 2rem;
            height: 2rem;
        }

        /* Loading Spinner Animation */
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #059669;
            border-top: 4px solid #d1fae5;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            position: relative;
        }

        .loading-spinner::before {
            content: '🍃';
            position: absolute;
            top: -18px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 24px;
            animation: rotate-leaf 2s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes rotate-leaf {
            0% { transform: translateX(-50%) rotate(0deg); }
            100% { transform: translateX(-50%) rotate(360deg); }
        }
    </style>