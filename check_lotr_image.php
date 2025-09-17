<?php
require_once 'config.php';

echo "<h1>Lord of the Rings Book Check</h1>";

// Check for Lord of the Rings book
$result = $conn->query("SELECT title, cover_image FROM books WHERE title LIKE '%Lord of the Rings%' OR title LIKE '%lord%' OR title LIKE '%rings%'");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<p><strong>Title:</strong> " . htmlspecialchars($row['title']) . "</p>";
        echo "<p><strong>Cover Image:</strong> " . htmlspecialchars($row['cover_image']) . "</p>";
        
        // Check if file exists
        $image_path = 'uploads/covers/' . $row['cover_image'];
        if (file_exists($image_path)) {
            echo "<p style='color: green;'>✅ Image file exists: $image_path</p>";
            echo "<img src='$image_path' style='max-width: 200px; border: 1px solid #ccc;'>";
        } else {
            echo "<p style='color: red;'>❌ Image file NOT found: $image_path</p>";
        }
        echo "<hr>";
    }
} else {
    echo "<p style='color: red;'>No Lord of the Rings book found in database</p>";
}

// Show all books for reference
echo "<h2>All Books in Database:</h2>";
$all_books = $conn->query("SELECT title, cover_image FROM books ORDER BY title");
if ($all_books && $all_books->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Title</th><th>Cover Image</th></tr>";
    while ($book = $all_books->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($book['title']) . "</td>";
        echo "<td>" . htmlspecialchars($book['cover_image']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?> 