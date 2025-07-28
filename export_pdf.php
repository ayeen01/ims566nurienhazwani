<?php
// File: export_pdf.php
// Description: Exports an application review to PDF using FPDF.
// Make sure to download FPDF from http://www.fpdf.org/ and place fpdf.php in the same directory.

require('fpdf.php'); // Make sure fpdf.php is in the same directory
include 'db_connect.php'; // Ensure this file correctly establishes $conn

// --- IMPORTANT: Start output buffering to catch any accidental output ---
// This can help prevent the FPDF error if a stray warning or whitespace slips through
ob_start();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Use exit() instead of die() when ob_start() is used, or flush and then die
    ob_end_clean(); // Clean any buffered output before dying
    die("Application ID not provided.");
}

$app_id = $conn->real_escape_string($_GET['id']);

// Fetch application details
// Ensure your 'Applications' table has 'created', 'modified', 'image', 'image_dir' columns as per ERD
$app_sql = "SELECT a.*, c.title as category_title
            FROM Applications a
            JOIN Categories c ON a.category_id = c.id
            WHERE a.id = $app_id";
$app_result = $conn->query($app_sql);

if ($app_result === false) {
    ob_end_clean();
    die("Error fetching application details: " . $conn->error);
}

if ($app_result->num_rows == 0) {
    ob_end_clean();
    die("Application review not found.");
}

$application = $app_result->fetch_assoc();

// Fetch comments for this application
$comments_sql = "SELECT * FROM Comments WHERE application_id = $app_id ORDER BY created ASC";
$comments_result = $conn->query($comments_sql);

if ($comments_result === false) {
    ob_end_clean();
    die("Error fetching comments: " . $conn->error);
}

class PDF extends FPDF
{
    // Page header
    function Header()
    {
        // Logo - Uncomment and set your logo if needed.
        // Example: $this->Image('path/to/your/logo.png', 10, 8, 33);

        $this->SetFont('Arial', 'B', 15);
        $this->Cell(80);
        $this->Cell(30, 10, 'Application Review Report', 0, 0, 'C');
        $this->Ln(20);
    }

    // Page footer
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // Table for comments
    function CommentsTable($header, $data)
    {
        $this->SetFillColor(255, 192, 203); // Light Pink
        $this->SetTextColor(0);
        $this->SetDrawColor(128, 0, 0); // Dark Red/Maroon for borders
        $this->SetLineWidth(.3);
        $this->SetFont('', 'B');

        // Header
        $w = array(50, 90, 20, 30); // Adjusted widths for Name, Comment, Rating, Date
        for ($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
        }
        $this->Ln();

        // Color and font restoration
        $this->SetFillColor(255, 240, 245); // Lighter Pink for data rows
        $this->SetTextColor(0);
        $this->SetFont('');

        // Data
        $fill = false; // For alternating row colors
        foreach ($data as $row) {
            // *** FIX: Changed 'reviewer_name' to 'name' based on ERD/SQL schema ***
            $this->Cell($w[0], 6, $row['name'], 'LR', 0, 'L', $fill);
            // MultiCell for comment to handle long text
            $x = $this->GetX();
            $y = $this->GetY();
            // *** FIX: Changed 'comment_text' to 'comment' based on ERD/SQL schema ***
            $this->MultiCell($w[1], 6, $row['comment'], 'LR', 'L', $fill);
            $new_y = $this->GetY();
            $this->SetXY($x + $w[1], $y); // Move cursor back after MultiCell
            $this->Cell($w[2], 6, $row['rating'], 'LR', 0, 'C', $fill);
            $this->Cell($w[3], 6, date('Y-m-d H:i', strtotime($row['created'])), 'LR', 0, 'C', $fill);
            $this->Ln();
            $fill = !$fill; // Alternate fill color
        }
        // Closing line
        $this->Cell(array_sum($w), 0, '', 'T'); // Top border for the last row
    }
}

// Instantiate and configure PDF
$pdf = new PDF();
$pdf->AliasNbPages(); // For {nb} page numbering
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);
$pdf->SetAutoPageBreak(true, 15); // Enable auto page break with a bottom margin

// --- Application Image (if available) ---
// Construct the full image path from 'image_dir' and 'image'
$full_image_path = '';
if (!empty($application['image_dir']) && !empty($application['image'])) {
    $full_image_path = rtrim($application['image_dir'], '/') . '/' . $application['image'];
}

if (!empty($full_image_path) && file_exists($full_image_path)) {
    $image_info = getimagesize($full_image_path);
    if ($image_info) {
        $pdf->Cell(0, 10, 'Application Image:', 0, 1, 'L');
        $pdf->Image($full_image_path, $pdf->GetX(), $pdf->GetY(), 100);
        $pdf->Ln(105);
    } else {
        $pdf->Cell(0, 10, 'Image file found but not a supported image type or corrupted.', 0, 1, 'L');
        $pdf->Ln(10);
    }
} else if (!empty($full_image_path)) {
    $pdf->Cell(0, 10, 'Application image not found at specified path: ' . htmlspecialchars($full_image_path), 0, 1, 'L');
    $pdf->Ln(10);
} else {
    $pdf->Cell(0, 10, 'No application image provided.', 0, 1, 'L');
    $pdf->Ln(10);
}

// --- Application Details ---
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(128, 0, 0); // Dark Red
$pdf->Cell(0, 10, 'Application Details:', 0, 1, 'L');
$pdf->SetTextColor(0); // Reset to black

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 7, 'Title: ' . $application['title'], 0, 1);
$pdf->Cell(0, 7, 'Category: ' . $application['category_title'], 0, 1);
// *** FIX: Changed 'description' to 'review' based on ERD/SQL schema ***
$pdf->MultiCell(0, 7, 'Review: ' . $application['review']);
// *** FIX: Changed 'is_active' to 'status' based on ERD/SQL schema ***
$pdf->Cell(0, 7, 'Status: ' . ucfirst($application['status']), 0, 1); // ucfirst() to capitalize 'active'/'inactive'
// *** FIX: Changed 'created_at' to 'created' based on ERD/SQL schema ***
$pdf->Cell(0, 7, 'Created At: ' . date('Y-m-d H:i:s', strtotime($application['created'])), 0, 1);
// *** FIX: Changed 'updated_at' to 'modified' based on ERD/SQL schema ***
$pdf->Cell(0, 7, 'Last Modified: ' . date('Y-m-d H:i:s', strtotime($application['modified'])), 0, 1);
$pdf->Ln(10); // Add some space

// --- Comments Section ---
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(128, 0, 0); // Dark Red
$pdf->Cell(0, 10, 'Comments:', 0, 1, 'L');
$pdf->SetTextColor(0); // Reset to black

if ($comments_result->num_rows > 0) {
    $header = array('Reviewer Name', 'Comment', 'Rating', 'Date');
    $comment_data = [];
    while ($row = $comments_result->fetch_assoc()) {
        $comment_data[] = $row;
    }
    $pdf->CommentsTable($header, $comment_data);
} else {
    $pdf->SetFont('Arial', 'I', 12);
    $pdf->Cell(0, 10, 'No comments available for this application.', 0, 1, 'L');
}

// --- IMPORTANT: Clear the output buffer before sending PDF ---
ob_end_clean();

// Output PDF
$pdf->Output('I', 'Application_Review_' . $application['id'] . '.pdf');

// Close database connection
$conn->close();
?>