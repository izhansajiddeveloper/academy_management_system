<?php
require_once "config/db.php";
require_once "includes/functions.php";

// Redirect logged-in users to their dashboards
if (isLoggedIn()) {
    $role = getUserType();
    if ($role === 'admin') {
        redirect("/admin/dashboard.php");
    } elseif ($role === 'teacher') {
        redirect("/teacher/dashboard.php");
    } elseif ($role === 'student') {
        redirect("/academy_management_system/student/dashboard.php");
    }
}

// Get some stats for display (you'll need to implement these functions)
// $totalStudents = getTotalStudents();
// $totalCourses = getTotalCourses();
// $totalTeachers = getTotalTeachers();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduSkill Pro - Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #4A6FA5;
            --accent-teal: #2A9D8F;
            --accent-orange: #E76F51;
            --light-blue: #F0F7FF;
        }

        .hero-gradient {
            background: linear-gradient(135deg, #E8F1FF 0%, #FFFFFF 50%, #F0F7FF 100%);
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid #F1F5F9;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        }

        .course-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
            border: 1px solid #F1F5F9;
        }

        .course-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .cta-button {
            background: linear-gradient(135deg, var(--accent-teal), #238A7C);
            color: white;
            padding: 16px 40px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 18px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 6px 20px rgba(42, 157, 143, 0.25);
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(42, 157, 143, 0.35);
            background: linear-gradient(135deg, #238A7C, var(--accent-teal));
        }

        .secondary-button {
            background: white;
            color: var(--primary-blue);
            padding: 16px 40px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 18px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            border: 2px solid var(--primary-blue);
            box-shadow: 0 4px 15px rgba(74, 111, 165, 0.1);
        }

        .secondary-button:hover {
            background: var(--primary-blue);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(74, 111, 165, 0.2);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-blue), #3A5A8C);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            margin-bottom: 20px;
            box-shadow: 0 8px 20px rgba(74, 111, 165, 0.2);
        }

        .icon-orange {
            background: linear-gradient(135deg, var(--accent-orange), #D65A40);
        }

        .icon-teal {
            background: linear-gradient(135deg, var(--accent-teal), #238A7C);
        }

        .testimonial-card {
            background: white;
            border-radius: 16px;
            padding: 35px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid #F1F5F9;
            position: relative;
        }

        .testimonial-card::before {
            content: '"';
            position: absolute;
            top: 20px;
            left: 25px;
            font-size: 80px;
            color: var(--accent-teal);
            opacity: 0.2;
            font-family: Georgia, serif;
        }

        .floating-animation {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-20px);
            }

            100% {
                transform: translateY(0px);
            }
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
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--accent-teal);
            border-radius: 2px;
        }

        .highlight-text {
            color: var(--accent-teal);
            font-weight: 700;
        }

        .pulse-animation {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(42, 157, 143, 0.4);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(42, 157, 143, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(42, 157, 143, 0);
            }
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <?php include "includes/navbar.php"; ?>

    <!-- Hero Section -->
    <section class="hero-gradient pt-20 pb-32">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <div class="inline-flex items-center gap-3 bg-blue-50 text-primary-blue px-4 py-2 rounded-full mb-6">
                        <span class="h-2 w-2 bg-primary-blue rounded-full"></span>
                        <span class="text-sm font-semibold">TRANSFORMING EDUCATION SINCE 2020</span>
                    </div>

                    <h1 class="text-5xl lg:text-6xl font-bold text-gray-900 mb-6 leading-tight">
                        Master Your Future with
                        <span class="text-primary-blue">Professional</span>
                        Skills Training
                    </h1>

                    <p class="text-xl text-gray-600 mb-10 leading-relaxed">
                        Join thousands of successful students who transformed their careers through our industry-focused courses.
                        Get hands-on experience, personalized mentorship, and job-ready skills.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-6 mb-12">
                        <a href="/academy_management_system/auth/login.php" class="cta-button pulse-animation">
                            <i class="fas fa-rocket"></i> Start Learning Free
                        </a>
                        <a href="/academy_management_system/services.php" class="secondary-button">
                            <i class="fas fa-play-circle"></i> View Courses
                        </a>
                    </div>

                    <div class="flex items-center gap-8">
                        <div>
                            <div class="flex -space-x-3">
                                <?php for ($i = 0; $i < 4; $i++): ?>
                                    <div class="w-12 h-12 rounded-full border-2 border-white bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold">
                                        <i class="fas fa-user text-sm"></i>
                                    </div>
                                <?php endfor; ?>
                                <div class="w-12 h-12 rounded-full border-2 border-white bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white font-bold">
                                    <span class="text-sm">2K+</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <p class="font-bold text-gray-900 text-lg">Join 2,500+ Successful Students</p>
                            <p class="text-gray-600">4.9/5 Average Rating</p>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <div class="relative z-10">
                        <img src="/academy_management_system/assets/images/hero-image.png"
                            alt="Students learning together"
                            class="rounded-2xl shadow-2xl w-full h-auto">
                    </div>

                    <!-- Floating elements -->
                    <div class="absolute -top-6 -left-6 w-24 h-24 bg-gradient-to-br from-blue-100 to-blue-200 rounded-2xl -rotate-12 floating-animation"></div>
                    <div class="absolute -bottom-6 -right-6 w-32 h-32 bg-gradient-to-br from-teal-100 to-teal-200 rounded-3xl rotate-12 floating-animation" style="animation-delay: 2s;"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="stat-card text-center">
                    <div class="text-5xl font-bold text-primary-blue mb-4">25+</div>
                    <div class="text-gray-600 font-semibold text-lg">Professional Courses</div>
                    <p class="text-gray-500 text-sm mt-2">Industry-relevant curriculum</p>
                </div>

                <div class="stat-card text-center">
                    <div class="text-5xl font-bold text-accent-teal mb-4">50+</div>
                    <div class="text-gray-600 font-semibold text-lg">Expert Instructors</div>
                    <p class="text-gray-500 text-sm mt-2">Industry professionals</p>
                </div>

                <div class="stat-card text-center">
                    <div class="text-5xl font-bold text-accent-orange mb-4">2,500+</div>
                    <div class="text-gray-600 font-semibold text-lg">Successful Students</div>
                    <p class="text-gray-500 text-sm mt-2">Career transformed</p>
                </div>

                <div class="stat-card text-center">
                    <div class="text-5xl font-bold text-primary-blue mb-4">98%</div>
                    <div class="text-gray-600 font-semibold text-lg">Placement Rate</div>
                    <p class="text-gray-500 text-sm mt-2">Job guarantee program</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-center text-gray-900 mb-4 section-title">Why Choose EduSkill Pro?</h2>
            <p class="text-xl text-gray-600 text-center mb-16 max-w-3xl mx-auto">
                We provide a complete learning ecosystem designed for your success
            </p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                <div class="text-center">
                    <div class="feature-icon mx-auto">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Hands-on Learning</h3>
                    <p class="text-gray-600">
                        Practice with real-world projects and build a professional portfolio that gets you hired.
                    </p>
                </div>

                <div class="text-center">
                    <div class="feature-icon mx-auto icon-orange">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Personal Mentorship</h3>
                    <p class="text-gray-600">
                        Get 1:1 guidance from industry experts and personalized feedback on your progress.
                    </p>
                </div>

                <div class="text-center">
                    <div class="feature-icon mx-auto icon-teal">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Career Support</h3>
                    <p class="text-gray-600">
                        Access job placement assistance, interview preparation, and networking opportunities.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Popular Courses -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-end mb-12">
                <div>
                    <h2 class="text-4xl font-bold text-gray-900 mb-4">Featured Courses</h2>
                    <p class="text-xl text-gray-600">Master in-demand skills with our top-rated programs</p>
                </div>
                <a href="/academy_management_system/services.php" class="text-primary-blue font-semibold hover:underline flex items-center gap-2">
                    View All Courses <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php
                $courses = [
                    [
                        'title' => 'Full Stack Web Development',
                        'duration' => '6 Months',
                        'level' => 'Advanced',
                        'icon' => 'fas fa-code',
                        'color' => 'from-blue-500 to-blue-700'
                    ],
                    [
                        'title' => 'Data Science & Machine Learning',
                        'duration' => '8 Months',
                        'level' => 'Advanced',
                        'icon' => 'fas fa-chart-line',
                        'color' => 'from-teal-500 to-teal-700'
                    ],
                    [
                        'title' => 'Digital Marketing Mastery',
                        'duration' => '4 Months',
                        'level' => 'Intermediate',
                        'icon' => 'fas fa-bullhorn',
                        'color' => 'from-orange-500 to-orange-700'
                    ]
                ];

                foreach ($courses as $course):
                ?>
                    <div class="course-card">
                        <div class="h-48 bg-gradient-to-br <?php echo $course['color']; ?> flex items-center justify-center">
                            <i class="<?php echo $course['icon']; ?> text-white text-5xl"></i>
                        </div>
                        <div class="p-8">
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-xl font-bold text-gray-900"><?php echo $course['title']; ?></h3>
                                <span class="px-3 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded-full">
                                    <?php echo $course['level']; ?>
                                </span>
                            </div>
                            <p class="text-gray-600 mb-6">
                                Master industry-relevant skills with hands-on projects and expert guidance.
                            </p>
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-2 text-gray-500">
                                    <i class="far fa-clock"></i>
                                    <span><?php echo $course['duration']; ?></span>
                                </div>
                                <a href="/academy_management_system/auth/login.php" class="text-primary-blue font-semibold hover:underline">
                                    Enroll Now →
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20" style="background: linear-gradient(135deg, var(--primary-blue) 0%, #3A5A8C 100%);">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold text-white mb-6">Ready to Transform Your Career?</h2>
            <p class="text-xl text-blue-100 mb-12 max-w-3xl mx-auto">
                Join thousands of students who have successfully launched their careers with our professional training programs.
            </p>

                <a href="/academy_management_system/auth/login.php" class="bg-transparent border-2 border-white text-white px-10 py-4 rounded-xl font-bold text-lg hover:bg-blue hover:text-primary-white transition-all duration-300 transform hover:-translate-y-1 inline-flex items-center gap-3">
                    <i class="fas fa-sign-in-alt"></i> Login to Dashboard
                </a>
            </div>

            <p class="text-blue-200 mt-8 text-sm">
                No credit card required. Start learning immediately after registration.
            </p>
        </div>
    </section>

    <?php include "includes/footer.php"; ?>

    <!-- JavaScript for animations -->
    <script>
        // Scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fadeInUp');
                }
            });
        }, observerOptions);

        // Observe all stat cards and course cards
        document.querySelectorAll('.stat-card, .course-card, .feature-icon').forEach(el => {
            observer.observe(el);
        });

        // Add CSS for fade-in animation
        const style = document.createElement('style');
        style.textContent = `
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
            .animate-fadeInUp {
                animation: fadeInUp 0.6s ease forwards;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>

</html>