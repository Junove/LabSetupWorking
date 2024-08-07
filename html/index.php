<!-- /var/www/html/index.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AWS EC2 Instance Provisioner</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 15px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <?php include 'navigation.php'; ?>
    <h1>AWS EC2 Instance Provisioner</h1>

    <h2>Existing Classes</h2>
    <table>
        <tr>
            <th>Class Name</th>
            <th>Number of Instances</th>
            <th>Terraform Config Path</th>
            <th>Actions</th>
        </tr>
        <?php
        $conn = new mysqli('localhost', 'instructor', 'password', 'aws_instructor');
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $result = $conn->query("SELECT id, class_name, num_instances, terraform_config_path FROM classes");
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . htmlspecialchars($row['class_name'], ENT_QUOTES, 'UTF-8') . "</td>
                        <td>" . htmlspecialchars($row['num_instances'], ENT_QUOTES, 'UTF-8') . "</td>
                        <td>" . htmlspecialchars($row['terraform_config_path'], ENT_QUOTES, 'UTF-8') . "</td>
                        <td>
                            <a href='edit_class.php?class_id=" . $row['id'] . "'><button>Edit</button></a>
                            <a href='delete_class.php?class_id=" . $row['id'] . "' onclick=\"return confirm('Are you sure you want to delete this class?');\"><button>Delete</button></a>
                        </td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='4'>No classes found</td></tr>";
        }
        $conn->close();
        ?>
    </table>
    <br>
    <a href="add_class.html"><button>Add New Class</button></a>

    <h2>Provision AWS EC2 Instances</h2>
    <form action="provision.php" method="post">
        <label for="class">Select Class:</label>
        <select name="class" id="class">
            <?php
            $conn = new mysqli('localhost', 'instructor', 'password', 'aws_instructor');
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
            $result = $conn->query("SELECT id, class_name FROM classes");
            while ($row = $result->fetch_assoc()) {
                echo "<option value='{$row['id']}'>" . htmlspecialchars($row['class_name'], ENT_QUOTES, 'UTF-8') . "</option>";
            }
            $conn->close();
            ?>
        </select>
        <br><br>
        <label for="students">Number of Students:</label>
        <input type="number" id="students" name="students" min="1" required>
        <br><br>
        <input type="submit" value="Provision Instances">
    </form>
</body>
</html>
