<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Updated Educational Blue Color Palette */
        :root {
            --footer-blue: #2C5AA0;
            /* Rich Academic Blue */
            --footer-dark-blue: #1A3D7C;
            /* Darker Blue for contrast */
            --accent-teal: #2A9D8F;
            /* Teal for highlights */
            --accent-orange: #E76F51;
            /* Orange for CTAs */
            --light-blue: #E8F1FF;
            /* Light blue background */
            --white: #FFFFFF;
            --text-light: #E8F1FF;
            --text-lighter: #B8D0FF;
        }

        .academy-footer {
            background: linear-gradient(135deg, var(--footer-blue) 0%, var(--footer-dark-blue) 100%);
            color: var(--text-light);
            padding: 60px 0 25px;
            margin-top: 70px;
            border-top: 4px solid var(--accent-teal);
            position: relative;
            overflow: hidden;
        }

        /* Background pattern for education theme */
        .academy-footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background-image:
                radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.05) 2px, transparent 2px),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.05) 2px, transparent 2px);
            background-size: 60px 60px;
            z-index: 0;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            position: relative;
            z-index: 1;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 50px;
            margin-bottom: 50px;
        }

        .footer-section h3 {
            color: white;
            font-size: 20px;
            margin-bottom: 25px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 12px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }

        .footer-section h3 i {
            color: var(--accent-teal);
            font-size: 22px;
            width: 28px;
            text-align: center;
        }

        .footer-section p {
            color: var(--text-lighter);
            line-height: 1.8;
            font-size: 15px;
            margin-top: 15px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 15px;
        }

        .footer-links a {
            color: var(--text-lighter);
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
            border-radius: 6px;
            padding-left: 10px;
        }

        .footer-links a:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(8px);
            padding-left: 15px;
        }

        .footer-links a i {
            color: var(--accent-teal);
            font-size: 14px;
            width: 20px;
            text-align: center;
        }

        .contact-info {
            list-style: none;
            padding: 0;
        }

        .contact-info li {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 18px;
            color: var(--text-lighter);
            font-size: 15px;
            line-height: 1.6;
        }

        .contact-info i {
            color: var(--accent-orange);
            font-size: 18px;
            width: 24px;
            text-align: center;
            margin-top: 3px;
            background: rgba(255, 255, 255, 0.1);
            padding: 8px;
            border-radius: 8px;
        }

        .social-links {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }

        .social-icon {
            width: 44px;
            height: 44px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 18px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .social-icon:hover {
            background: var(--accent-teal);
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .social-icon.facebook:hover {
            background: #1877F2;
        }

        .social-icon.twitter:hover {
            background: #1DA1F2;
        }

        .social-icon.linkedin:hover {
            background: #0077B5;
        }

        .social-icon.instagram:hover {
            background: #E4405F;
        }

        .social-icon.youtube:hover {
            background: #FF0000;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            padding-top: 25px;
            text-align: center;
        }

        .copyright {
            color: var(--text-lighter);
            font-size: 15px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .footer-bottom-links {
            display: flex;
            justify-content: center;
            gap: 35px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .footer-bottom-links a {
            color: var(--text-lighter);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 6px 12px;
            border-radius: 6px;
        }

        .footer-bottom-links a:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }

        .academy-logo-footer {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .logo-icon-small {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--accent-teal), #238A7C);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 22px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .logo-text-footer {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(to right, white, #E8F1FF);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }

        .logo-tagline-footer {
            color: var(--accent-orange);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-top: -5px;
            display: block;
        }

        /* Newsletter Section */
        .newsletter-box {
            background: rgba(255, 255, 255, 0.1);
            padding: 25px;
            border-radius: 12px;
            margin-top: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .newsletter-box h4 {
            color: white;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .newsletter-form {
            display: flex;
            gap: 10px;
        }

        .newsletter-input {
            flex: 1;
            padding: 12px 16px;
            border: none;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.9);
            color: var(--footer-blue);
            font-size: 14px;
        }

        .newsletter-input:focus {
            outline: none;
            background: white;
            box-shadow: 0 0 0 3px rgba(42, 157, 143, 0.3);
        }

        .newsletter-btn {
            background: var(--accent-orange);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .newsletter-btn:hover {
            background: #D65A40;
            transform: translateY(-2px);
        }

        /* Quick Contact Button */
        .quick-contact {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--accent-orange);
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .quick-contact:hover {
            background: #D65A40;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(231, 111, 81, 0.3);
        }

        /* Mobile Responsive */
        @media (max-width: 992px) {
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
                gap: 40px;
            }

            .newsletter-form {
                flex-direction: column;
            }

            .footer-bottom-links {
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .footer-content {
                grid-template-columns: 1fr;
                gap: 35px;
            }

            .academy-footer {
                padding: 50px 0 20px;
            }

            .footer-bottom-links {
                flex-direction: column;
                gap: 10px;
                align-items: center;
            }

            .footer-section h3 {
                font-size: 18px;
            }
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .footer-section {
            animation: fadeInUp 0.6s ease;
        }

        .footer-section:nth-child(2) {
            animation-delay: 0.1s;
        }

        .footer-section:nth-child(3) {
            animation-delay: 0.2s;
        }

        .footer-section:nth-child(4) {
            animation-delay: 0.3s;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <footer class="academy-footer">
        <div class="footer-container">
            <div class="footer-content">
                <!-- Academy Information -->
                <div class="footer-section">
                    <div class="academy-logo-footer">
                        <div class="logo-icon-small">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div>
                            <span class="logo-text-footer">EduSkill Pro</span>
                            <span class="logo-tagline-footer">Learn • Grow • Succeed</span>
                        </div>
                    </div>
                    <p>Transforming lives through skill-based education. We provide industry-relevant training, hands-on learning experiences, and career support to help students achieve their professional goals.</p>

                    <div class="social-links">
                        <a href="#" class="social-icon facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon linkedin"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="social-icon instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon youtube"><i class="fab fa-youtube"></i></a>
                    </div>

                    <a href="/contact.php" class="quick-contact">
                        <i class="fas fa-headset"></i> Quick Contact
                    </a>
                </div>

                <!-- Quick Links -->
                <div class="footer-section">
                    <h3><i class="fas fa-link"></i> Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="/index.php"><i class="fas fa-home"></i> Home</a></li>
                        <li><a href="/about.php"><i class="fas fa-info-circle"></i> About Academy</a></li>
                        <li><a href="/services.php"><i class="fas fa-book-open"></i> All Courses</a></li>
                        <li><a href="/contact.php"><i class="fas fa-envelope"></i> Contact Admissions</a></li>
                        <li><a href="/auth/register.php"><i class="fas fa-user-plus"></i> Student Registration</a></li>
                        <li><a href="/auth/login.php"><i class="fas fa-sign-in-alt"></i> Student Portal</a></li>
                    </ul>
                </div>

                <!-- Academic Resources -->
                <div class="footer-section">
                    <h3><i class="fas fa-graduation-cap"></i> Academic Resources</h3>
                    <ul class="footer-links">
                        <li><a href="#"><i class="fas fa-calendar-alt"></i> Academic Calendar</a></li>
                        <li><a href="#"><i class="fas fa-download"></i> Course Materials</a></li>
                        <li><a href="#"><i class="fas fa-question-circle"></i> Student FAQ</a></li>
                        <li><a href="#"><i class="fas fa-book"></i> Online Library</a></li>
                        <li><a href="#"><i class="fas fa-bullhorn"></i> Campus News</a></li>
                        <li><a href="#"><i class="fas fa-trophy"></i> Student Achievements</a></li>
                    </ul>

                    <div class="newsletter-box">
                        <h4>Subscribe to Our Newsletter</h4>
                        <form class="newsletter-form">
                            <input type="email" class="newsletter-input" placeholder="Your email address" required>
                            <button type="submit" class="newsletter-btn">Subscribe</button>
                        </form>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="footer-section">
                    <h3><i class="fas fa-address-book"></i> Contact Info</h3>
                    <ul class="contact-info">
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <span><strong>Main Campus:</strong><br>123 Education Avenue, Knowledge City, KC 10101</span>
                        </li>
                        <li>
                            <i class="fas fa-phone"></i>
                            <span><strong>Admissions:</strong><br>+1 (555) 123-EDU<br><strong>Support:</strong><br>+1 (555) 123-HELP</span>
                        </li>
                        <li>
                            <i class="fas fa-envelope"></i>
                            <span><strong>Email:</strong><br>admissions@eduskillpro.edu<br>support@eduskillpro.edu</span>
                        </li>
                        <li>
                            <i class="fas fa-clock"></i>
                            <span><strong>Office Hours:</strong><br>Mon-Fri: 8:00 AM - 7:00 PM<br>Sat: 9:00 AM - 4:00 PM</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="footer-bottom-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Code of Conduct</a>
                    <a href="#">Academic Integrity</a>
                    <a href="#">Accessibility</a>
                    <a href="#">Careers</a>
                    <a href="#">Sitemap</a>
                </div>

                <p class="copyright">
                    &copy; <?php echo date("Y"); ?> EduSkill Pro Academy Management System. All Rights Reserved. |
                    <span style="color: var(--accent-orange);">Accredited by International Education Standards</span>
                </p>
            </div>
        </div>
    </footer>
</body>

</html>