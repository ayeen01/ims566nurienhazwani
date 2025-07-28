<?php
// File: index.php
// Description: Displays active and inactive application reviews, with search/filter functionality.

include 'db_connect.php';


// Search and Filter Logic
$search_query = "";
$status_filter = "";
$category_filter = "";

if (isset($_GET['search'])) {
    $search_query = $conn->real_escape_string($_GET['search']);
}
if (isset($_GET['status'])) {
    $status_filter = $conn->real_escape_string($_GET['status']);
}
if (isset($_GET['category_id'])) {
    $category_filter = $conn->real_escape_string($_GET['category_id']);
}

$sql = "SELECT a.*, c.title as category_title FROM Applications a JOIN Categories c ON a.category_id = c.id WHERE 1=1";

if (!empty($search_query)) {
    $sql .= " AND (a.title LIKE '%$search_query%' OR a.author LIKE '%$search_query%' OR a.review LIKE '%$search_query%')";
}
if (!empty($status_filter)) {
    $sql .= " AND a.status = '$status_filter'";
}
if (!empty($category_filter)) {
    $sql .= " AND a.category_id = '$category_filter'";
}

$result = $conn->query($sql);

// Fetch categories for filter dropdown
$categories_result = $conn->query("SELECT * FROM Categories ORDER BY title ASC");
?>

<h2 class="my-4">Application Reviews</h2>

<div class="card p-4 mb-4">
    <form class="row g-3 align-items-end" method="GET" action="index.php">
        <div class="col-md-4">
            <label for="search" class="form-label">Search</label>
            <input type="text" class="form-control" id="search" name="search" placeholder="Search by title, author, review..." value="<?php echo htmlspecialchars($search_query); ?>">
        </div>
        <div class="col-md-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">All</option>
                <option value="active" <?php echo ($status_filter == 'active') ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo ($status_filter == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="category_id" class="form-label">Category</label>
            <select class="form-select" id="category_id" name="category_id">
                <option value="">All</option>
                <?php while ($category = $categories_result->fetch_assoc()): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo ($category_filter == $category['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['title']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>
</div>

<a href="applications.php?action=create" class="btn btn-success mb-3">Add New Application Review</a>

<?php if ($result->num_rows > 0): ?>
    <div class="row">
        <?php
        $active_reviews = [];
        $inactive_reviews = [];

        while($row = $result->fetch_assoc()) {
            if ($row['status'] == 'active') {
                $active_reviews[] = $row;
            } else {
                $inactive_reviews[] = $row;
            }
        }
        ?>

        <?php if (!empty($active_reviews)): ?>
            <h3 class="mt-4">Active Reviews</h3>
            <?php foreach ($active_reviews as $app): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card mb-4">
                        <div class="card-body">
                            <?php if (!empty($app['image'])): ?>
                                <img src="<?php echo htmlspecialchars($app['image_dir'] . $app['image']); ?>" class="img-fluid mb-3 rounded img-thumbnail" alt="App Image">
                            <?php else: ?>
                                <img src="https://placehold.co/150x150/ffc0cb/000000?text=No+Image" class="img-fluid mb-3 rounded img-thumbnail" alt="No Image">
                            <?php endif; ?>
                            <h5 class="card-title"><?php echo htmlspecialchars($app['title']); ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted">By: <?php echo htmlspecialchars($app['author']); ?></h6>
                            <p class="card-text">Category: <?php echo htmlspecialchars($app['category_title']); ?></p>
                            <p class="card-text"><small class="text-muted">Posted: <?php echo formatDateTime($app['posted_date']); ?></small></p>
                            <p class="card-text"><small class="text-muted">Created: <?php echo formatDateTime($app['created']); ?></small></p>
                            <p class="card-text"><small class="text-muted">Modified: <?php echo formatDateTime($app['modified']); ?></small></p>
                            <a href="view_application.php?id=<?php echo $app['id']; ?>" class="btn btn-info btn-sm">View Details</a>
                            <a href="applications.php?action=edit&id=<?php echo $app['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                            <a href="applications.php?action=delete&id=<?php echo $app['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this review?');">Delete</a>
                            <a href="export_pdf.php?id=<?php echo $app['id']; ?>" class="btn btn-secondary btn-sm mt-2">Export PDF</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="mt-4 alert alert-info">No active application reviews found.</p>
        <?php endif; ?>

        <?php if (!empty($inactive_reviews)): ?>
            <h3 class="mt-4">Inactive Reviews</h3>
            <?php foreach ($inactive_reviews as $app): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card mb-4 bg-light">
                        <div class="card-body">
                            <?php if (!empty($app['image'])): ?>
                                <img src="<?php echo htmlspecialchars($app['image_dir'] . $app['image']); ?>" class="img-fluid mb-3 rounded img-thumbnail" alt="App Image">
                            <?php else: ?>
                                <img src="https://placehold.co/150x150/ffc0cb/000000?text=No+Image" class="img-fluid mb-3 rounded img-thumbnail" alt="No Image">
                            <?php endif; ?>
                            <h5 class="card-title text-muted"><?php echo htmlspecialchars($app['title']); ?> (Inactive)</h5>
                            <h6 class="card-subtitle mb-2 text-muted">By: <?php echo htmlspecialchars($app['author']); ?></h6>
                            <p class="card-text text-muted">Category: <?php echo htmlspecialchars($app['category_title']); ?></p>
                            <p class="card-text"><small class="text-muted">Posted: <?php echo formatDateTime($app['posted_date']); ?></small></p>
                            <p class="card-text"><small class="text-muted">Created: <?php echo formatDateTime($app['created']); ?></small></p>
                            <p class="card-text"><small class="text-muted">Modified: <?php echo formatDateTime($app['modified']); ?></small></p>
                            <a href="view_application.php?id=<?php echo $app['id']; ?>" class="btn btn-info btn-sm">View Details</a>
                            <a href="applications.php?action=edit&id=<?php echo $app['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                            <a href="applications.php?action=delete&id=<?php echo $app['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this review?');">Delete</a>
                            <a href="export_pdf.php?id=<?php echo $app['id']; ?>" class="btn btn-secondary btn-sm mt-2">Export PDF</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="mt-4 alert alert-info">No inactive application reviews found.</p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <p class="alert alert-warning">No application reviews found. <a href="applications.php?action=create">Add one now!</a></p>
<?php endif; ?>

