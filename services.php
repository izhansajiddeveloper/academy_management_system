<?php
require_once "config/db.php";
require_once "includes/functions.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Courses - EduSkill Pro Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #4A6FA5;
            --accent-teal: #2A9D8F;
            --accent-orange: #E76F51;
        }

        .hero-section {
            background: linear-gradient(135deg, rgba(42, 157, 143, 0.9) 0%, rgba(74, 111, 165, 0.9) 100%),
                url('https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
            padding: 120px 0;
            position: relative;
        }

        .course-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid #f1f5f9;
            height: 100%;
        }

        .course-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.12);
        }

        .course-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: white;
            color: var(--accent-orange);
            padding: 6px 15px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .category-filter {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            margin-bottom: 40px;
            border: 1px solid #f1f5f9;
        }

        .filter-btn {
            padding: 10px 25px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .filter-btn.active {
            background: var(--primary-blue);
            color: white;
        }

        .filter-btn:hover:not(.active) {
            border-color: var(--primary-blue);
            color: var(--primary-blue);
        }

        .level-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .level-basic {
            background: rgba(74, 111, 165, 0.1);
            color: var(--primary-blue);
        }

        .level-intermediate {
            background: rgba(42, 157, 143, 0.1);
            color: var(--accent-teal);
        }

        .level-advanced {
            background: rgba(231, 111, 81, 0.1);
            color: var(--accent-orange);
        }

        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f5f9;
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
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
            margin: 0 auto 20px;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php include "includes/navbar.php"; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-5xl font-bold text-white mb-6">Industry-Relevant Courses</h1>
            <p class="text-xl text-white max-w-3xl mx-auto mb-10">
                Master in-demand skills with our comprehensive training programs designed by industry experts
            </p>
            <div class="inline-flex items-center gap-3 bg-white/20 backdrop-blur-sm text-white px-6 py-3 rounded-full">
                <i class="fas fa-graduation-cap"></i>
                <span>98% Placement Success Rate</span>
            </div>
        </div>
    </section>

    <!-- Course Categories Filter -->
    <section class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="category-filter">
                <h3 class="text-xl font-bold text-gray-900 mb-6 text-center">Browse Courses by Category</h3>
                <div class="flex flex-wrap gap-3 justify-center">
                    <button class="filter-btn active" data-category="all">All Courses</button>
                    <button class="filter-btn" data-category="tech">Technology</button>
                    <button class="filter-btn" data-category="business">Business</button>
                    <button class="filter-btn" data-category="design">Design</button>
                    <button class="filter-btn" data-category="marketing">Digital Marketing</button>
                    <button class="filter-btn" data-category="data">Data Science</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Courses Grid -->
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" id="courses-grid">
                <?php
                // Sample courses data - In real implementation, fetch from database
                $courses = [
                    [
                        'id' => 1,
                        'title' => 'Full Stack Web Development',
                        'category' => 'tech',
                        'duration' => '6 Months',
                        'level' => 'Advanced',
                        'description' => 'Master frontend and backend technologies to build complete web applications',
                        'icon' => 'fas fa-code',
                        'color' => 'from-blue-500 to-blue-700',
                        'price' => 'Rs45,000',
                        'features' => ['HTML/CSS/JS', 'React.js', 'Node.js', 'MongoDB', 'Express.js']
                    ],
                    [
                        'id' => 2,
                        'title' => 'Data Science & Machine Learning',
                        'category' => 'data',
                        'duration' => '8 Months',
                        'level' => 'Advanced',
                        'description' => 'Learn to extract insights from data and build intelligent systems',
                        'icon' => 'fas fa-chart-line',
                        'color' => 'from-teal-500 to-teal-700',
                        'price' => 'Rs55,000',
                        'features' => ['Python', 'TensorFlow', 'Pandas', 'Data Visualization', 'AI Models']
                    ],
                    [
                        'id' => 3,
                        'title' => 'Digital Marketing Mastery',
                        'category' => 'marketing',
                        'duration' => '4 Months',
                        'level' => 'Intermediate',
                        'description' => 'Master digital marketing strategies for business growth',
                        'icon' => 'fas fa-bullhorn',
                        'color' => 'from-orange-500 to-orange-700',
                        'price' => 'Rs25,000',
                        'features' => ['SEO', 'Social Media', 'Google Ads', 'Content Marketing', 'Analytics']
                    ],
                    [
                        'id' => 4,
                        'title' => 'UI/UX Design Pro',
                        'category' => 'design',
                        'duration' => '5 Months',
                        'level' => 'Intermediate',
                        'description' => 'Design beautiful and user-friendly digital experiences',
                        'icon' => 'fas fa-paint-brush',
                        'color' => 'from-purple-500 to-purple-700',
                        'price' => 'Rs35,000',
                        'features' => ['Figma', 'Wireframing', 'Prototyping', 'User Research', 'Design Systems']
                    ],
                    [
                        'id' => 5,
                        'title' => 'Business Analytics',
                        'category' => 'business',
                        'duration' => '6 Months',
                        'level' => 'Intermediate',
                        'description' => 'Use data to drive business decisions and strategy',
                        'icon' => 'fas fa-chart-pie',
                        'color' => 'from-green-500 to-green-700',
                        'price' => 'Rs40,000',
                        'features' => ['Excel', 'SQL', 'Tableau', 'Business Intelligence', 'Statistics']
                    ],
                    [
                        'id' => 6,
                        'title' => 'Mobile App Development',
                        'category' => 'tech',
                        'duration' => '7 Months',
                        'level' => 'Advanced',
                        'description' => 'Build native and cross-platform mobile applications',
                        'icon' => 'fas fa-mobile-alt',
                        'color' => 'from-red-500 to-red-700',
                        'price' => 'Rs50,000',
                        'features' => ['React Native', 'Flutter', 'iOS', 'Android', 'API Integration']
                    ]
                ];

                foreach ($courses as $course):
                ?>
                    <div class="course-card" data-category="<?php echo $course['category']; ?>">
                        <div class="relative">
                            <div class="h-48 bg-gradient-to-br <?php echo $course['color']; ?> flex items-center justify-center">
                                <i class="<?php echo $course['icon']; ?> text-white text-5xl"></i>
                            </div>
                            <span class="course-badge"><?php echo $course['price']; ?></span>
                            <div class="absolute bottom-4 left-4">
                                <span class="level-badge level-<?php echo strtolower($course['level']); ?>">
                                    <i class="fas fa-signal"></i> <?php echo $course['level']; ?>
                                </span>
                            </div>
                        </div>

                        <div class="p-8">
                            <h3 class="text-2xl font-bold text-gray-900 mb-3"><?php echo $course['title']; ?></h3>
                            <p class="text-gray-600 mb-6"><?php echo $course['description']; ?></p>

                            <div class="mb-6">
                                <div class="flex items-center gap-2 text-gray-500 mb-3">
                                    <i class="far fa-clock"></i>
                                    <span class="font-medium">Duration: <?php echo $course['duration']; ?></span>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <?php foreach ($course['features'] as $feature): ?>
                                        <span class="px-3 py-1 bg-gray-100 text-gray-700 text-sm rounded-full">
                                            <?php echo $feature; ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="flex justify-between items-center">
                                <button class="view-details-btn text-primary-blue font-semibold hover:underline"
                                    data-course='<?php echo json_encode($course); ?>'>
                                    View Details →
                                </button>
                                <a href="/academy_management_system/auth/login.php" class="bg-primary-blue text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-600 transition-colors">
                                    Enroll Now
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Course Features -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-center text-gray-900 mb-16">Why Choose Our Courses?</h2>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Industry-Relevant</h3>
                    <p class="text-gray-600">Curriculum designed by industry experts</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, var(--accent-teal), #238A7C);">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Hands-on Projects</h3>
                    <p class="text-gray-600">Real-world projects for practical experience</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, var(--accent-orange), #D65A40);">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Expert Mentors</h3>
                    <p class="text-gray-600">Learn from industry professionals</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #6B7280, #4B5563);">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Job Placement</h3>
                    <p class="text-gray-600">98% placement assistance</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Course Modal -->
    <div class="modal-overlay" id="courseModal">
        <div class="modal-content">
            <div class="p-8">
                <div class="flex justify-between items-start mb-6">
                    <h2 class="text-3xl font-bold text-gray-900" id="modalTitle"></h2>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div>
                        <div class="mb-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Course Overview</h3>
                            <p class="text-gray-600" id="modalDescription"></p>
                        </div>

                        <div class="mb-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-3">What You'll Learn</h3>
                            <ul class="space-y-2" id="modalFeatures"></ul>
                        </div>
                    </div>

                    <div>
                        <div class="bg-gray-50 rounded-xl p-6 mb-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-4">Course Details</h3>
                            <div class="space-y-4">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Duration:</span>
                                    <span class="font-semibold" id="modalDuration"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Level:</span>
                                    <span class="font-semibold" id="modalLevel"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Course Fee:</span>
                                    <span class="font-semibold text-primary-blue" id="modalPrice"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Category:</span>
                                    <span class="font-semibold" id="modalCategory"></span>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <a href="/academy_management_system/auth/login.php" class="block w-full bg-gradient-to-r from-blue-500 to-blue-700 text-white py-4 rounded-xl font-bold text-lg hover:opacity-90 transition-opacity mb-4">
                                <i class="fas fa-user-plus mr-2"></i> Enroll Now
                            </a>
                            <a href="/academy_management_system/contact.php" class="block w-full border-2 border-primary-blue text-primary-blue py-4 rounded-xl font-bold text-lg hover:bg-blue-50 transition-colors">
                                <i class="fas fa-question-circle mr-2"></i> Have Questions?
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include "includes/footer.php"; ?>

    <script>
        // Course Filtering
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Update active button
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                const category = this.dataset.category;
                const courses = document.querySelectorAll('.course-card');

                courses.forEach(course => {
                    if (category === 'all' || course.dataset.category === category) {
                        course.style.display = 'block';
                        setTimeout(() => course.style.opacity = '1', 50);
                    } else {
                        course.style.opacity = '0';
                        setTimeout(() => course.style.display = 'none', 300);
                    }
                });
            });
        });

        // Course Modal
        const modal = document.getElementById('courseModal');
        const viewDetailsBtns = document.querySelectorAll('.view-details-btn');

        viewDetailsBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const course = JSON.parse(this.dataset.course);
                openModal(course);
            });
        });

        function openModal(course) {
            document.getElementById('modalTitle').textContent = course.title;
            document.getElementById('modalDescription').textContent = course.description;
            document.getElementById('modalDuration').textContent = course.duration;
            document.getElementById('modalLevel').textContent = course.level;
            document.getElementById('modalPrice').textContent = course.price;
            document.getElementById('modalCategory').textContent =
                course.category.charAt(0).toUpperCase() + course.category.slice(1);

            // Clear and populate features
            const featuresList = document.getElementById('modalFeatures');
            featuresList.innerHTML = '';
            course.features.forEach(feature => {
                const li = document.createElement('li');
                li.className = 'flex items-center gap-3';
                li.innerHTML = `
                    <i class="fas fa-check text-accent-teal"></i>
                    <span class="text-gray-700">${feature}</span>
                `;
                featuresList.appendChild(li);
            });

            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close modal on overlay click
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        // Add animation to course cards
        document.addEventListener('DOMContentLoaded', function() {
            const courses = document.querySelectorAll('.course-card');
            courses.forEach((course, index) => {
                course.style.animationDelay = `${index * 0.1}s`;
                course.classList.add('animate-fadeIn');
            });

            // Add CSS animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes fadeIn {
                    from {
                        opacity: 0;
                        transform: translateY(20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                .animate-fadeIn {
                    animation: fadeIn 0.6s ease forwards;
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>

</html>