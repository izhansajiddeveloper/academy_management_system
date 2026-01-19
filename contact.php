<?php
require_once "config/db.php";
require_once "includes/functions.php";

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    // Validate inputs
    if (!$name || !$email || !$subject || !$message) {
        $error = "Please fill in all required fields!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address!";
    } else {
        // Save to database
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, phone, subject, message, created_at) 
                                VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);

        if ($stmt->execute()) {
            $success = "Thank you for your message! We've received it and will get back to you soon.";
            // Clear form
            $_POST = [];

            // Optional: Send notification to admin (if you want to implement email later)
            // sendAdminNotification($name, $email, $subject, $message);
        } else {
            $error = "There was an error submitting your message. Please try again.";
        }
    }
}

// Function to create contact_messages table if it doesn't exist
function createContactTable($conn)
{
    $sql = "CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        email VARCHAR(150) NOT NULL,
        phone VARCHAR(30),
        subject VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        status ENUM('new', 'read', 'replied') DEFAULT 'new',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if (!$conn->query($sql)) {
        error_log("Failed to create contact_messages table: " . $conn->error);
    }
}

// Create table if it doesn't exist
createContactTable($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - EduSkill Pro Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #4A6FA5;
            --accent-teal: #2A9D8F;
            --accent-orange: #E76F51;
        }

        .hero-section {
            background: linear-gradient(135deg, rgba(231, 111, 81, 0.9) 0%, rgba(74, 111, 165, 0.9) 100%),
                url('https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
            padding: 120px 0;
            position: relative;
        }

        .contact-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            border: 1px solid #f1f5f9;
        }

        .info-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f5f9;
            transition: all 0.3s ease;
            height: 100%;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        }

        .contact-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-blue), #3A5A8C);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .input-group {
            margin-bottom: 25px;
        }

        .input-label {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary-blue);
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 15px;
        }

        .input-label .required {
            color: var(--accent-orange);
            font-size: 12px;
        }

        .input-field {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .input-field:focus {
            outline: none;
            border-color: var(--accent-teal);
            background: white;
            box-shadow: 0 0 0 3px rgba(42, 157, 143, 0.1);
        }

        textarea.input-field {
            min-height: 150px;
            resize: vertical;
        }

        .submit-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--accent-teal), #238A7C);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(42, 157, 143, 0.3);
        }

        .error-message {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }

        .success-message {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #16a34a;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }

        .map-container {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid #f1f5f9;
        }

        .social-contact {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-link {
            width: 50px;
            height: 50px;
            background: #f8fafc;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-blue);
            text-decoration: none;
            font-size: 20px;
            transition: all 0.3s ease;
            border: 2px solid #e2e8f0;
        }

        .social-link:hover {
            background: var(--primary-blue);
            color: white;
            border-color: var(--primary-blue);
            transform: translateY(-3px);
        }

        .success-animation {
            animation: successPulse 2s ease-in-out;
        }

        @keyframes successPulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.02);
            }

            100% {
                transform: scale(1);
            }
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php include "includes/navbar.php"; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-5xl font-bold text-white mb-6">Get in Touch</h1>
            <p class="text-xl text-white max-w-3xl mx-auto">
                Have questions? We're here to help. Reach out to us for course inquiries, admissions, or any other questions.
            </p>
        </div>
    </section>

    <!-- Contact Information -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-center text-gray-900 mb-16">Contact Information</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-20">
                <div class="info-card text-center">
                    <div class="contact-icon mx-auto">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Visit Our Campus</h3>
                    <p class="text-gray-600">
                        123 Education Avenue<br>
                        Knowledge City, KC 10101<br>
                        India
                    </p>
                    <div class="mt-4">
                        <a href="https://maps.google.com/?q=123+Education+Avenue+Knowledge+City"
                            target="_blank"
                            class="text-primary-blue font-semibold hover:underline inline-flex items-center gap-2">
                            <i class="fas fa-directions"></i> Get Directions
                        </a>
                    </div>
                </div>

                <div class="info-card text-center">
                    <div class="contact-icon mx-auto" style="background: linear-gradient(135deg, var(--accent-teal), #238A7C);">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Call Us</h3>
                    <p class="text-gray-600 mb-2">
                        <strong>Admissions:</strong> +91 98765 43210
                    </p>
                    <p class="text-gray-600">
                        <strong>Support:</strong> +91 98765 43211
                    </p>
                    <div class="mt-4">
                        <a href="tel:+919876543210" class="text-primary-blue font-semibold hover:underline inline-flex items-center gap-2">
                            <i class="fas fa-phone-alt"></i> Call Now
                        </a>
                    </div>
                </div>

                <div class="info-card text-center">
                    <div class="contact-icon mx-auto" style="background: linear-gradient(135deg, var(--accent-orange), #D65A40);">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Email Us</h3>
                    <p class="text-gray-600 mb-2">
                        <strong>Admissions:</strong><br>
                        admissions@eduskillpro.edu
                    </p>
                    <p class="text-gray-600">
                        <strong>Support:</strong><br>
                        support@eduskillpro.edu
                    </p>
                    <div class="mt-4">
                        <a href="mailto:admissions@eduskillpro.edu" class="text-primary-blue font-semibold hover:underline inline-flex items-center gap-2">
                            <i class="fas fa-envelope"></i> Send Email
                        </a>
                    </div>
                </div>
            </div>

            <!-- Contact Form & Map -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <!-- Contact Form -->
                <div class="contact-card p-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Send us a Message</h3>

                    <?php if ($error): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="success-message success-animation">
                            <i class="fas fa-check-circle"></i>
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" id="contactForm">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="input-group">
                                <label class="input-label">
                                    <i class="fas fa-user"></i>
                                    Full Name <span class="required">*</span>
                                </label>
                                <input
                                    type="text"
                                    name="name"
                                    class="input-field"
                                    placeholder="Enter your name"
                                    required
                                    value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                            </div>

                            <div class="input-group">
                                <label class="input-label">
                                    <i class="fas fa-envelope"></i>
                                    Email Address <span class="required">*</span>
                                </label>
                                <input
                                    type="email"
                                    name="email"
                                    class="input-field"
                                    placeholder="your@email.com"
                                    required
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="input-group">
                                <label class="input-label">
                                    <i class="fas fa-phone"></i>
                                    Phone Number
                                </label>
                                <input
                                    type="tel"
                                    name="phone"
                                    class="input-field"
                                    placeholder="+91 98765 43210"
                                    value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                            </div>

                            <div class="input-group">
                                <label class="input-label">
                                    <i class="fas fa-tag"></i>
                                    Subject <span class="required">*</span>
                                </label>
                                <select name="subject" class="input-field" required>
                                    <option value="">Select a subject</option>
                                    <option value="Admission Inquiry" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Admission Inquiry') ? 'selected' : ''; ?>>Admission Inquiry</option>
                                    <option value="Course Information" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Course Information') ? 'selected' : ''; ?>>Course Information</option>
                                    <option value="Fee Structure" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Fee Structure') ? 'selected' : ''; ?>>Fee Structure</option>
                                    <option value="Placement Query" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Placement Query') ? 'selected' : ''; ?>>Placement Query</option>
                                    <option value="Technical Support" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Technical Support') ? 'selected' : ''; ?>>Technical Support</option>
                                    <option value="Other" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="input-group">
                            <label class="input-label">
                                <i class="fas fa-comment"></i>
                                Your Message <span class="required">*</span>
                            </label>
                            <textarea
                                name="message"
                                class="input-field"
                                placeholder="Please type your message here..."
                                required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                        </div>

                        <div class="mb-6">
                            <label class="flex items-center gap-3">
                                <input type="checkbox" name="newsletter" class="w-5 h-5 text-primary-blue" checked>
                                <span class="text-gray-700">Subscribe to our newsletter for updates and course information</span>
                            </label>
                        </div>

                        <!-- Add a simple bot prevention field -->
                        <input type="text" name="website" style="display: none;" autocomplete="off">

                        <button type="submit" class="submit-btn" id="submitBtn">
                            <i class="fas fa-paper-plane"></i>
                            Send Message
                        </button>
                    </form>

                    <div class="mt-8 pt-8 border-t border-gray-100">
                        <p class="text-center text-gray-600 mb-4">Connect with us on social media</p>
                        <div class="social-contact justify-center">
                            <a href="#" class="social-link">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="social-link">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="social-link">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <a href="#" class="social-link">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="social-link">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Map & Info -->
                <div>
                    <div class="map-container mb-8">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3503.614360206944!2d77.20898111508189!3d28.57828298244065!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x390ce2daa9eb4d0b%3A0x717971125923e5d!2sIndia%20Gate!5e0!3m2!1sen!2sin!4v1648028621240!5m2!1sen!2sin"
                            width="100%"
                            height="300"
                            style="border:0;"
                            allowfullscreen=""
                            loading="lazy"
                            title="EduSkill Pro Academy Location"></iframe>
                    </div>

                    <div class="info-card">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Frequently Asked Questions</h3>

                        <div class="space-y-4">
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-1">What are the admission requirements?</h4>
                                <p class="text-gray-600 text-sm">Minimum 12th grade completion. Some courses may have additional prerequisites.</p>
                            </div>

                            <div>
                                <h4 class="font-semibold text-gray-800 mb-1">Do you offer payment plans?</h4>
                                <p class="text-gray-600 text-sm">Yes, we offer flexible EMI options for all courses.</p>
                            </div>

                            <div>
                                <h4 class="font-semibold text-gray-800 mb-1">Can I visit the campus?</h4>
                                <p class="text-gray-600 text-sm">Yes, we offer campus tours Monday to Friday, 10 AM to 4 PM.</p>
                            </div>

                            <div>
                                <h4 class="font-semibold text-gray-800 mb-1">What is the placement process?</h4>
                                <p class="text-gray-600 text-sm">We provide 100% placement assistance with our industry partners.</p>
                            </div>
                        </div>

                        <div class="mt-6 pt-6 border-t border-gray-100">
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-clock text-accent-teal mr-2"></i>
                                <strong>Response Time:</strong> We typically respond within 24 hours during business days.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- WhatsApp Contact -->
    <section class="py-16 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="bg-gradient-to-r from-green-50 to-teal-50 rounded-2xl p-8 border border-green-100">
                <div class="w-20 h-20 bg-gradient-to-br from-green-500 to-green-700 rounded-full flex items-center justify-center text-white text-3xl mx-auto mb-6">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Quick WhatsApp Support</h3>
                <p class="text-gray-600 mb-8 max-w-2xl mx-auto">
                    For quick queries, connect with our admissions team on WhatsApp. We respond within minutes!
                </p>
                <a href="https://wa.me/919876543210?text=Hello%20EduSkill%20Pro%20Academy,%20I%20have%20a%20question%20about%20admissions"
                    target="_blank"
                    class="inline-flex items-center gap-3 bg-green-600 text-white px-8 py-4 rounded-xl font-bold text-lg hover:bg-green-700 transition-colors shadow-lg">
                    <i class="fab fa-whatsapp"></i>
                    Chat on WhatsApp
                </a>
            </div>
        </div>
    </section>

    <?php include "includes/footer.php"; ?>

    <script>
        // Form validation and submission
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('contactForm');
            const submitBtn = document.getElementById('submitBtn');

            // Simple bot prevention
            const websiteField = form.querySelector('input[name="website"]');
            if (websiteField) {
                websiteField.addEventListener('input', function() {
                    if (this.value) {
                        // This is likely a bot - don't submit
                        form.reset();
                        showToast('Submission blocked. Please contact us directly.', 'error');
                    }
                });
            }

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Validate form
                if (!validateForm()) {
                    showToast('Please fill in all required fields correctly.', 'error');
                    return;
                }

                // Show loading state
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                submitBtn.disabled = true;

                // Submit form via AJAX (optional, falls back to normal submit)
                try {
                    // Create FormData
                    const formData = new FormData(form);

                    // Send AJAX request
                    fetch('', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => response.text())
                        .then(data => {
                            // Reload page to show success message
                            window.location.reload();
                        })
                        .catch(error => {
                            // If AJAX fails, submit normally
                            console.log('AJAX failed, submitting normally');
                            form.submit();
                        });

                } catch (error) {
                    // Fallback to normal form submission
                    form.submit();
                }
            });

            function validateForm() {
                let isValid = true;
                const requiredFields = form.querySelectorAll('[required]');

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('border-red-500');
                        isValid = false;
                    } else {
                        field.classList.remove('border-red-500');

                        // Email validation
                        if (field.type === 'email') {
                            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                            if (!emailRegex.test(field.value)) {
                                field.classList.add('border-red-500');
                                isValid = false;
                            }
                        }
                    }
                });

                return isValid;
            }

            // Real-time validation
            const inputs = form.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.hasAttribute('required') && !this.value.trim()) {
                        this.classList.add('border-red-500');
                    } else {
                        this.classList.remove('border-red-500');

                        // Email validation
                        if (this.type === 'email' && this.value) {
                            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                            if (!emailRegex.test(this.value)) {
                                this.classList.add('border-red-500');
                            }
                        }
                    }
                });

                input.addEventListener('input', function() {
                    if (this.value.trim()) {
                        this.classList.remove('border-red-500');
                    }
                });
            });

            function showToast(message, type) {
                // Remove existing toasts
                const existingToasts = document.querySelectorAll('.custom-toast');
                existingToasts.forEach(toast => toast.remove());

                // Create new toast
                const toast = document.createElement('div');
                toast.className = `custom-toast fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg text-white font-semibold z-50 ${
                    type === 'error' ? 'bg-red-500' : 'bg-green-500'
                }`;
                toast.textContent = message;
                toast.style.animation = 'slideIn 0.3s ease';

                document.body.appendChild(toast);

                // Remove toast after 3 seconds
                setTimeout(() => {
                    toast.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }

            // Add CSS animations for toast
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                
                @keyframes slideOut {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                }
                
                select.input-field {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%234A6FA5'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
                    background-repeat: no-repeat;
                    background-position: right 20px center;
                    background-size: 20px;
                    padding-right: 50px;
                    appearance: none;
                }
            `;
            document.head.appendChild(style);

            // If there's a success message, scroll to it
            <?php if ($success): ?>
                setTimeout(() => {
                    const successMsg = document.querySelector('.success-message');
                    if (successMsg) {
                        successMsg.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }
                }, 100);
            <?php endif; ?>
        });
    </script>
</body>

</html>