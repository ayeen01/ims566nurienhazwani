<?php
// File: applications.php
// Description: Handles CRUD operations for Applications, including image upload.

include 'db_connect.php';


$message = '';

// Directory for image uploads
$target_dir = "uploads/";
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

// Handle Create/Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_id = $conn->real_escape_string($_POST['category_id']);
    $posted_date = $conn->real_escape_string($_POST['posted_date']);
    $author = $conn->real_escape_string($_POST['author']);
    $title = $conn->real_escape_string($_POST['title']);
    $review = $conn->real_escape_string($_POST['review']);
    $status = $conn->real_escape_string($_POST['status']);
    $now = date('Y-m-d H:i:s');

    $image = '';
    $image_dir = '';

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $uploadOk = 1;

        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if($check !== false) {
            $uploadOk = 1;
        } else {
            $message = "<div class='alert alert-danger'>File is not an image.</div>";
            $uploadOk = 0;
        }

        // Check file size (e.g., 5MB limit)
        if ($_FILES["image"]["size"] > 5000000) {
            $message = "<div class='alert alert-danger'>Sorry, your file is too large.</div>";
            $uploadOk = 0;
        }

        // Allow certain file formats
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif" ) {
            $message = "<div class='alert alert-danger'>Sorry, only JPG, JPEG, PNG & GIF files are allowed.</div>";
            $uploadOk = 0;
        }

        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            // Do nothing, message is already set
        } else {
            // If everything is ok, try to upload file
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image = basename($_FILES["image"]["name"]);
                $image_dir = $target_dir;
            } else {
                $message = "<div class='alert alert-danger'>Sorry, there was an error uploading your file.</div>";
            }
        }
    } else if (isset($_POST['existing_image'])) {
        // If no new image uploaded, retain existing one
        $image = $conn->real_escape_string($_POST['existing_image']);
        $image_dir = $target_dir; // Assuming existing images are in 'uploads/'
    }


    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Update
        $id = $conn->real_escape_string($_POST['id']);
        $sql = "UPDATE Applications SET category_id=$category_id, posted_date='$posted_date', author='$author', title='$title', review='$review', status='$status', modified='$now'";
        if (!empty($image)) {
            $sql .= ", image='$image', image_dir='$image_dir'";
        }
        $sql .= " WHERE id=$id";

        if ($conn->query($sql) === TRUE) {
            $message = "<div class='alert alert-success'>Application review updated successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error updating application review: " . $conn->error . "</div>";
        }
    } else {
        // Create
        $sql = "INSERT INTO Applications (category_id, posted_date, author, title, review, image, image_dir, status, created, modified) VALUES ($category_id, '$posted_date', '$author', '$title', '$review', '$image', '$image_dir', '$status', '$now', '$now')";
        if ($conn->query($sql) === TRUE) {
            $message = "<div class='alert alert-success'>Application review added successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error adding application review: " . $conn->error . "</div>";
        }
    }
}

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $conn->real_escape_string($_GET['id']);
    // Optionally delete image file
    $stmt = $conn->prepare("SELECT image, image_dir FROM Applications WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($img_name, $img_dir);
    $stmt->fetch();
    $stmt->close();

    if ($img_name && file_exists($img_dir . $img_name)) {
        unlink($img_dir . $img_name);
    }

    $sql = "DELETE FROM Applications WHERE id=$id";
    if ($conn->query($sql) === TRUE) {
        $message = "<div class='alert alert-success'>Application review deleted successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Error deleting application review: " . $conn->error . "</div>";
    }
}

// Fetch application for editing
$edit_application = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = $conn->real_escape_string($_GET['id']);
    $edit_sql = "SELECT * FROM Applications WHERE id=$id";
    $edit_result = $conn->query($edit_sql);
    if ($edit_result->num_rows > 0) {
        $edit_application = $edit_result->fetch_assoc();
    }
}

// Fetch categories for dropdown
$categories_result = $conn->query("SELECT id, title FROM Categories ORDER BY title ASC");
?>

<h2 class="my-4">Manage Application Reviews</h2>

<?php echo $message; ?>

<div class="card p-4 mb-4">
    <h3><?php echo $edit_application ? 'Edit Application Review' : 'Add New Application Review'; ?></h3>
    <form method="POST" action="applications.php" enctype="multipart/form-data">
        <?php if ($edit_application): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_application['id']); ?>">
            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($edit_application['image']); ?>">
        <?php endif; ?>

        <div class="mb-3">
            <label for="category_id" class="form-label">Category</label>
            <select class="form-select" id="category_id" name="category_id" required>
                <option value="">Select a category</option>
                <?php while ($category = $categories_result->fetch_assoc()): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo ($edit_application && $edit_application['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['title']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="posted_date" class="form-label">Posted Date</label>
            <input type="date" class="form-control" id="posted_date" name="posted_date" value="<?php echo htmlspecialchars($edit_application['posted_date'] ?? date('Y-m-d')); ?>" required>
        </div>
        <div class="mb-3">
            <label for="author" class="form-label">Author</label>
            <input type="text" class="form-control" id="author" name="author" value="<?php echo htmlspecialchars($edit_application['author'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
            <label for="title" class="form-label">Title</label>
            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($edit_application['title'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
            <label for="review" class="form-label">Review</label>
            <textarea class="form-control" id="review" name="review" rows="5" required><?php echo htmlspecialchars($edit_application['review'] ?? ''); ?></textarea>
        </div>
        <div class="mb-3">
            <label for="image" class="form-label">Image</label>
            <input type="file" class="form-control" id="image" name="image" accept="image/*">
            <?php if ($edit_application && !empty($edit_application['image'])): ?>
                <p class="mt-2">Current Image: <img src="<?php echo htmlspecialchars($edit_application['image_dir'] . $edit_application['image']); ?>" class="img-thumbnail" alt="Current Image"></p>
            <?php endif; ?>
        </div>
        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status" required>
                <option value="active" <?php echo ($edit_application && $edit_application['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo ($edit_application && $edit_application['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo $edit_application ? 'Update Review' : 'Add Review'; ?></button>
        <?php if ($edit_application): ?>
            <a href="index.php" class="btn btn-secondary">Cancel Edit</a>
        <?php endif; ?>
    </form>
</div>

<h3 class="mt-4">All Application Reviews</h3>
<?php
$sql = "SELECT a.*, c.title as category_title FROM Applications a JOIN Categories c ON a.category_id = c.id ORDER BY a.created DESC";
$result = $conn->query($sql);
?>
<?php if ($result->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Category</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Status</th>
                    <th>Image</th>
                    <th>Created</th>
                    <th>Modified</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['category_title']); ?></td>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><?php echo htmlspecialchars($row['author']); ?></td>
                        <td>
                            <?php if ($row['status'] == 'active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($row['image'])): ?>
                                <img src="<?php echo htmlspecialchars($row['image_dir'] . $row['image']); ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;" alt="App Image">
                            <?php else: ?>
                                No Image
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatDateTime($row['created']); ?></td>
                        <td><?php echo formatDateTime($row['modified']); ?></td>
                        <td>
                            <a href="view_application.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">View</a>
                            <a href="applications.php?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                            <a href="applications.php?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this review?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p class="alert alert-warning">No application reviews found.</p>
<?php endif; ?>

