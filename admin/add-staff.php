<?php include __DIR__ . '/databases/add-staff-process.php'; ?>

<div class="dashboard-container">

    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">

        <div class="page-heading">
            <h1>Add Staff Member</h1>
            <p>Register a new staff member in the system.</p>
        </div>

        <?php if ($error !== ''): ?>

            <div class="form-message error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <?php if ($success !== ''): ?>

            <div class="form-message success-message">
                <?php echo htmlspecialchars($success); ?>
            </div>

        <?php endif; ?>

        <section class="content-card">

            <div class="card-heading">
                <h3>Staff Information</h3>
            </div>

            <form method="POST" action="" class="staff-form-grid">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
                >

                <div class="form-group">

                    <label for="first_name">First Name</label>

                    <input
                        type="text"
                        name="first_name"
                        id="first_name"
                        maxlength="100"
                        placeholder="Enter first name"
                        value="<?php echo htmlspecialchars($first_name); ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="last_name">Last Name</label>

                    <input
                        type="text"
                        name="last_name"
                        id="last_name"
                        maxlength="100"
                        placeholder="Enter last name"
                        value="<?php echo htmlspecialchars($last_name); ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="professional_role">Professional Role</label>

                    <select
                        name="professional_role"
                        id="professional_role"
                        required
                    >

                        <option value="">Select Professional Role</option>

                        <option
                            value="Doctor"
                            <?php echo $professional_role === 'Doctor' ? 'selected' : ''; ?>
                        >
                            Doctor
                        </option>

                        <option
                            value="Nurse"
                            <?php echo $professional_role === 'Nurse' ? 'selected' : ''; ?>
                        >
                            Nurse
                        </option>

                         <option
                            value="Receptionist"
                            <?php echo $professional_role === 'Receptionist' ? 'selected' : ''; ?>
                        >
                            Receptionist
                        </option>

                    </select>

                </div>

                <div class="form-group">

                    <label for="department">Department</label>

                    <input
                        type="text"
                        name="department"
                        id="department"
                        maxlength="100"
                        placeholder="Enter department"
                        value="<?php echo htmlspecialchars($department); ?>"
                    >

                </div>

                <div class="form-group">

                    <label for="specialization">Specialization</label>

                    <input
                        type="text"
                        name="specialization"
                        id="specialization"
                        maxlength="150"
                        placeholder="Enter specialization (if applicable)"
                        value="<?php echo htmlspecialchars($specialization); ?>"
                    >

                </div>

                <div class="form-group">

                    <label for="contact_number">Contact Number</label>

                    <input
                        type="tel"
                        name="contact_number"
                        id="contact_number"
                        maxlength="20"
                        placeholder="Enter contact number"
                        value="<?php echo htmlspecialchars($contact_number); ?>"
                    >

                </div>

                <div class="form-group form-group-full-width">

                    <label for="address">Address</label>

                    <textarea
                        name="address"
                        id="address"
                        rows="4"
                        placeholder="Enter residential address"
                    ><?php echo htmlspecialchars($address); ?></textarea>

                </div>

                <div class="form-actions">

                    <button type="submit" class="primary-btn">
                        Register Staff
                    </button>

                    <a href="staff.php" class="cancel-btn">
                        Cancel
                    </a>

                </div>

            </form>

        </section>

    </main>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
