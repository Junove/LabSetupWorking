<!-- /var/www/html/view_schedules.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Scheduled Classes</title>
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
    <h1>Scheduled Classes</h1>
    <table>
        <tr>
            <th>Class Name</th>
            <th>Number of Students</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Session ID</th>
        </tr>
        <?php
        $conn = new mysqli('localhost', 'instructor', 'password', 'aws_instructor');
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $sql = "SELECT schedule.class_id, classes.class_name, schedule.num_students, schedule.start_date, schedule.end_date, schedule.session_id 
                FROM schedule 
                JOIN classes ON schedule.class_id = classes.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . htmlspecialchars($row['class_name'], ENT_QUOTES, 'UTF-8') . "</td>
                        <td>" . htmlspecialchars($row['num_students'], ENT_QUOTES, 'UTF-8') . "</td>
                        <td>" . htmlspecialchars($row['start_date'], ENT_QUOTES, 'UTF-8') . "</td>
                        <td>" . htmlspecialchars($row['end_date'], ENT_QUOTES, 'UTF-8') . "</td>
                        <td>" . htmlspecialchars($row['session_id'], ENT_QUOTES, 'UTF-8') . "</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='5'>No scheduled classes found</td></tr>";
        }
        $conn->close();
        ?>
    </table>
</body>
</html>
