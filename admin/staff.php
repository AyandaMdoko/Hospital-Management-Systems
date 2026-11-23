
<?php

require_once __DIR__ . '/includes/admin-auth.php';
require_once __DIR__ . "/../config/dbconnect.php";

// Create a CSRF token for the staff status change form.
if (empty($_SESSION['staff_status_csrf'])) {
    $_SESSION['staff_status_csrf'] = bin2hex(random_bytes(32));
}

$statusSuccess = $_SESSION['staff_status_success'] ?? '';
$statusError = $_SESSION['staff_status_error'] ?? '';

unset(
    $_SESSION['staff_status_success'],
    $_SESSION['staff_status_error']
);

// Retrieve all registered staff members.
$stmt = $pdo->prepare("
    SELECT
        staff_id,
        first_name,
        last_name,
        professional_role,
        staff_status,
        department,
        specialization,
        contact_number,
        created_at
    FROM staff
    ORDER BY created_at DESC
");

$stmt->execute();

$staff_members = $stmt->fetchAll();

?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="dashboard-container">

    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">

        <!-- Page heading -->
        <div class="page-heading">
            <h1>Staff Management</h1>
            <p>View and manage registered doctors and nurses.</p>
        </div>

        <!-- Display success or error messages -->
        <?php if ($statusSuccess): ?>
            <div class="form-message success-message">
                <?= htmlspecialchars($statusSuccess, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($statusError): ?>
            <div class="form-message error-message">
                <?= htmlspecialchars($statusError, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <!-- Staff list -->
        <section class="content-card">

            <div class="card-heading">

                <h3>Registered Staff Members</h3>

                <a href="add-staff.php" class="primary-btn" style="color: #fff;">
                    + Add Staff
                </a>

            </div>

            <div class="table-container">

                <table class="dashboard-table">

                    <thead>
                        <tr>
                            <th>Staff ID</th>
                            <th>Full Name</th>
                            <th>Professional Role</th>
                            <th>Department</th>
                            <th>Specialization</th>
                            <th>Contact Number</th>
                            <th>Date Registered</th>
                            <th>Status</th>
                            <th>Actions</th>
                            
                        </tr>
                    </thead>

                    <tbody>

                        <!-- Display staff members if available -->
                        <?php if (count($staff_members) > 0): ?>

                            <?php foreach ($staff_members as $staff): ?>

                                <tr>

                                    <td>
                                        <?php echo htmlspecialchars($staff['staff_id']); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars(
                                            $staff['first_name'] . ' ' .
                                            $staff['last_name']
                                        ); ?>
                                    </td>

                                    <td>
                                        <span class="status-badge <?php echo strtolower($staff['professional_role']); ?>">
                                            <?php echo htmlspecialchars($staff['professional_role']); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars(
                                            $staff['department'] ?? 'N/A'
                                        ); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars(
                                            $staff['specialization'] ?? 'N/A'
                                        ); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars(
                                            $staff['contact_number'] ?? 'N/A'
                                        ); ?>
                                    </td>

                                    <td>
                                        <?php echo date(
                                            'd M Y',
                                            strtotime($staff['created_at'])
                                        ); ?>
                                    </td>

                                     <td>
                                        <span class="status-badge <?= strtolower($staff['staff_status']) ?>">
                                            <?= htmlspecialchars($staff['staff_status'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>

                                    <td>
                                        <a
                                            href="edit-staff.php?id=<?= (int)$staff['staff_id'] ?>"
                                            class="primary-btn"
                                            >
                                                Edit
                                        </a>

                                            <?php
                                            $isActive = $staff['staff_status'] === 'Active';

                                            $nextStatus = $isActive ? 'Inactive' : 'Active';

                                            $buttonLabel = $isActive ? 'Deactivate' : 'Activate';

                                            $buttonClass = $isActive
                                                ? 'deactivate-btn'
                                                : 'activate-btn';
                                            ?>

                                            <form
                                                action="databases/change-staff-status.php"
                                                method="POST"
                                                class="staff-status-form"
                                                onsubmit="return confirm('Are you sure you want to <?= strtolower($buttonLabel) ?> this staff member?');"
                                                >
                                                
                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= htmlspecialchars($_SESSION['staff_status_csrf'], ENT_QUOTES, 'UTF-8') ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="staff_id"
                                                    value="<?= (int)$staff['staff_id'] ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="new_status"
                                                    value="<?= $nextStatus ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="<?= $buttonClass ?>"
                                                >
                                                    <?= $buttonLabel ?>
                                                </button>
                                            </form>
                                        </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="8" style="text-align: center; padding: 25px;">
                                    No staff members have been registered yet.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
