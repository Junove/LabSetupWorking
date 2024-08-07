<!-- /var/www/html/add_schedule.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Schedule Class</title>
</head>
<body>
    <?php include 'navigation.php'; ?>
    <h1>Schedule Class</h1>
    <form action="schedule.php" method="post">
        <label for="class">Select Class:</label>
        <select name="class_id" id="class_id">
            <?php
            $conn = new mysqli('localhost', 'instructor', 'password', 'aws_instructor');
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
            $result = $conn->query("SELECT id, class_name FROM classes");
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['class_name'], ENT_QUOTES, 'UTF-8') . "</option>";
                }
            } else {
                echo "<option value=''>No classes available</option>";
            }
            $conn->close();
            ?>
        </select>
        <br><br>
        <label for="num_students">Number of Students:</label>
        <input type="number" id="num_students" name="num_students" min="1" required>
        <br><br>
        <label for="start_date">Start Date:</label>
        <input type="date" id="start_date" name="start_date" required>
        <br><br>
        <label for="end_date">End Date:</label>
        <input type="date" id="end_date" name="end_date" required>
        <br><br>
        <label for="session_id">Session ID:</label>
        <input type="text" id="session_id" name="session_id" required>
        <br><br>
        <input type="submit" value="Schedule Class">
    </form>
</body>
</html>
