<?php
// File: comments.php
// Description: Handles CRUD operations for Comments.

include 'db_connect.php';



$message = '';

// Handle Create/Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $application_id = $conn->real_escape_string($_POST['application_id']);
    $name = $conn->real_escape_string($_POST['name']);
    $comment = $conn->real_escape_string($_POST['comment']);
    $rating = $conn->real_escape_string($_POST['rating']);
    $status = $conn->real_escape_string($_POST['status']);
    $now = date('Y-m-d H:i:s');

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Update
        $id = $conn->real_escape_string($_POST['id']);
        $sql = "UPDATE Comments SET application_id=$application_id, name='$name', comment='$comment', rating=$rating, status='$status', modified='$now' WHERE id=$id";
        if ($conn->query($sql) === TRUE) {
            $message = "<div class='alert alert-success'>Comment updated successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error updating comment: " . $conn->error . "</div>";
        }
    } else {
        // Create
        $sql = "INSERT INTO Comments (application_id, name, comment, rating, status, created, modified) VALUES ($application_id, '$name', '$comment', $rating, '$status', '$now', '$now')";
        if ($conn->query($sql) === TRUE) {
            $message = "<div class='alert alert-success'>Comment added successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error adding comment: " . $conn->error . "</div>";
        }
    }
}

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $conn->real_escape_string($_GET['id']);
    $sql = "DELETE FROM Comments WHERE id=$id";
    if ($conn->query($sql) === TRUE) {
        $message = "<div class='alert alert-success'>Comment deleted successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Error deleting comment: " . $conn->error . "</div>";
    }
}

// Fetch comments for display
$sql = "SELECT c.*, a.title as app_title FROM Comments c JOIN Applications a ON c.application_id = a.id ORDER BY c.created DESC";
$result = $conn->query($sql);

// Fetch comment for editing
$edit_comment = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = $conn->real_escape_string($_GET['id']);
    $edit_sql = "SELECT * FROM Comments WHERE id=$id";
    $edit_result = $conn->query($edit_sql);
    if ($edit_result->num_rows > 0) {
        $edit_comment = $edit_result->fetch_assoc();
    }
}

// Fetch applications for dropdown
$applications_result = $conn->query("SELECT id, title FROM Applications ORDER BY title ASC");
?>

<h2 class="my-4">Manage Comments</h2>

<?php echo $message; ?>

<div class="card p-4 mb-4">
    <h3><?php echo $edit_comment ? 'Edit Comment' : 'Add New Comment'; ?></h3>
    <form method="POST" action="comments.php">
        <?php if ($edit_comment): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_comment['id']); ?>">
        <?php endif; ?>

        <div class="mb-3">
            <label for="application_id" class="form-label">Application Review</label>
            <select class="form-select" id="application_id" name="application_id" required>
                <option value="">Select an application</option>
                <?php while ($app = $applications_result->fetch_assoc()): ?>
                    <option value="<?php echo $app['id']; ?>" <?php echo ($edit_comment && $edit_comment['application_id'] == $app['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($app['title']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="name" class="form-label">Your Name</label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($edit_comment['name'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
            <label for="comment" class="form-label">Comment</label>
            <textarea class="form-control" id="comment" name="comment" rows="3" required><?php echo htmlspecialchars($edit_comment['comment'] ?? ''); ?></textarea>
        </div>
        <div class="mb-3">
            <label for="rating" class="form-label">Rating (1-5)</label>
            <input type="number" class="form-control" id="rating" name="rating" min="1" max="5" value="<?php echo htmlspecialchars($edit_comment['rating'] ?? 5); ?>" required>
        </div>
        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status" required>
                <option value="active" <?php echo ($edit_comment && $edit_comment['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo ($edit_comment && $edit_comment['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo $edit_comment ? 'Update Comment' : 'Add Comment'; ?></button>
        <?php if ($edit_comment): ?>
            <a href="comments.php" class="btn btn-secondary">Cancel Edit</a>
        <?php endif; ?>
    </form>
</div>

<h3 class="mt-4">All Comments</h3>
<?php if ($result->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Application</th>
                    <th>Name</th>
                    <th>Comment</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Modified</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['app_title']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['comment']); ?></td>
                        <td><?php echo htmlspecialchars($row['rating']); ?></td>
                        <td>
                            <?php if ($row['status'] == 'active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatDateTime($row['created']); ?></td>
                        <td><?php echo formatDateTime($row['modified']); ?></td>
                        <td>
                            <a href="comments.php?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                            <a href="comments.php?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this comment?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p class="alert alert-warning">No comments found.</p>
<?php endif; ?>

