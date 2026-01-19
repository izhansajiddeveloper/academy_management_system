<?php
require_once "config/db.php";
require_once "includes/functions.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - EduSkill Pro Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #4A6FA5;
            --accent-teal: #2A9D8F;
            --accent-orange: #E76F51;
        }

        .hero-section {
            background: linear-gradient(135deg, rgba(74, 111, 165, 0.9) 0%, rgba(42, 157, 143, 0.9) 100%),
                url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
            padding: 120px 0;
            position: relative;
        }

        .mission-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid #f1f5f9;
            transition: all 0.3s ease;
        }

        .mission-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.12);
        }

        .stats-card {
            background: linear-gradient(135deg, var(--primary-blue), #3A5A8C);
            color: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 15px 40px rgba(74, 111, 165, 0.2);
        }

        .team-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }

        .team-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .value-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--accent-teal);
            transition: all 0.3s ease;
        }

        .value-card:hover {
            border-left-color: var(--accent-orange);
            transform: translateX(10px);
        }

        .section-title {
            position: relative;
            display: inline-block;
            margin-bottom: 50px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 0;
            width: 80px;
            height: 4px;
            background: var(--accent-teal);
            border-radius: 2px;
        }

        .section-title.center::after {
            left: 50%;
            transform: translateX(-50%);
        }

        .timeline {
            position: relative;
            max-width: 800px;
            margin: 0 auto;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 4px;
            height: 100%;
            background: var(--primary-blue);
            opacity: 0.2;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 60px;
        }

        .timeline-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            width: 45%;
        }

        .timeline-item:nth-child(odd) .timeline-content {
            margin-left: 55%;
        }

        .timeline-item:nth-child(even) .timeline-content {
            margin-right: 55%;
        }

        .timeline-dot {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 24px;
            height: 24px;
            background: var(--accent-orange);
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 0 0 4px var(--accent-orange);
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php include "includes/navbar.php"; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-5xl font-bold text-white mb-6">About EduSkill Pro Academy</h1>
            <p class="text-xl text-blue-100 max-w-3xl mx-auto">
                Empowering the next generation of professionals through industry-relevant education and hands-on learning experiences.
            </p>
        </div>
    </section>

    <!-- Mission & Vision -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <div class="mission-card">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl flex items-center justify-center text-white text-2xl mb-6">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">Our Mission</h2>
                    <p class="text-gray-600 text-lg mb-6">
                        To bridge the gap between academic education and industry requirements by providing practical,
                        skill-based training that empowers students to excel in their careers.
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3">
                            <i class="fas fa-check text-accent-teal mt-1"></i>
                            <span class="text-gray-700">Provide industry-relevant curriculum</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fas fa-check text-accent-teal mt-1"></i>
                            <span class="text-gray-700">Foster practical, hands-on learning</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fas fa-check text-accent-teal mt-1"></i>
                            <span class="text-gray-700">Enable career transformation</span>
                        </li>
                    </ul>
                </div>

                <div class="mission-card">
                    <div class="w-16 h-16 bg-gradient-to-br from-teal-500 to-teal-700 rounded-2xl flex items-center justify-center text-white text-2xl mb-6">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">Our Vision</h2>
                    <p class="text-gray-600 text-lg mb-6">
                        To become the leading academy for professional skill development, recognized for producing
                        job-ready professionals who drive innovation and growth in their industries.
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3">
                            <i class="fas fa-star text-accent-orange mt-1"></i>
                            <span class="text-gray-700">Global recognition for excellence</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fas fa-star text-accent-orange mt-1"></i>
                            <span class="text-gray-700">Innovative learning methodologies</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fas fa-star text-accent-orange mt-1"></i>
                            <span class="text-gray-700">Strong industry partnerships</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16" style="background: linear-gradient(135deg, #f0f7ff 0%, #ffffff 100%);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-center text-gray-900 mb-16 section-title center">Our Impact in Numbers</h2>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="stats-card">
                    <div class="text-5xl font-bold mb-4">2,500+</div>
                    <div class="text-xl font-semibold mb-2">Students Trained</div>
                    <p class="text-blue-200">Successful career transformations</p>
                </div>

                <div class="stats-card" style="background: linear-gradient(135deg, var(--accent-teal), #238A7C);">
                    <div class="text-5xl font-bold mb-4">25+</div>
                    <div class="text-xl font-semibold mb-2">Courses Offered</div>
                    <p class="text-teal-200">Industry-relevant programs</p>
                </div>

                <div class="stats-card" style="background: linear-gradient(135deg, var(--accent-orange), #D65A40);">
                    <div class="text-5xl font-bold mb-4">50+</div>
                    <div class="text-xl font-semibold mb-2">Expert Instructors</div>
                    <p class="text-orange-200">Industry professionals</p>
                </div>

                <div class="stats-card" style="background: linear-gradient(135deg, #6B7280, #4B5563);">
                    <div class="text-5xl font-bold mb-4">98%</div>
                    <div class="text-xl font-semibold mb-2">Placement Rate</div>
                    <p class="text-gray-300">Job guarantee success</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Values -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-gray-900 mb-16 section-title">Our Core Values</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="value-card">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-100 to-blue-200 rounded-xl flex items-center justify-center text-blue-600 text-2xl mb-6">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Excellence</h3>
                    <p class="text-gray-600">
                        We maintain the highest standards in education quality, curriculum design, and student support.
                    </p>
                </div>

                <div class="value-card">
                    <div class="w-14 h-14 bg-gradient-to-br from-teal-100 to-teal-200 rounded-xl flex items-center justify-center text-teal-600 text-2xl mb-6">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Innovation</h3>
                    <p class="text-gray-600">
                        We continuously evolve our teaching methodologies to stay ahead of industry trends and technologies.
                    </p>
                </div>

                <div class="value-card">
                    <div class="w-14 h-14 bg-gradient-to-br from-orange-100 to-orange-200 rounded-xl flex items-center justify-center text-orange-600 text-2xl mb-6">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Integrity</h3>
                    <p class="text-gray-600">
                        We operate with transparency, honesty, and ethical practices in all our interactions.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Timeline -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-center text-gray-900 mb-16 section-title center">Our Journey</h2>

            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="text-sm text-blue-600 font-semibold mb-2">2020</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Foundation</h3>
                        <p class="text-gray-600">
                            EduSkill Pro Academy was founded with a vision to transform professional education.
                        </p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="text-sm text-blue-600 font-semibold mb-2">2021</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">First 100 Students</h3>
                        <p class="text-gray-600">
                            Successfully trained our first batch of 100 students with 95% placement rate.
                        </p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="text-sm text-blue-600 font-semibold mb-2">2022</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Industry Partnerships</h3>
                        <p class="text-gray-600">
                            Established partnerships with 20+ leading tech companies for placement opportunities.
                        </p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="text-sm text-blue-600 font-semibold mb-2">2023</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Digital Expansion</h3>
                        <p class="text-gray-600">
                            Launched online learning platform, reaching students across the country.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20" style="background: linear-gradient(135deg, var(--primary-blue) 0%, #3A5A8C 100%);">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold text-white mb-6">Join Our Learning Community</h2>
            <p class="text-xl text-blue-100 mb-12 max-w-3xl mx-auto">
                Become part of a thriving community of learners, innovators, and future leaders.
            </p>

            <div class="flex flex-col sm:flex-row gap-6 justify-center">
                <a href="/academy_management_system/auth/login.php" class="bg-white text-primary-blue px-10 py-4 rounded-xl font-bold text-lg hover:bg-gray-100 transition-all duration-300 transform hover:-translate-y-1 shadow-2xl inline-flex items-center gap-3">
                    <i class="fas fa-user-plus"></i> Start Your Journey
                </a>
                <a href="/academy_management_system/contact.php" class="bg-transparent border-2 border-white text-white px-10 py-4 rounded-xl font-bold text-lg hover:bg-primary-blue hover:text-white transition-all duration-300 transform hover:-translate-y-1 inline-flex items-center gap-3">
                    <i class="fas fa-envelope"></i> Contact Us
                </a>
            </div>
        </div>
    </section>

    <?php include "includes/footer.php"; ?>
</body>

</html>