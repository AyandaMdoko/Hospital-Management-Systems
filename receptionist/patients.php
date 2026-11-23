<?php include __DIR__ . '/databases/patients-process.php'; ?>



<main class="main-content">

    <!-- PAGE HEADING -->
    <div class="page-heading">
        <h1>Patient Management</h1>
        <p>
            Search, register and manage patient
            demographic information.
        </p>
    </div>

    
    <?php if ($registrationSuccess): ?>

        <div class="form-message success-message">
            <?= htmlspecialchars(
                $registrationSuccess,
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </div>

    <?php endif; ?>


    <!-- SUCCESS MESSAGE -->
    <section class="content-card">

        <div class="card-heading patient-card-heading">

            <div>
                <h3>Registered Patients</h3>

                <p class="patient-count">
                    <?= $totalPatients ?>
                    <?= $totalPatients === 1
                        ? 'patient'
                        : 'patients' ?>
                    found
                </p>
              </br>

                <a href="add-patient.php" class="primary-btn" style="color: #fff;">
                    + Register Patient
                </a>
            </div>

            <form
                action="patients.php"
                method="GET"
                class="patient-search-form"
            >

                <input
                    type="search"
                    name="search"
                    class="patient-search-input"
                    placeholder="Search by name or ID number..."
                    value="<?= escapePatient($search) ?>"
                    maxlength="100"
                    aria-label="Search patients"
                >

                <button type="submit" class="primary-btn">
                    Search
                </button>

                <?php if ($search !== ''): ?>
                    <a href="patients.php" class="cancel-btn">
                        Clear
                    </a>
                <?php endif; ?>

            </form>

        </div>

        <div class="table-container">

            <table class="dashboard-table">

                <thead>
                    <tr>
                        <th>Patient ID</th>
                        <th>Patient Name</th>
                        <th>ID Number</th>
                        <th>Gender</th>
                        <th>Contact Number</th>
                        <th>Date Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (!empty($patients)): ?>

                        <?php foreach ($patients as $patient): ?>

                            <tr>

                                <td>
                                    <?= (int)$patient['patient_id'] ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= escapePatient(
                                            $patient['first_name'] . ' ' .
                                            $patient['last_name']
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?php
                                    $idNumber = (string)(
                                        $patient['id_number'] ?? ''
                                    );

                                    $maskedId = $idNumber === ''
                                        ? 'N/A'
                                        : str_repeat(
                                            '*',
                                            max(0, strlen($idNumber) - 4)
                                        ) . substr($idNumber, -4);

                                    echo escapePatient($maskedId);
                                    ?>
                                </td>

                                <td>
                                    <?= escapePatient(
                                        $patient['gender'] ?: 'N/A'
                                    ) ?>
                                </td>

                                <td>
                                    <?= escapePatient(
                                        $patient['contact_number'] ?: 'N/A'
                                    ) ?>
                                </td>

                                <td>
                                    <?= date(
                                        'd M Y',
                                        strtotime($patient['created_at'])
                                    ) ?>
                                </td>


                                <td>
                                    <a
                                        href="patient-details.php?id=<?= (int)$patient['patient_id'] ?>"
                                        class="patient-view-btn"
                                    >
                                        View Details
                                    </a>


                                    <a
                                        href="check-in.php?id=<?= (int)$patient['patient_id'] ?>"
                                        class="patient-view-btn"
                                    >
                                        Check In
                                    </a>

                                </td>


                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="empty-table-message">

                                <?php if ($search !== ''): ?>
                                    No patients match your search.
                                <?php else: ?>
                                    No registered patients found.
                                <?php endif; ?>

                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <?php if ($totalPages > 1): ?>

            <div class="patient-pagination">

                <span>
                    Page <?= $page ?> of <?= $totalPages ?>
                </span>

                <div class="pagination-actions">

                    <?php if ($page > 1): ?>

                        <a
                            href="<?= escapePatient(
                                'patients.php?' . http_build_query([
                                    'search' => $search,
                                    'page' => $page - 1
                                ])
                            ) ?>"
                            class="cancel-btn"
                        >
                            Previous
                        </a>

                    <?php endif; ?>

                    <?php if ($page < $totalPages): ?>

                        <a
                            href="<?= escapePatient(
                                'patients.php?' . http_build_query([
                                    'search' => $search,
                                    'page' => $page + 1
                                ])
                            ) ?>"
                            class="primary-btn"
                        >
                            Next
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        <?php endif; ?>

    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
