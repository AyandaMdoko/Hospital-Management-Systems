<?php include_once __DIR__ . '/databases/patients-process.php'; ?>

<main class="clinical-main pm-page">
    <div class="clinical-page-heading">
        <div>
            <div class="clinical-eyebrow">SHARED CLINICAL WORKSPACE</div>
            <h1>Patient Medical Records</h1>
            <p>Find patients for whom you have an active clinical-care relationship.</p>
        </div>
    </div>

    <section class="clinical-card pm-card">
        <div class="clinical-card-heading">
            <div>
                <h2>Find Patient</h2>
                <p>Search by patient name or internal Patient ID. Results are limited to active clinic visits and patients you previously treated.</p>
            </div>
        </div>

        <form method="get" action="/clinical/patients.php" class="pm-search">
            <div>
                <label for="pm-search">Patient name or ID</label>
                <input
                    id="pm-search"
                    type="search"
                    name="search"
                    maxlength="80"
                    placeholder="e.g. Demo ClinicalTest or 12"
                    value="<?= pmH($search) ?>"
                >
            </div>
            <button class="pm-btn pm-primary" type="submit">Search</button>
            <?php if ($search !== ''): ?>
                <a class="pm-btn pm-secondary" href="/clinical/patients.php">Clear</a>
            <?php endif; ?>
        </form>

        <div class="pm-result-count" role="status">
            <?= $total ?> accessible patient<?= $total === 1 ? '' : 's' ?> found.
        </div>

        <div class="clinical-table-scroll">
            <table class="clinical-table pm-table">
                <thead>
                    <tr>
                        <th>Patient ID</th>
                        <th>Patient</th>
                        <th>Most Recent Visit</th>
                        <th>Latest Visit Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$patients): ?>
                        <tr>
                            <td colspan="5">No matching patients with authorized clinical access were found.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($patients as $p): ?>
                        <tr>
                            <td>#<?= (int) $p['patient_id'] ?></td>
                            <td><strong><?= pmH($p['first_name'] . ' ' . $p['last_name']) ?></strong></td>
                            <td><?= $p['last_visit'] ? pmH(date('d M Y, h:i A', strtotime($p['last_visit']))) : 'No visit' ?></td>
                            <td><?= pmH($p['last_status'] ?? 'No visit') ?></td>
                            <td>
                                <a
                                    class="pm-btn pm-secondary"
                                    href="/clinical/patient-details.php?patient_id=<?= (int) $p['patient_id'] ?>"
                                >
                                    View Medical History
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
            <nav class="pm-pages" aria-label="Patient results pages">
                <?php if ($page > 1): ?>
                    <a href="<?= pmH(pmUrl($page - 1, $search)) ?>">Previous</a>
                <?php endif; ?>
                <span>Page <?= $page ?> of <?= $pages ?></span>
                <?php if ($page < $pages): ?>
                    <a href="<?= pmH(pmUrl($page + 1, $search)) ?>">Next</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </section>

    <p class="pm-footnote">
        Clinical records are confidential. Every medical-history page view is recorded in the audit log.
        If a patient is not listed, use the established check-in workflow rather than accessing their record without a care relationship.
    </p>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
