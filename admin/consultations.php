<?php include_once __DIR__ . '/databases/consultations-process.php'; ?>

<main class="main-content">

    <div class="page-heading">
        <h1>Consultation Management</h1>
        <p>
            View and monitor patient consultations
            recorded by healthcare professionals.
        </p>
    </div>

    <div class="content-card">

        <div class="card-heading consultation-card-heading">

            <div>
                <h3>Consultation Records</h3>

                <p class="consultation-count">
                    <?= $totalConsultations ?>
                    <?= $totalConsultations === 1
                        ? 'consultation'
                        : 'consultations' ?>
                    found
                </p>
            </div>

            <form
                action="consultations.php"
                method="GET"
                class="consultation-filters"
            >

                <input
                    type="search"
                    name="search"
                    class="consultation-search"
                    placeholder="Search patient or staff..."
                    value="<?= escapeConsultation($search) ?>"
                    maxlength="100"
                    aria-label="Search consultations"
                >

                <select
                    name="status"
                    class="consultation-status-filter"
                    aria-label="Filter consultation status"
                >
                    <option value="">
                        All Statuses
                    </option>

                    <option
                        value="Completed"
                        <?= $status === 'Completed'
                            ? 'selected' : '' ?>
                    >
                        Completed
                    </option>

                    <option
                        value="In Progress"
                        <?= $status === 'In Progress'
                            ? 'selected' : '' ?>
                    >
                        In Progress
                    </option>
                </select>

                <button
                    type="submit"
                    class="primary-btn"
                >
                    Search
                </button>

                <?php if ($search !== '' || $status !== ''): ?>
                    <a
                        href="consultations.php"
                        class="cancel-btn"
                    >
                        Clear
                    </a>
                <?php endif; ?>

            </form>

        </div>

        <div class="table-container">

            <table class="dashboard-table">

                <thead>
                    <tr>
                        <th>Consultation ID</th>
                        <th>Patient</th>
                        <th>Attending Staff</th>
                        <th>Professional Role</th>
                        <th>Consultation Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (!empty($consultations)): ?>

                        <?php foreach ($consultations as $consultation): ?>

                            <tr>

                                <td>
                                    <?= (int)$consultation['consultation_id'] ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= escapeConsultation(
                                            $consultation['patient_first_name']
                                            . ' ' .
                                            $consultation['patient_last_name']
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= escapeConsultation(
                                        $consultation['staff_first_name']
                                        . ' ' .
                                        $consultation['staff_last_name']
                                    ) ?>
                                </td>

                                <td>
                                    <?= escapeConsultation(
                                        $consultation['professional_role']
                                    ) ?>
                                </td>

                                <td>
                                    <?= formatConsultationDate(
                                        $consultation['consultation_date']
                                    ) ?>
                                </td>

                                <td>
                                    <?php
                                    $consultationStatus =
                                        $consultation['consultation_status'];

                                    $statusClass =
                                        $consultationStatus === 'Completed'
                                        ? 'completed'
                                        : 'in-progress';
                                    ?>

                                    <span
                                        class="status-badge <?= $statusClass ?>"
                                    >
                                        <?= escapeConsultation(
                                            $consultationStatus
                                        ) ?>
                                    </span>
                                </td>


                                <td>
                                    <a
                                        href="consultation-details.php?id=<?= (int)$consultation['consultation_id'] ?>"
                                        class="consultation-view-btn"
                                    >
                                        View Details
                                    </a>
                                </td>


                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td
                                colspan="6"
                                class="empty-table-message"
                            >
                                <?php if (
                                    $search !== '' || $status !== ''
                                ): ?>

                                    No consultations match
                                    your search or filter.

                                <?php else: ?>

                                    No consultations have
                                    been recorded yet.

                                <?php endif; ?>
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <?php if ($totalPages > 1): ?>

            <div class="consultation-pagination">

                <span>
                    Page <?= $page ?> of <?= $totalPages ?>
                </span>

                <div class="pagination-actions">

                    <?php if ($page > 1): ?>

                        <a
                            href="<?= escapeConsultation(
                                'consultations.php?' .
                                http_build_query([
                                    'search' => $search,
                                    'status' => $status,
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
                            href="<?= escapeConsultation(
                                'consultations.php?' .
                                http_build_query([
                                    'search' => $search,
                                    'status' => $status,
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

    </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
