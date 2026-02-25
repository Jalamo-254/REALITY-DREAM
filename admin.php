<?php
// ============================================
// Admin Dashboard - View All Contact Submissions
// ============================================

session_start();
require_once 'db_config.php';

// Protect admin area
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Get all submissions
$sql = "SELECT * FROM contacts ORDER BY submitted_date DESC";
$result = $conn->query($sql);

if (!$result) {
    die("Error fetching data: " . $conn->error);
}

$submissions = $result->fetch_all(MYSQLI_ASSOC);
$totalSubmissions = count($submissions);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Contact Submissions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .dashboard-container { max-width: 1400px; margin: 0 auto; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="dashboard-container py-8 px-4">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Admin Dashboard</h1>
            <p class="text-gray-600">Contact Form Submissions</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="text-4xl font-bold text-blue-600"><?php echo $totalSubmissions; ?></div>
                    <div class="ml-4">
                        <p class="text-gray-600 font-semibold">Total Submissions</p>
                        <p class="text-gray-500 text-sm">All time</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="text-4xl font-bold text-green-600"><?php echo count(array_filter($submissions, function($s) { return $s['status'] === 'New'; })); ?></div>
                    <div class="ml-4">
                        <p class="text-gray-600 font-semibold">New Submissions</p>
                        <p class="text-gray-500 text-sm">Unreviewed</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="text-4xl font-bold text-purple-600"><?php echo date('M d, Y'); ?></div>
                    <div class="ml-4">
                        <p class="text-gray-600 font-semibold">Today's Date</p>
                        <p class="text-gray-500 text-sm">Current</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submissions Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">Recent Submissions</h2>
            </div>

            <?php if ($totalSubmissions > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Name</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Email</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Phone</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Course</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Date</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                                <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $submission): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 text-sm text-gray-800">
                                        <?php echo htmlspecialchars($submission['first_name'] . ' ' . $submission['last_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-800">
                                        <a href="mailto:<?php echo htmlspecialchars($submission['email']); ?>" 
                                           class="text-blue-600 hover:underline">
                                            <?php echo htmlspecialchars($submission['email']); ?>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-800">
                                        <a href="tel:<?php echo htmlspecialchars($submission['phone']); ?>" 
                                           class="text-blue-600 hover:underline">
                                            <?php echo htmlspecialchars($submission['phone']); ?>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-800">
                                        <?php echo htmlspecialchars($submission['course']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php echo date('M d, Y H:i', strtotime($submission['submitted_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $submission['status'] === 'New' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo htmlspecialchars($submission['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button onclick="viewDetails(<?php echo $submission['id']; ?>, `<?php echo addslashes(htmlspecialchars($submission['message'])); ?>`)" 
                                                class="text-blue-600 hover:text-blue-800 font-semibold">
                                            View Details
                                        </button>
                                        <?php if (!empty($submission['attachment'])): ?>
                                            <div class="mt-2">
                                                <a href="<?php echo htmlspecialchars($submission['attachment']); ?>" target="_blank" class="text-green-600 hover:underline text-sm">View Attachment</a>
                                            </div>
                                        <?php endif; ?>
                                        <div class="mt-2">
                                            <form method="post" action="update_status.php" style="display:inline;">
                                                <input type="hidden" name="id" value="<?php echo $submission['id']; ?>">
                                                <select name="status" onchange="this.form.submit()" class="text-sm rounded border px-2 py-1">
                                                    <option value="New" <?php echo $submission['status'] === 'New' ? 'selected' : ''; ?>>New</option>
                                                    <option value="Reviewed" <?php echo $submission['status'] === 'Reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                                    <option value="Contacted" <?php echo $submission['status'] === 'Contacted' ? 'selected' : ''; ?>>Contacted</option>
                                                </select>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="px-6 py-8 text-center">
                    <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-600 text-lg">No submissions yet</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Back Button -->
        <div class="mt-6">
            <a href="index.html" class="text-blue-600 hover:text-blue-800 font-semibold">
                <i class="fas fa-arrow-left mr-2"></i> Back to Website
            </a>
            <a href="change_password.php" class="ml-6 text-indigo-600 hover:text-indigo-800 font-semibold">Change Password</a>
            <a href="logout.php" class="ml-6 text-red-600 hover:text-red-800 font-semibold">Logout</a>
        </div>
    </div>

    <!-- Modal for Details -->
    <div id="detailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full max-h-96 overflow-y-auto">
            <div class="sticky top-0 bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-xl font-semibold text-gray-800">Message Details</h3>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <div class="px-6 py-4">
                <div id="modalContent"></div>
            </div>
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end">
                <button onclick="closeModal()" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-6 rounded">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        function viewDetails(id, message) {
            const modal = document.getElementById('detailsModal');
            const content = document.getElementById('modalContent');
            content.innerHTML = `
                <div class="space-y-3">
                    <div>
                        <p class="text-gray-500 text-sm font-semibold">MESSAGE</p>
                        <p class="text-gray-800 mt-1 whitespace-pre-wrap">${message}</p>
                    </div>
                </div>
            `;
            modal.classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('detailsModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('detailsModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>
