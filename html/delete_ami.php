<!-- /var/www/html/delete_ami.php -->
<?php
if (isset($_GET['ami_id']) && isset($_GET['class_id'])) {
    $ami_id = $_GET['ami_id'];
    $class_id = $_GET['class_id'];

    // Enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Connect to the database
    $conn = new mysqli('localhost', 'instructor', 'password', 'aws_instructor');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Delete the AMI from the amis table
    $stmt = $conn->prepare("DELETE FROM amis WHERE id = ? AND class_id = ?");
    $stmt->bind_param("ii", $ami_id, $class_id);
    if ($stmt->execute()) {
        echo "AMI deleted successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close connections
    $stmt->close();
    $conn->close();

    // Redirect back to the edit class page
    header("Location: edit_class.php?class_id=$class_id");
    exit();
} else {
    echo "Invalid request.";
}
?>
