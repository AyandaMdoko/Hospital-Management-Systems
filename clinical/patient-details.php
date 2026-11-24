<?php include_once __DIR__ . '/databases/patient-details-process.php'; ?>

<main class="clinical-main pm-page">
    <div class="clinical-page-heading">
        <div>
            <div class="clinical-eyebrow">PATIENT MEDICAL RECORDS</div>
            <h1><?= phH($patient['first_name'] . ' ' . $patient['last_name']) ?></h1>
            <p>Read-only clinical record &middot; Patient #<?= (int) $patientId ?></p>
        </div>
        <a class="pm-btn pm-secondary" href="/clinical/patients.php">Back to Patient Search</a>
    </div>

    <section class="clinical-card pm-card">
        <div class="clinical-card-heading">
            <div>
                <h2>Patient Information</h2>
                <p>Identity details are displayed without ID/passport numbers or contact information.</p>
            </div>
        </div>
        <dl class="pm-facts">
            <div><dt>Patient ID</dt><dd>#<?= (int) $patientId ?></dd></div>
            <div><dt>Date of Birth</dt><dd><?= phH($patient['date_of_birth'] ?: 'Not recorded') ?></dd></div>
            <div><dt>Gender</dt><dd><?= phH($patient['gender'] ?: 'Not recorded') ?></dd></div>
            <div><dt>Citizenship</dt><dd><?= phH($patient['citizenship_status'] ?: 'Not confirmed') ?></dd></div>
        </dl>
    </section>

    <section class="clinical-card pm-card">
        <div class="clinical-card-heading">
            <div>
                <h2>Visit History</h2>
                <p>Most recent 20 check-ins. Visit status is separate from consultation status.</p>
            </div>
        </div>
        <div class="clinical-table-scroll">
            <table class="clinical-table pm-table">
                <thead>
                    <tr>
                        <th>Visit</th>
                        <th>Checked In</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Completed At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$visits): ?>
                        <tr><td colspan="5">No visits recorded.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($visits as $v): ?>
                        <tr>
                            <td>#<?= (int) $v['visit_id'] ?></td>
                            <td><?= phDate($v['check_in_at']) ?></td>
                            <td><?= phH($v['visit_type']) ?></td>
                            <td><?= phH($v['visit_status']) ?></td>
                            <td><?= phDate($v['completed_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="clinical-card pm-card">
        <div class="clinical-card-heading">
            <div>
                <h2>Completed Consultations</h2>
                <p>Most recent 30 completed records, including Doctor and Professional Nurse entries.</p>
            </div>
        </div>
        <?php if (!$consultations): ?>
            <p class="pm-empty">No completed consultations recorded.</p>
        <?php endif; ?>
        <div class="pm-history">
            <?php foreach ($consultations as $c): ?>
                <details class="pm-history-entry">
                    <summary>
                        <strong><?= phDate($c['consultation_date']) ?></strong>
                        &middot; <?= phH($c['clinician_first'] . ' ' . $c['clinician_last']) ?>
                        (<?= phH($c['professional_role']) ?>)
                        &middot; Consultation #<?= (int) $c['consultation_id'] ?>
                        <?= $c['visit_id'] ? ' &middot; Visit #' . (int) $c['visit_id'] : ' &middot; Legacy record' ?>
                    </summary>
                    <div class="pm-notes-grid">
                        <div>
                            <h3>Symptoms / Assessment</h3>
                            <p><?= nl2br(phH($c['symptoms'] ?: 'Not recorded')) ?></p>
                        </div>
                        <div>
                            <h3>Clinical Impression</h3>
                            <p><?= nl2br(phH($c['diagnosis'] ?: 'Not recorded')) ?></p>
                        </div>
                        <div>
                            <h3>Treatment / Care Plan</h3>
                            <p><?= nl2br(phH($c['treatment_plan'] ?: 'Not recorded')) ?></p>
                        </div>
                        <div>
                            <h3>Notes</h3>
                            <p><?= nl2br(phH($c['notes'] ?: 'Not recorded')) ?></p>
                        </div>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="clinical-card pm-card">
        <div class="clinical-card-heading">
            <div>
                <h2>Nursing Observations</h2>
                <p>Most recent 30 observations (read-only).</p>
            </div>
        </div>
        <div class="clinical-table-scroll">
            <table class="clinical-table pm-table">
                <thead>
                    <tr>
                        <th>Recorded</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>Clinical Notes</th>
                        <th>Recorded By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$observations): ?>
                        <tr><td colspan="5">No nursing observations have been recorded.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($observations as $o): ?>
                        <tr>
                            <td><?= phDate($o['observation_date']) ?></td>
                            <td><?= phH($o['observation_type']) ?></td>
                            <td><?= phH($o['observation_value']) ?></td>
                            <td class="pm-wrap"><?= nl2br(phH($o['notes'] ?: 'Not recorded')) ?></td>
                            <td>
                                <?= phH($o['clinician_first'] . ' ' . $o['clinician_last']) ?>
                                (<?= phH($o['professional_role']) ?>)
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <p class="pm-footnote">
        Access is logged. Medical information is confidential and must only be used for authorized patient care.
        No changes can be made from this page.
    </p>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
