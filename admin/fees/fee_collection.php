<?php
require_once __DIR__ . '/../../config/db.php';

// Soft delete
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    mysqli_query($conn, "UPDATE fee_collections SET status='inactive' WHERE id=$id");
    header("Location: fee_collection.php");
    exit;
}

// Count statistics
$total_query = "SELECT COUNT(*) as count FROM fee_collections WHERE status='active'";
$total_result = mysqli_query($conn, $total_query);
$total_collections = mysqli_fetch_assoc($total_result)['count'];

$amount_query = "SELECT SUM(amount_paid) as total_amount FROM fee_collections WHERE status='active'";
$amount_result = mysqli_query($conn, $amount_query);
$total_amount = mysqli_fetch_assoc($amount_result)['total_amount'];
$total_amount_formatted = $total_amount ? number_format($total_amount, 2) : '0.00';

$today_query = "SELECT SUM(amount_paid) as today_amount FROM fee_collections WHERE status='active' AND payment_date = CURDATE()";
$today_result = mysqli_query($conn, $today_query);
$today_amount = mysqli_fetch_assoc($today_result)['today_amount'];
$today_amount_formatted = $today_amount ? number_format($today_amount, 2) : '0.00';

// Fetch all fee collections with details
$collections = mysqli_query($conn, "
    SELECT fc.*, 
           st.name AS student_name, 
           st.student_code,
           sk.skill_name, 
           se.session_name, 
           b.batch_name
    FROM fee_collections fc
    JOIN students st ON fc.student_id = st.id
    JOIN skills sk ON fc.skill_id = sk.id
    JOIN sessions se ON fc.session_id = se.id
    JOIN batches b ON fc.batch_id = b.id
    WHERE fc.status='active'
    ORDER BY fc.payment_date DESC, fc.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Fee Collection | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        
           
            /* Dark sidebar */
            
        .action-btn {
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
            font-size: 13px;
        }

        .table-container {
            background: white;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        tr:hover {
            background: #f9fafb !important;
        }

        .payment-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .badge-cash {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-bank {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-online {
            background: #ede9fe;
            color: #5b21b6;
        }

        .badge-card {
            background: #fef3c7;
            color: #92400e;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">

    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="flex">
        <!-- SIDEBAR -->
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <!-- MAIN CONTENT -->
        <main class="flex-1 p-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Fee Collection Management</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-cash-register text-blue-500 mr-1"></i>
                        Manage student fee payments and collections
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                            placeholder="Search collections..."
                            class="pl-9 pr-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-56 text-sm"
                            id="searchInput">
                    </div>
                    <a href="collect_fee.php"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                        <i class="fas fa-plus mr-1"></i> Collect Fee
                    </a>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-3 gap-3 mb-6">
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Total Collections</p>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo $total_collections; ?></h3>
                </div>
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Total Collected</p>
                    <h3 class="text-xl font-bold text-gray-800">Rs<?php echo $total_amount_formatted; ?></h3>
                </div>
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Today's Collection</p>
                    <h3 class="text-xl font-bold text-gray-800">Rs<?php echo $today_amount_formatted; ?></h3>
                </div>
            </div>

            <!-- Fee Collections Table -->
            <div class="table-container">
                <div class="px-4 py-3 border-b bg-gray-50">
                    <h3 class="font-medium text-gray-800">All Fee Collections (<?php echo $total_collections; ?>)</h3>
                </div>

                <?php if ($total_collections > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">ID</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Student</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Skill</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Batch</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Amount</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Date</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Method</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php while ($row = mysqli_fetch_assoc($collections)):
                                    $badge_class = '';
                                    switch (strtolower($row['payment_method'])) {
                                        case 'cash':
                                            $badge_class = 'badge-cash';
                                            break;
                                        case 'bank transfer':
                                            $badge_class = 'badge-bank';
                                            break;
                                        case 'online':
                                            $badge_class = 'badge-online';
                                            break;
                                        case 'card':
                                            $badge_class = 'badge-card';
                                            break;
                                        default:
                                            $badge_class = 'badge-cash';
                                    }
                                ?>
                                    <tr>
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900">#<?= $row['id'] ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900"><?= htmlspecialchars($row['student_name']) ?></div>
                                            <div class="text-xs text-gray-500"><?= $row['student_code'] ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900"><?= htmlspecialchars($row['skill_name']) ?></div>
                                            <div class="text-xs text-gray-500"><?= htmlspecialchars($row['session_name']) ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-600"><?= htmlspecialchars($row['batch_name']) ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="text-lg font-bold text-green-600">
                                                Rs<?= number_format($row['amount_paid'], 2) ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-600"><?= date('d M, Y', strtotime($row['payment_date'])) ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="<?= $badge_class ?> payment-badge">
                                                <?= htmlspecialchars($row['payment_method']) ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex gap-1">
                                                <a href="collect_fee.php?id=<?= $row['id'] ?>"
                                                    class="action-btn bg-blue-50 text-blue-700 hover:bg-blue-100"
                                                    title="Edit">
                                                    <i class="fas fa-edit text-xs"></i>
                                                </a>
                                                <a href="fee_collection.php?delete_id=<?= $row['id'] ?>"
                                                    onclick="return confirm('Delete this fee collection for <?= htmlspecialchars($row['student_name']) ?>?')"
                                                    class="action-btn bg-red-50 text-red-700 hover:bg-red-100"
                                                    title="Delete">
                                                    <i class="fas fa-trash text-xs"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-8 text-center">
                        <i class="fas fa-cash-register text-gray-300 text-4xl mb-3"></i>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">No Fee Collections Found</h3>
                        <p class="text-gray-500 text-sm mb-4">Start collecting fees from enrolled students</p>
                        <a href="collect_fee.php"
                            class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                            <i class="fas fa-plus"></i> Collect First Fee
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');

            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    const searchTerm = e.target.value.toLowerCase();
                    const tableRows = document.querySelectorAll('tbody tr');

                    tableRows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
                            row.style.display = 'table-row';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>

</body>

</html>