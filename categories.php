<?php
// File: categories.php
// Description: Handles CRUD operations for Categories.

include 'db_connect.php';


$message = '';

// Handle Create/Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $status = $conn->real_escape_string($_POST['status']);
    $now = date('Y-m-d H:i:s');

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Update
        $id = $conn->real_escape_string($_POST['id']);
        $sql = "UPDATE Categories SET title='$title', status='$status', modified='$now' WHERE id=$id";
        if ($conn->query($sql) === TRUE) {
            $message = "<div class='alert alert-success'>Category updated successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error updating category: " . $conn->error . "</div>";
        }
    } else {
        // Create
        $sql = "INSERT INTO Categories (title, status, created, modified) VALUES ('$title', '$status', '$now', '$now')";
        if ($conn->query($sql) === TRUE) {
            $message = "<div class='alert alert-success'>Category added successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error adding category: " . $conn->error . "</div>";
        }
    }
}

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $conn->real_escape_string($_GET['id']);
    $sql = "DELETE FROM Categories WHERE id=$id";
    if ($conn->query($sql) === TRUE) {
        $message = "<div class='alert alert-success'>Category deleted successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Error deleting category: " . $conn->error . "</div>";
    }
}

// Fetch categories for display
$sql = "SELECT * FROM Categories ORDER BY created DESC";
$result = $conn->query($sql);

// Fetch category for editing
$edit_category = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = $conn->real_escape_string($_GET['id']);
    $edit_sql = "SELECT * FROM Categories WHERE id=$id";
    $edit_result = $conn->query($edit_sql);
    if ($edit_result->num_rows > 0) {
        $edit_category = $edit_result->fetch_assoc();
    }
}
?>

<h2 class="my-4">Manage Categories</h2>

<?php echo $message; ?>

<div class="card p-4 mb-4">
    <h3><?php echo $edit_category ? 'Edit Category' : 'Add New Category'; ?></h3>
    <form method="POST" action="categories.php">
        <?php if ($edit_category): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_category['id']); ?>">
        <?php endif; ?>
        <div class="mb-3">
            <label for="title" class="form-label">Category Title</label>
            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($edit_category['title'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status" required>
                <option value="active" <?php echo ($edit_category && $edit_category['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo ($edit_category && $edit_category['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo $edit_category ? 'Update Category' : 'Add Category'; ?></button>
        <?php if ($edit_category): ?>
            <a href="categories.php" class="btn btn-secondary">Cancel Edit</a>
        <?php endif; ?>
    </form>
</div>

<h3 class="mt-4">All Categories</h3>
<?php if ($result->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
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
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
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
                            <a href="categories.php?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                            <a href="categories.php?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this category?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p class="alert alert-warning">No categories found.</p>
<?php endif; ?>


