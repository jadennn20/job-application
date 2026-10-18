<?php
require_once "db.php";

// CREATE or UPDATE
if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["save"])) {

    $id = $_POST["id"] ?? "";
    $first = trim($_POST["first_name"] ?? "");
    $last = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $education = trim($_POST["education"] ?? "");
    $skills = trim($_POST["technical_skills"] ?? "");
    $experience = $_POST["years_experience"] ?? "0";

    if (
        $first === "" || $last === "" || $email === ""
        || $phone === "" || $education === "" || $skills === ""
        || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || !ctype_digit((string)$experience)
    ) {
        exit("Invalid input. Check the required fields and try again.");
    }

    $experience = (int)$experience;

    if ($experience > 100) {
        exit("Please enter a valid number of experience years.");
    }

    if ($id !== "") {
        // UPDATE existing application
        $sql = "UPDATE applications SET
        first_name = ?, last_name = ?, email = ?,
        phone = ?, education = ?, technical_skills = ?,
        years_experience = ?
        WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
    $first, $last, $email, $phone, $education,
    $skills, $experience, (int)$id
]);
    } else {
        $sql = "INSERT INTO applications
        (first_name, last_name, email, phone,
         education, technical_skills, years_experience)
        VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
    $first, $last, $email, $phone, $education,
    $skills, $experience
]);
    }

    header("Location: index.php");
    exit;
}

// DELETE application
if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["delete_id"])) {

    $stmt = $pdo->prepare(
        "DELETE FROM applications WHERE id = ?"
    );
    $stmt->execute([(int)$_POST["delete_id"]]);

    header("Location: index.php");
    exit;
}

// READ: get all applications
$stmt = $pdo->query(
    "SELECT * FROM applications ORDER BY id DESC"
);
$applications = $stmt->fetchAll();

// Load selected application for editing
$edit = null;

if (isset($_GET["edit"]) && ctype_digit($_GET["edit"])) {
    $stmt = $pdo->prepare(
        "SELECT * FROM applications WHERE id = ?"
    );
    $stmt->execute([(int)$_GET["edit"]]);
    $edit = $stmt->fetch();
}

// Safely display database values in HTML
function e($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Software Engineer Job Applications</title>

    <style>
        * { box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 25px;
            color: #1e293b;
        }

        .container {
            max-width: 1200px;
            margin: auto;
        }

        h1, h2 { color: #1d4ed8; }

        .panel {
            background: white;
            padding: 22px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px #0000000d;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 6px;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            font: inherit;
        }

        textarea { resize: vertical; }

        button, .btn {
            display: inline-block;
            border: 0;
            border-radius: 5px;
            padding: 10px 14px;
            cursor: pointer;
            text-decoration: none;
            font: inherit;
        }

        .save { background: #2563eb; color: white; }
        .edit { background: #f59e0b; color: #111827; }
        .delete { background: #dc2626; color: white; }
        .cancel { background: #e2e8f0; color: #1e293b; }

        .table-wrap { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
        }

        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        th { background: #eff6ff; }

        td.skills {
            white-space: pre-wrap;
            min-width: 180px;
        }

        .actions {
            display: flex;
            gap: 7px;
            align-items: center;
        }

        .actions form { margin: 0; }

        @media (max-width: 650px) {
            body { padding: 12px; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>
<div class="container">

    <h1>Software Engineer Job Application System</h1>
    <p>Submit and Manage Software Engineer Applications.</p>

    <section class="panel">
        <h2><?= $edit ? "Update Application" : "Application Form" ?></h2>

        <form method="POST" action="index.php">
            <input type="hidden" name="id"
                   value="<?= e($edit["id"] ?? "") ?>">

            <div class="form-grid">
                <div>
                    <label for="first_name">First Name *</label>
                    <input id="first_name" name="first_name"
                        value="<?= e($edit["first_name"] ?? "") ?>"
                        maxlength="100" required>
                </div>

                <div>
                    <label for="last_name">Last Name *</label>
                    <input id="last_name" name="last_name"
                        value="<?= e($edit["last_name"] ?? "") ?>"
                        maxlength="100" required>
                </div>

                <div>
                    <label for="email">Email *</label>
                    <input id="email" name="email" type="email"
                        value="<?= e($edit["email"] ?? "") ?>"
                        maxlength="150" required>
                </div>

                <div>
                    <label for="phone">Phone *</label>
                    <input id="phone" name="phone"
                        value="<?= e($edit["phone"] ?? "") ?>"
                        maxlength="30" required>
                </div>

                <div>
                    <label for="education">Educational Attainment *</label>
                    <select id="education" name="education" required>
                        <option value="">Select education</option>
                        <?php
                        $levels = [
                            "Senior High School",
                            "Associate Degree",
                            "Bachelor's Degree",
                            "Master's Degree",
                            "Doctorate"
                        ];
                        foreach ($levels as $level):
                        ?>
                            <option value="<?= e($level) ?>"
                                <?= (($edit["education"] ?? "") === $level)
                                    ? "selected" : "" ?>>
                                <?= e($level) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="years_experience">
                        Years of Experience *
                    </label>
                    <input id="years_experience"
                        name="years_experience" type="number"
                        min="0" max="100" step="1"
                        value="<?= e($edit["years_experience"] ?? "0") ?>"
                        required>
                </div>

                <div>
                    <label for="technical_skills">
                        Technical Skills *
                    </label>
                    <textarea id="technical_skills"
                        name="technical_skills" rows="3"
                        required><?= e($edit["technical_skills"] ?? "") ?></textarea>
                </div>
            </div>

            <p>
                <button class="save" type="submit" name="save">
                    <?= $edit ? "Update Application" : "Submit Application" ?>
                </button>

                <?php if ($edit): ?>
                    <a class="btn cancel" href="index.php">Cancel</a>
                <?php endif; ?>
            </p>
        </form>
    </section>

    <section class="panel">
        <h2>All Applications (<?= count($applications) ?>)</h2>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Education</th>
                    <th>Technical Skills</th>
                    <th>Experience</th>
                    <th>Date Added</th>
                    <th>Actions</th>
                </tr>
                </thead>

                <tbody>
                <?php if (!$applications): ?>
                    <tr>
                        <td colspan="11">No applications found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><?= e($app["id"]) ?></td>
                            <td><?= e($app["first_name"]) ?></td>
                            <td><?= e($app["last_name"]) ?></td>
                            <td><?= e($app["email"]) ?></td>
                            <td><?= e($app["phone"]) ?></td>
                            <td><?= e($app["education"]) ?></td>
                            <td class="skills"><?= e($app["technical_skills"]) ?></td>
                            <td><?= e($app["years_experience"]) ?> year(s)</td>
                            <td>
                                <?php if (!empty($app["portfolio_url"])): ?>
                                    <a href="<?= e($app["portfolio_url"]) ?>"
                                       target="_blank"
                                       rel="noopener noreferrer">
                                        View portfolio
                                    </a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><?= e($app["date_added"]) ?></td>
                            <td>
                                <div class="actions">
                                    <a class="btn edit"
                                       href="index.php?edit=<?= e($app["id"]) ?>">
                                        Edit
                                    </a>

                                    <form method="POST" action="index.php"
                                        onsubmit="return confirm('Delete this application?');">
                                        <input type="hidden" name="delete_id"
                                            value="<?= e($app["id"]) ?>">
                                        <button class="delete" type="submit">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>
</body>
</html>
