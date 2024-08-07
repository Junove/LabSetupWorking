<!-- /var/www/html/update_class.php -->
<?php include 'navigation.php'; ?>
<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $class_id = $_POST['class_id'];
    $class_name = $_POST['class_name'];
    $num_instances = $_POST['num_instances'];
    $terraform_config_path = $_POST['terraform_config_path'];
    $ami_ids = $_POST['ami_ids'];
    $ami_tags = $_POST['ami_tags'];

    // Enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Connect to the database
    $conn = new mysqli('localhost', 'instructor', 'password', 'aws_instructor');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Update class details in the classes table
    $stmt = $conn->prepare("UPDATE classes SET class_name = ?, num_instances = ?, terraform_config_path = ? WHERE id = ?");
    $stmt->bind_param("sisi", $class_name, $num_instances, $terraform_config_path, $class_id);
    if ($stmt->execute()) {
        // Update AMI details in the amis table
        $stmt_ami = $conn->prepare("REPLACE INTO amis (class_id, ami_id, ami_tag) VALUES (?, ?, ?)");
        foreach ($ami_ids as $index => $ami_id) {
            $ami_tag = $ami_tags[$index];
            $stmt_ami->bind_param("iss", $class_id, $ami_id, $ami_tag);
            $stmt_ami->execute();
        }

        echo "Class and AMIs updated successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close connections
    $stmt->close();
    $stmt_ami->close();
    $conn->close();
} else {
    echo "Invalid request.";
}
?>
<br><br>
<a href="index.php"><button>Back to Home</button></a>
<a href="edit_class.php"><button>Edit Another Class</button></a>
