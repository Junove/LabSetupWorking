<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Class</title>
</head>
<body>
    <?php include 'navigation.php'; ?>
    <h1>Add New Class</h1>
    <form action="add_class.php" method="post">
        <label for="class_name">Class Name:</label>
        <input type="text" id="class_name" name="class_name" required>
        <br><br>
        <label for="ami_details">AMIs, Default Name Tags, and Terraform Configs:</label>
        <div id="ami_details">
            <div>
                <input type="text" name="ami_ids[]" placeholder="AMI ID" required>
                <input type="text" name="ami_tags[]" placeholder="Default Name Tag" required>
                <input type="text" name="ami_terraform_configs[]" placeholder="Terraform Config Name" required>
            </div>
        </div>
        <button type="button" onclick="addAmiInput()">Add Another AMI</button>
        <br><br>
        <input type="submit" value="Add Class">
    </form>

    <script>
        function addAmiInput() {
            const div = document.createElement('div');
            div.innerHTML = `<input type="text" name="ami_ids[]" placeholder="AMI ID" required>
                             <input type="text" name="ami_tags[]" placeholder="Default Name Tag" required>
                             <input type="text" name="ami_terraform_configs[]" placeholder="Terraform Config Name" required>`;
            document.getElementById('ami_details').appendChild(div);
        }
    </script>
</body>
</html>

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $class_name = $_POST['class_name'];
    $ami_ids = $_POST['ami_ids'];
    $ami_tags = $_POST['ami_tags'];
    $ami_terraform_configs = $_POST['ami_terraform_configs'];

    // Enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Define the base path for classes
    $base_path = '/home/ubuntu/classes';

    // Connect to the database
    $conn = new mysqli('localhost', 'instructor', 'password', 'aws_instructor');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Insert the new class into the classes table
    $stmt = $conn->prepare("INSERT INTO classes (class_name) VALUES (?)");
    $stmt->bind_param("s", $class_name);
    if ($stmt->execute()) {
        $class_id = $stmt->insert_id;

        // Create directory structure for the class
        $class_dir = $base_path . '/' . $class_name;
        if (!mkdir($class_dir, 0777, true)) {
            die("Failed to create directory: $class_dir");
        }
        $terraform_subdir = $class_dir . '/terraform';
        if (!mkdir($terraform_subdir, 0777, true)) {
            die("Failed to create subdirectory: $terraform_subdir");
        }

        // Insert the AMIs into the amis table
        $stmt_ami = $conn->prepare("INSERT INTO amis (class_id, ami_id, ami_tag, terraform_config) VALUES (?, ?, ?, ?)");
        foreach ($ami_ids as $index => $ami_id) {
            $ami_tag = $ami_tags[$index];
            $ami_terraform_config = $ami_terraform_configs[$index];
            $stmt_ami->bind_param("isss", $class_id, $ami_id, $ami_tag, $ami_terraform_config);
            $stmt_ami->execute();

            // Copy the prebuilt Terraform config to the class directory
            //$source_config_path = '/path/to/prebuilt/terraform/' . $ami_terraform_config;
            //$dest_config_path = $terraform_subdir . '/' . $ami_terraform_config;
            //if (!copy($source_config_path, $dest_config_path)) {
            //    die("Failed to copy Terraform config: $source_config_path to $dest_config_path");
            //}
        }

        echo "Class and AMIs added successfully.";
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
