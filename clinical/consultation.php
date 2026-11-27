
<?php include_once __DIR__ . '/databases/consultation-process.php'; ?>

<main class="clinical-main clinical-consultation-page">
    <div class="clinical-page-heading">
        <div>
            <div class="clinical-eyebrow">SHARED CLINICAL WORKSPACE</div>
            <h1>Patient Consultation</h1>
            <p>Review this visit and its relevant medical history before recording clinical care.</p>
        </div>
        <a class="cc-btn cc-btn-outline" href="/clinical/waiting-list.php">Back to Waiting List</a>
    </div>

    <?php if ($error !== ''): ?>
        <div class="cc-alert cc-alert-error" role="alert"><?= ccH($error) ?></div>
    <?php endif; ?>
    <?php if (is_array($flash) && isset($flash['message'])): ?>
        <div class="cc-alert cc-alert-success" role="status"><?= ccH($flash['message']) ?></div>
    <?php endif; ?>

    
    <section class="clinical-card">
        <div class="clinical-card-heading">
            <div><h2><?= ccH($visit['first_name'] . ' ' . $visit['last_name']) ?></h2>
                 <p>Patient #<?= (int) $visit['patient_id'] ?> &middot; Visit #<?= (int) $visitId ?></p></div>
            <span class="clinical-status <?= $visit['visit_status'] === 'Waiting' ? 'clinical-badge-waiting' : ($visit['visit_status'] === 'In Consultation' ? 'clinical-badge-progress' : '') ?>">
                <?= ccH($visit['visit_status']) ?>
            </span>
        </div>
        <dl class="cc-facts">
            <div><dt>Visit Category</dt><dd><?= ccH($visit['visit_type']) ?></dd></div>
            <div><dt>Checked In</dt><dd><?= ccH(date('d M Y, h:i A', strtotime($visit['check_in_at']))) ?></dd></div>
            <div><dt>Date of Birth</dt><dd><?= ccH($visit['date_of_birth'] ?: 'Not recorded') ?></dd></div>
            <div><dt>Gender</dt><dd><?= ccH($visit['gender'] ?: 'Not recorded') ?></dd></div>
        </dl>
    </section>

    <!-- Display the current consultation form or read-only view, depending on the user's authorization and the visit's status. -->
    <section class="clinical-card" aria-labelledby="cc-work-title">
        <div class="clinical-card-heading">
            <div>
                <h2 id="cc-work-title">Current Clinical Work</h2>
                <p>Only the clinician who starts a consultation can change its notes.</p>
            </div>
        </div>

        <?php if ($canStart): ?>
            <p class="cc-hint">Review the patient's past records below. Click Start Consultation only when you are ready to begin care.</p>
            <form method="post" action="/clinical/consultation.php?visit_id=<?= $visitId ?>">
                <input type="hidden" name="csrf_token" value="<?= ccH($_SESSION['clinical_consultation_csrf']) ?>">
                <input type="hidden" name="action" value="start">
                <button class="cc-btn cc-btn-primary" type="submit">Start Consultation</button>
            </form>

        <?php elseif ($active): ?>
            <p class="cc-hint">
                Consultation #<?= (int) $active['consultation_id'] ?> started by
                <strong><?= ccH($active['staff_first'] . ' ' . $active['staff_last']) ?></strong>
                (<?= ccH($active['professional_role']) ?>).
            </p>


            <!-- Display the consultation form if the current user is authorized to edit it. Otherwise, show a read-only view. -->
            <?php if ($canEdit): ?>
                <form class="cc-form" method="post" action="/clinical/consultation.php?visit_id=<?= $visitId ?>">
                    <input type="hidden" name="csrf_token" value="<?= ccH($_SESSION['clinical_consultation_csrf']) ?>">
                    <input type="hidden" name="consultation_id" value="<?= (int) $active['consultation_id'] ?>">
                    <div class="cc-fields">
                        <div><label for="cc-symptoms">Symptoms / Assessment</label><textarea id="cc-symptoms" name="symptoms" rows="3" maxlength="5000"><?= ccH($active['symptoms']) ?></textarea></div>
                        <div><label for="cc-diagnosis">Diagnosis / Clinical Impression</label><textarea id="cc-diagnosis" name="diagnosis" rows="3" maxlength="5000"><?= ccH($active['diagnosis']) ?></textarea></div>
                        <div><label for="cc-plan">Treatment / Care Plan</label><textarea id="cc-plan" name="treatment_plan" rows="3" maxlength="5000"><?= ccH($active['treatment_plan']) ?></textarea></div>
                        <div><label for="cc-notes">Additional Clinical Notes</label><textarea id="cc-notes" name="notes" rows="4" maxlength="10000"><?= ccH($active['notes']) ?></textarea></div>
                    </div>

                    <p class="cc-hint">Document only care you are professionally authorized to provide. This module does not authorize prescribing medication.</p>
                    <div class="cc-next">
                        <label for="cc-next-step">After completing this consultation</label>
                        <select name="next_step" id="cc-next-step">
                            <option value="">Choose when completing</option>
                            <option value="finish_visit">Patient care is finished — close visit</option>
                            <option value="handoff">Patient needs another clinician — return to Waiting</option>
                        </select>
                    </div>

                    <div class="cc-actions">
                        <button class="cc-btn cc-btn-outline" type="submit" name="action" value="save">Save Draft</button>
                        <button class="cc-btn cc-btn-primary" type="submit" name="action" value="complete" onclick="return confirm('Complete this consultation and apply the selected visit outcome? Completed records cannot be edited here.');">Complete Consultation</button>
                    </div>
                </form>
            <?php else: ?>
                
                <p class="cc-alert cc-alert-neutral">This consultation is in progress. The responsible clinician must finish or hand over their work before another consultation can start.</p>
                <div class="cc-readonly-grid">
                    <div><h3>Symptoms / Assessment</h3><p><?= nl2br(ccH($active['symptoms'] ?: 'No notes yet')) ?></p></div>
                    <div><h3>Clinical Impression</h3><p><?= nl2br(ccH($active['diagnosis'] ?: 'No notes yet')) ?></p></div>
                    <div><h3>Care Plan</h3><p><?= nl2br(ccH($active['treatment_plan'] ?: 'No notes yet')) ?></p></div>
                    <div><h3>Additional Notes</h3><p><?= nl2br(ccH($active['notes'] ?: 'No notes yet')) ?></p></div>
                </div>

            <?php endif; ?>
        <?php else: ?>
            <p class="cc-alert cc-alert-neutral">This visit is <?= ccH($visit['visit_status']) ?> and cannot start another consultation. Its recorded history remains available below.</p>
        <?php endif; ?>
    </section>

    <!-- Display the patient's previous consultations and nursing observations, if any exist. -->
    <section class="clinical-card">
        <div class="clinical-card-heading"><div><h2>Previous Consultations</h2><p>Up to 12 most recent completed records for this patient.</p></div></div>
        <?php if ($history): ?>
            <div class="cc-history">
                <?php foreach ($history as $entry): ?>
                    <details class="cc-history-entry">
                        <summary><strong><?= ccH(date('d M Y', strtotime($entry['consultation_date']))) ?></strong>
                            &middot; <?= ccH($entry['staff_first'] . ' ' . $entry['staff_last']) ?>
                            (<?= ccH($entry['professional_role']) ?>)
                            &middot; Consultation #<?= (int) $entry['consultation_id'] ?></summary>
                        <div class="cc-readonly-grid">
                            <div><h3>Symptoms / Assessment</h3><p><?= nl2br(ccH($entry['symptoms'] ?: 'Not recorded')) ?></p></div>
                            <div><h3>Clinical Impression</h3><p><?= nl2br(ccH($entry['diagnosis'] ?: 'Not recorded')) ?></p></div>
                            <div><h3>Treatment / Care Plan</h3><p><?= nl2br(ccH($entry['treatment_plan'] ?: 'Not recorded')) ?></p></div>
                            <div><h3>Notes</h3><p><?= nl2br(ccH($entry['notes'] ?: 'Not recorded')) ?></p></div>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="cc-hint">No completed consultations have been recorded for this patient.</p>
        <?php endif; ?>
    </section>

    <!-- Display the most recent nursing observations for this patient, if any exist. -->
    <section class="clinical-card">
        <div class="clinical-card-heading"><div><h2>Previous Nursing Observations</h2><p>Up to 8 most recent observation records.</p></div></div>
        <?php if ($observations): ?>
            <div class="clinical-table-scroll"><table class="clinical-table">
                <thead><tr><th>Date</th><th>Type</th><th>Observation</th><th>Recorded By</th></tr></thead>
                <tbody>
                <?php foreach ($observations as $observation): ?>
                    <tr>
                        <td><?= ccH(date('d M Y, h:i A', strtotime($observation['observation_date']))) ?></td>
                        <td><?= ccH($observation['observation_type']) ?></td>
                        <td><?= ccH($observation['observation_value']) ?>
                            <?php if (trim((string) ($observation['notes'] ?? '')) !== ''): ?>
                                <div class="cc-muted"><?= ccH($observation['notes']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= ccH($observation['staff_first'] . ' ' . $observation['staff_last']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody></table></div>
        <?php else: ?>
            <p class="cc-hint">No nursing observations have been recorded for this patient.</p>
        <?php endif; ?>
    </section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
