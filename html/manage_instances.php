<!-- /var/www/html/manage_instances.php -->
<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to the database
$conn = new mysqli('localhost', 'instructor', 'password', 'aws_instructor');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch classes that need instance creation
$today = date('Y-m-d');
$four_days_before = date('Y-m-d', strtotime('-4 days'));
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Create instances for instructors 4 days before class starts
$instructor_classes = $conn->query("SELECT * FROM schedule WHERE start_date = '$four_days_before'");
while ($schedule = $instructor_classes->fetch_assoc()) {
    create_instances($schedule['id'], $schedule['class_id'], $schedule['num_students'], true);
}

// Create instances for students on class start date
$student_classes = $conn->query("SELECT * FROM schedule WHERE start_date = '$today'");
while ($schedule = $student_classes->fetch_assoc()) {
    create_instances($schedule['id'], $schedule['class_id'], $schedule['num_students'], false);
}

// Destroy instances for classes that ended the day before
$ended_classes = $conn->query("SELECT * FROM schedule WHERE end_date = '$today'");
while ($schedule = $ended_classes->fetch_assoc()) {
    destroy_instances($schedule['id']);
}

// Function to create instances
function create_instances($schedule_id, $class_id, $num_students, $for_instructor) {
    global $conn;

    // Fetch AMIs for the class
    $ami_result = $conn->query("SELECT ami_id, ami_tag FROM amis WHERE class_id=$class_id");
    if (!$ami_result) {
        die("Error fetching AMI details: " . $conn->error);
    }
    $amis = [];
    while ($row = $ami_result->fetch_assoc()) {
        $amis[] = $row;
    }

    // Determine number of instances
    $num_instances = $for_instructor ? ($num_students + 1) : $num_students;

    // Create Terraform configuration
    $tf_config = "provider \"aws\" {
        region = \"us-west-2\"
    }\n\n";

    for ($i = 0; $i < $num_instances; $i++) {
        foreach ($amis as $index => $ami) {
            $ami_id = $ami['ami_id'];
            $ami_tag = "Student " . ($i + 1) . ": " . $ami['ami_tag'];
            $tf_config .= "resource \"aws_instance\" \"example_{$i}_{$index}\" {
                instance_type = \"t2.micro\"
                ami           = \"$ami_id\"
                tags = {
                    Name = \"$ami_tag\"
                }
            }\n\n";
        }
    }

    // Create a temporary directory for the Terraform configuration
    $tf_dir = "/tmp/terraform_" . uniqid();
    if (!mkdir($tf_dir)) {
        die("Failed to create temporary directory: $tf_dir");
    }

    // Write the Terraform configuration to a file
    $tf_file = $tf_dir . "/main.tf";
    if (file_put_contents($tf_file, $tf_config) === false) {
        die("Failed to write Terraform configuration to file: $tf_file");
    }

    // Change the working directory to the temporary directory
    if (!chdir($tf_dir)) {
        die("Failed to change directory to: $tf_dir");
    }

    // Execute Terraform commands
    exec("terraform init 2>&1", $init_output, $init_retval);
    if ($init_retval == 0) {
        exec("terraform apply -auto-approve 2>&1", $apply_output, $apply_retval);
        if ($apply_retval == 0) {
            // Record instances in instance_history
            foreach ($amis as $index => $ami) {
                $instance_id = "example_{$index}"; // Placeholder, replace with actual instance ID
                $stmt_history = $conn->prepare("INSERT INTO instance_history (schedule_id, ami_id, instance_id, creation_date) VALUES (?, ?, ?, ?)");
                $stmt_history->bind_param("isss", $schedule_id, $ami['ami_id'], $instance_id, $today);
                $stmt_history->execute();
            }
        } else {
            echo "Error applying Terraform configuration: " . implode("\n", $apply_output);
        }
    } else {
        echo "Error initializing Terraform: " . implode("\n", $init_output);
    }

    // Clean up
    array_map('unlink', glob("$tf_dir/*.*"));
    if (!rmdir($tf_dir)) {
        echo "Warning: Failed to remove directory $tf_dir";
    }
}

// Function to destroy instances
function destroy_instances($schedule_id) {
    global $conn;

    // Fetch instance history for the schedule
    $history_result = $conn->query("SELECT * FROM instance_history WHERE schedule_id=$schedule_id AND destruction_date IS NULL");
    if (!$history_result) {
        die("Error fetching instance history: " . $conn->error);
    }

    // Create Terraform configuration for destruction
    $tf_config = "provider \"aws\" {
        region = \"us-west-2\"
    }\n\n";

    while ($row = $history_result->fetch_assoc()) {
        $instance_id = $row['instance_id'];
        $tf_config .= "resource \"aws_instance\" \"$instance_id\" {
            instance_type = \"t2.micro\"
            ami           = \"{$row['ami_id']}\"
            tags = {
                Name = \"{$row['ami_id']}\"
            }
        }\n\n";
    }

    // Create a temporary directory for the Terraform configuration
    $tf_dir = "/tmp/terraform_" . uniqid();
    if (!mkdir($tf_dir)) {
        die("Failed to create temporary directory: $tf_dir");
    }

    // Write the Terraform configuration to a file
    $tf_file = $tf_dir . "/main.tf";
    if (file_put_contents($tf_file, $tf_config) === false) {
        die("Failed to write Terraform configuration to file: $tf_file");
    }

    // Change the working directory to the temporary directory
    if (!chdir($tf_dir)) {
        die("Failed to change directory to: $tf_dir");
    }

    // Execute Terraform commands
    exec("terraform init 2>&1", $init_output, $init_retval);
    if ($init_retval == 0) {
        exec("terraform destroy -auto-approve 2>&1", $destroy_output, $destroy_retval);
        if ($destroy_retval == 0) {
            // Update destruction_date in instance_history
            $conn->query("UPDATE instance_history SET destruction_date='$today' WHERE schedule_id=$schedule_id AND destruction_date IS NULL");
        } else {
            echo "Error destroying instances: " . implode("\n", $destroy_output);
        }
    } else {
        echo "Error initializing Terraform: " . implode("\n", $init_output);
    }

    // Clean up
    array_map('unlink', glob("$tf_dir/*.*"));
    if (!rmdir($tf_dir)) {
        echo "Warning: Failed to remove directory $tf_dir";
    }
}
?>
