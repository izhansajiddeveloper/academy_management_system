<?php
require_once __DIR__ . '/../../config/db.php';


$skill_id = $session_id = $total_fee = "";
$editing = false;
$page_title = "Add New Fee Structure";
$button_text = "Add Structure";
$structure_info = null;

// Check if editing
if (isset($_GET['id'])) {
    $editing = true;
    $id = intval($_GET['id']);
    $structure = mysqli_query($conn, "SELECT * FROM fee_structures WHERE id=$id");
    $row = mysqli_fetch_assoc($structure);
    if ($row) {
        $skill_id = $row['skill_id'];
        $session_id = $row['session_id'];
        $total_fee = $row['total_fee'];
        $page_title = "Edit Fee Structure";
        $button_text = "Update Structure";

        // Get current structure info for display
        $info_query = mysqli_query($conn, "
            SELECT fs.*, sk.skill_name, sk.level, se.session_name
            FROM fee_structures fs
            JOIN skills sk ON fs.skill_id = sk.id
            JOIN sessions se ON fs.session_id = se.id
            WHERE fs.id = $id
        ");
        $structure_info = mysqli_fetch_assoc($info_query);
    }
}

// Handle form submission
if (isset($_POST['submit'])) {
    $skill_id = intval($_POST['skill_id']);
    $session_id = intval($_POST['session_id']);
    $total_fee = floatval($_POST['total_fee']);

    // Check if fee structure already exists for this skill and session
    $check_query = mysqli_query(
        $conn,
        "
        SELECT id FROM fee_structures 
        WHERE skill_id = $skill_id 
        AND session_id = $session_id 
        AND status = 'active'
        " . ($editing ? "AND id != $id" : "")
    );

    if (mysqli_num_rows($check_query) > 0) {
        $error_message = "A fee structure already exists for this skill and session combination.";
    } else {
        if ($editing) {
            mysqli_query($conn, "UPDATE fee_structures SET skill_id=$skill_id, session_id=$session_id, total_fee=$total_fee WHERE id=$id");
            $success_message = "Fee structure updated successfully!";
        } else {
            mysqli_query($conn, "INSERT INTO fee_structures (skill_id, session_id, total_fee, status) VALUES ($skill_id, $session_id, $total_fee, 'active')");
            $success_message = "Fee structure created successfully!";
        }

        // Redirect after successful submission
        if (!isset($error_message)) {
            header("Location: fee_structures.php");
            exit;
        }
    }
}

// Fetch skills and sessions for dropdowns
$skills = mysqli_query($conn, "SELECT * FROM skills WHERE status='active' ORDER BY skill_name");
$sessions = mysqli_query($conn, "SELECT * FROM sessions WHERE status='active' ORDER BY session_name");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?> | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #6366f1;
            --secondary: #10b981;
            --accent: #f59e0b;
            --warning: #ef4444;
            --dark: #1f2937;
            --light: #f8fafc;
        }

        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
        }




        .form-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
        }

        .form-input {
            transition: all 0.3s ease;
            border: 2px solid #e5e7eb;
        }

        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #4f46e5 100%);
            color: white;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--secondary) 0%, #059669 100%);
            color: white;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }

        .info-card {
            background: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%);
            border-radius: 12px;
            border: 1px solid #bae6fd;
        }

        .currency-input {
            position: relative;
        }

        .currency-symbol {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: 600;
            color: #059669;
        }
    </style>
</head>

<body class="min-h-screen">

    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="flex min-h-screen">

        <!-- SIDEBAR -->
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800"><?= $page_title ?></h1>
                    <p class="text-gray-500 mt-2">
                        <i class="fas fa-calculator text-green-500 mr-2"></i>
                        <?= $editing ? 'Update fee structure details' : 'Create a new fee structure for courses' ?>
                    </p>
                </div>
                <div>
                    <a href="fee_structures.php"
                        class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-5 py-3 rounded-lg font-medium hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Structures
                    </a>
                </div>
            </div>

            <!-- Current Structure Info (Only for edit) -->
            <?php if ($editing && $structure_info): ?>
                <div class="max-w-2xl mx-auto mb-6">
                    <div class="info-card p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-info-circle text-blue-600"></i>
                            </div>
                            <div>
                                <h3 class="font-medium text-gray-800">Current Structure Information</h3>
                                <p class="text-sm text-gray-500">Editing existing fee structure</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Skill</p>
                                <p class="font-medium text-gray-800"><?= htmlspecialchars($structure_info['skill_name']) ?> (<?= $structure_info['level'] ?>)</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Session</p>
                                <p class="font-medium text-gray-800"><?= htmlspecialchars($structure_info['session_name']) ?></p>
                            </div>
                            <div class="md:col-span-2">
                                <p class="text-sm text-gray-500 mb-1">Current Fee</p>
                                <p class="text-2xl font-bold text-green-600">Rs<?= number_format($structure_info['total_fee'], 2) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Form Card -->
            <div class="max-w-2xl mx-auto">
                <div class="form-card p-8">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
                        <div class="w-12 h-12 <?= $editing ? 'bg-yellow-100' : 'bg-green-100' ?> rounded-full flex items-center justify-center">
                            <i class="fas <?= $editing ? 'fa-edit text-yellow-600' : 'fa-plus text-green-600' ?> text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-800"><?= $editing ? 'Update Fee Details' : 'Fee Structure Details' ?></h2>
                            <p class="text-sm text-gray-500">Fill in the fee structure information below</p>
                        </div>
                    </div>

                    <?php if (isset($error_message)): ?>
                        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3">
                            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-exclamation-triangle text-red-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-red-800">Error!</p>
                                <p class="text-red-600 text-sm"><?php echo $error_message; ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($success_message)): ?>
                        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check text-green-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-green-800">Success!</p>
                                <p class="text-green-600 text-sm"><?php echo $success_message; ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-6">
                        <!-- Skill Selection -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <i class="fas fa-book text-purple-500 mr-2"></i>
                                Select Skill *
                            </label>
                            <select name="skill_id"
                                class="w-full px-4 py-3 rounded-lg form-input focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                                <option value="">-- Select a Skill --</option>
                                <?php mysqli_data_seek($skills, 0);
                                while ($sk = mysqli_fetch_assoc($skills)) { ?>
                                    <option value="<?= $sk['id'] ?>" <?= $sk['id'] == $skill_id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sk['skill_name']) ?> (<?= $sk['level'] ?>)
                                    </option>
                                <?php } ?>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">
                                Choose the skill/course for this fee structure
                            </p>
                        </div>

                        <!-- Session Selection -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <i class="fas fa-calendar text-blue-500 mr-2"></i>
                                Select Session *
                            </label>
                            <select name="session_id"
                                class="w-full px-4 py-3 rounded-lg form-input focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                                <option value="">-- Select a Session --</option>
                                <?php mysqli_data_seek($sessions, 0);
                                while ($se = mysqli_fetch_assoc($sessions)) { ?>
                                    <option value="<?= $se['id'] ?>" <?= $se['id'] == $session_id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($se['session_name']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">
                                Choose the session for this fee structure
                            </p>
                        </div>

                        <!-- Total Fee -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <i class="fas fa-money-bill-wave text-green-500 mr-2"></i>
                                Total Course Fee *
                            </label>
                            <div class="relative">
                                <span class="currency-symbol">Rs</span>
                                <input type="number"
                                    name="total_fee"
                                    class="w-full pl-8 pr-4 py-3 rounded-lg form-input focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    value="<?= $total_fee ?>"
                                    step="0.01"
                                    min="0"
                                    placeholder="Enter total course fee"
                                    required>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                Enter the total fee for the complete course (in Indian Rupees)
                            </p>
                        </div>

                        <!-- Important Notes -->
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-info-circle text-blue-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium text-blue-800 mb-1">Important Information</h4>
                                    <ul class="text-sm text-blue-600 space-y-1">
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-check-circle text-xs"></i>
                                            <span>Only one fee structure can exist per skill-session combination</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-check-circle text-xs"></i>
                                            <span>Fee structures are used to calculate student fees during enrollment</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-check-circle text-xs"></i>
                                            <span>You can update the fee anytime, but it won't affect existing enrollments</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-4 pt-4 border-t border-gray-100">
                            <button type="submit"
                                name="submit"
                                class="flex-1 <?= $editing ? 'btn-primary' : 'btn-success' ?> px-6 py-3 rounded-lg font-medium flex items-center justify-center gap-2">
                                <i class="fas <?= $editing ? 'fa-save' : 'fa-plus' ?>"></i>
                                <?= $button_text ?>
                            </button>
                            <a href="fee_structures.php"
                                class="flex-1 bg-gray-100 text-gray-700 px-6 py-3 rounded-lg font-medium hover:bg-gray-200 transition-colors flex items-center justify-center gap-2">
                                <i class="fas fa-times"></i>
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add focus effects to form inputs
            const formInputs = document.querySelectorAll('.form-input');
            formInputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('ring-2', 'ring-blue-200', 'ring-opacity-50');
                });

                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('ring-2', 'ring-blue-200', 'ring-opacity-50');
                });
            });

            // Format fee input
            const feeInput = document.querySelector('input[name="total_fee"]');
            if (feeInput) {
                feeInput.addEventListener('input', function() {
                    let value = this.value;
                    // Remove any non-numeric characters except decimal point
                    value = value.replace(/[^\d.]/g, '');

                    // Ensure only one decimal point
                    const decimalSplit = value.split('.');
                    if (decimalSplit.length > 2) {
                        value = decimalSplit[0] + '.' + decimalSplit.slice(1).join('');
                    }

                    // Limit to 2 decimal places
                    if (decimalSplit.length === 2) {
                        value = decimalSplit[0] + '.' + decimalSplit[1].slice(0, 2);
                    }

                    this.value = value;
                });

                // Format on blur
                feeInput.addEventListener('blur', function() {
                    if (this.value) {
                        const num = parseFloat(this.value);
                        if (!isNaN(num)) {
                            this.value = num.toFixed(2);
                        }
                    }
                });
            }

            // Auto-fill fee based on skill level when skill is selected
            const skillSelect = document.querySelector('select[name="skill_id"]');
            const feeField = document.querySelector('input[name="total_fee"]');

            if (skillSelect && feeField && !<?= $editing ? 'true' : 'false' ?>) {
                // Store skill fee suggestions based on level
                const feeSuggestions = {
                    'Beginner': 5000,
                    'Intermediate': 10000,
                    'Advanced': 15000
                };

                // Parse skill options to get their levels
                const skillOptions = {};
                <?php
                mysqli_data_seek($skills, 0);
                while ($sk = mysqli_fetch_assoc($skills)) {
                    echo "skillOptions[{$sk['id']}] = '{$sk['level']}';";
                }
                ?>

                skillSelect.addEventListener('change', function() {
                    const skillId = this.value;
                    if (skillId && skillOptions[skillId]) {
                        const level = skillOptions[skillId];
                        const suggestedFee = feeSuggestions[level] || 0;
                        if (suggestedFee > 0 && (!feeField.value || feeField.value == 0)) {
                            if (confirm(`Based on the ${level} level, would you like to set the fee to Rs${suggestedFee.toLocaleString('en-IN')}?`)) {
                                feeField.value = suggestedFee.toFixed(2);
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>

</html>