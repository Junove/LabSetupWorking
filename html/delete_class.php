<!-- /var/www/html/delete_class.php -->
<?php
if (isset($_GET['class_id'])) {
    $class_id = $_GET['class_id'];

    // Enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Connect to the database
    $conn = new mysqli('localhost', 'instructor', 'password', 'aws_instructor');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Delete the class from the classes table
    $stmt = $conn->prepare("DELETE FROM classes WHERE id = ?");
    $stmt->bind_param("i", $class_id);
    if ($stmt->execute()) {
        // Also delete the related AMIs from the amis table
        $stmt_ami = $conn->prepare("DELETE FROM amis WHERE class_id = ?");
        $stmt_ami->bind_param("i", $class_id);
        $stmt_ami->execute();

        echo "Class and associated AMIs deleted successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close connections
    $stmt->close();
    $stmt_ami->close();
    $conn->close();

    // Redirect back to the index page
    header("Location: index.php");
    exit();
} else {
    echo "Invalid request.";
}
?>
