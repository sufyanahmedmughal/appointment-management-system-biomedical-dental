<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

$db = getDB();
$message = '';
$messageType = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $review_id = intval($_POST['review_id'] ?? 0);
        
        if ($action === 'approve' && $review_id > 0) {
            $stmt = $db->prepare("UPDATE reviews SET approved = 1 WHERE id = ?");
            if ($stmt->execute([$review_id])) {
                $message = 'Review approved successfully';
                $messageType = 'success';
            }
        }
        
        if ($action === 'reject' && $review_id > 0) {
            $stmt = $db->prepare("UPDATE reviews SET approved = 0 WHERE id = ?");
            if ($stmt->execute([$review_id])) {
                $message = 'Review rejected';
                $messageType = 'success';
            }
        }
        
        if ($action === 'delete' && $review_id > 0) {
            $stmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
            if ($stmt->execute([$review_id])) {
                $message = 'Review deleted successfully';
                $messageType = 'success';
            }
        }
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'all';

// Build query
$query = "SELECT * FROM reviews WHERE 1=1";

if ($filter === 'pending') {
    $query .= " AND approved = 0 AND source = 'manual'";
} elseif ($filter === 'approved') {
    $query .= " AND approved = 1";
} elseif ($filter === 'google') {
    $query .= " AND source = 'google'";
}

$query .= " ORDER BY created_at DESC";

$stmt = $db->query($query);
$reviews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <i class="fas fa-tooth text-blue-600 text-2xl"></i>
                <h1 class="text-xl font-bold text-gray-800">Admin Panel</h1>
            </div>
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="text-blue-600 hover:underline">Dashboard</a>
                <span class="text-gray-700"><?php echo $_SESSION['admin_name']; ?></span>
                <a href="logout.php" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold text-gray-800">Reviews Management</h2>
        </div>

        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check' : 'exclamation'; ?>-circle mr-2"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex space-x-2">
                <a href="?filter=all" class="flex-1 text-center px-4 py-2 rounded-lg <?php echo $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                    All Reviews
                </a>
                <a href="?filter=pending" class="flex-1 text-center px-4 py-2 rounded-lg <?php echo $filter === 'pending' ? 'bg-yellow-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                    Pending Approval
                </a>
                <a href="?filter=approved" class="flex-1 text-center px-4 py-2 rounded-lg <?php echo $filter === 'approved' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                    Approved
                </a>
                <a href="?filter=google" class="flex-1 text-center px-4 py-2 rounded-lg <?php echo $filter === 'google' ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                    Google Reviews
                </a>
            </div>
        </div>

        <!-- Reviews List -->
        <div class="space-y-4">
            <?php if (empty($reviews)): ?>
            <div class="bg-white rounded-lg shadow-md p-8 text-center text-gray-500">
                <i class="fas fa-inbox text-6xl mb-4"></i>
                <p class="text-xl">No reviews found</p>
            </div>
            <?php else: ?>
            <?php foreach ($reviews as $review): ?>
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo $review['approved'] ? '' : 'border-l-4 border-yellow-500'; ?>">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex-1">
                        <div class="flex items-center mb-2">
                            <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white text-xl font-bold mr-4">
                                <?php echo strtoupper(substr($review['name'], 0, 1)); ?>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800 text-lg"><?php echo htmlspecialchars($review['name']); ?></h3>
                                <div class="flex items-center space-x-3 text-sm text-gray-500">
                                    <span><i class="far fa-calendar mr-1"></i><?php echo formatDate($review['created_at']); ?></span>
                                    <?php if ($review['email']): ?>
                                    <span><i class="far fa-envelope mr-1"></i><?php echo htmlspecialchars($review['email']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex text-yellow-400 mb-3">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <i class="fas fa-star<?php echo $i < $review['rating'] ? '' : '-o'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        
                        <p class="text-gray-700 text-lg mb-3">"<?php echo htmlspecialchars($review['review_text']); ?>"</p>
                        
                        <div class="flex space-x-2">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $review['source'] === 'google' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                <i class="fab fa-<?php echo $review['source'] === 'google' ? 'google' : 'wpforms'; ?> mr-1"></i>
                                <?php echo ucfirst($review['source']); ?>
                            </span>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $review['approved'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                <i class="fas fa-<?php echo $review['approved'] ? 'check-circle' : 'clock'; ?> mr-1"></i>
                                <?php echo $review['approved'] ? 'Approved' : 'Pending'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="ml-4 flex flex-col space-y-2">
                        <?php if ($review['source'] === 'manual'): ?>
                            <?php if (!$review['approved']): ?>
                            <form method="POST" onsubmit="return confirm('Approve this review?');">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 w-full">
                                    <i class="fas fa-check mr-2"></i>Approve
                                </button>
                            </form>
                            <?php else: ?>
                            <form method="POST" onsubmit="return confirm('Reject this review?');">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                <button type="submit" class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700 w-full">
                                    <i class="fas fa-times mr-2"></i>Reject
                                </button>
                            </form>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <form method="POST" onsubmit="return confirm('Delete this review permanently?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 w-full">
                                <i class="fas fa-trash mr-2"></i>Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>