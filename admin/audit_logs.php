<?php include_once __DIR__ . '/databases/audit-logs-process.php'; ?>

<main class="main-content">

    <div class="page-heading">
        <h1>Audit Logs</h1>
        <p>
            Monitor recorded activities and changes
            performed within the Hospital Management System.
        </p>
    </div>

    <div class="content-card">

        <div class="card-heading audit-card-heading">

            <div>
                <h3>System Activity History</h3>

                <p class="audit-count">
                    <?= $totalLogs ?>
                    <?= $totalLogs === 1
                        ? 'record'
                        : 'records' ?>
                    found
                </p>
            </div>

            <form
                action="audit_logs.php"
                method="GET"
                class="audit-filters"
            >

                <!-- SEARCH -->
                <input
                    type="search"
                    name="search"
                    class="audit-search"
                    placeholder="Search username or action..."
                    value="<?= escapeAudit($search) ?>"
                    maxlength="100"
                    aria-label="Search audit logs"
                >

                <!-- ENTITY FILTER -->
                <select
                    name="entity_type"
                    class="audit-filter-select"
                    aria-label="Filter entity type"
                >

                    <option value="">
                        All Activities
                    </option>

                    <?php foreach ($entityTypes as $type): ?>

                        <option
                            value="<?= escapeAudit($type) ?>"
                            <?= $entityType === $type
                                ? 'selected' : '' ?>
                        >
                            <?= escapeAudit(
                                ucwords(
                                    str_replace('_', ' ', $type)
                                )
                            ) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <button
                    type="submit"
                    class="primary-btn"
                >
                    Search
                </button>

                <?php if (
                    $search !== '' ||
                    $entityType !== ''
                ): ?>

                    <a
                        href="audit_logs.php"
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
                        <th>Log ID</th>
                        <th>Performed By</th>
                        <th>User Role</th>
                        <th>Action</th>
                        <th>Entity Type</th>
                        <th>Record ID</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (!empty($auditLogs)): ?>

                        <?php foreach ($auditLogs as $log): ?>

                            <tr>

                                <td>
                                    <?= (int)$log['log_id'] ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= escapeAudit(
                                            $log['username']
                                            ?? 'Unknown User'
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= escapeAudit(
                                        $log['role'] ?? 'N/A'
                                    ) ?>
                                </td>

                                <td>
                                    <span class="audit-action">
                                        <?= escapeAudit(
                                            $log['action']
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="audit-entity">
                                        <?= escapeAudit(
                                            !empty($log['entity_type'])
                                            ? ucwords(
                                                str_replace(
                                                    '_',
                                                    ' ',
                                                    $log['entity_type']
                                                )
                                            )
                                            : 'N/A'
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= $log['entity_id'] !== null
                                        ? (int)$log['entity_id']
                                        : 'N/A' ?>
                                </td>

                                <td>
                                    <?= formatAuditDate(
                                        $log['created_at']
                                    ) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td
                                colspan="7"
                                class="empty-table-message"
                            >

                                <?php if (
                                    $search !== '' ||
                                    $entityType !== ''
                                ): ?>

                                    No audit logs match
                                    your search or filter.

                                <?php else: ?>

                                    No system activities have
                                    been recorded yet.

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <!-- PAGINATION -->
        <?php if ($totalPages > 1): ?>

            <div class="audit-pagination">

                <span>
                    Page <?= $page ?> of <?= $totalPages ?>
                </span>

                <div class="pagination-actions">

                    <?php if ($page > 1): ?>

                        <a
                            href="<?= escapeAudit(
                                'audit_logs.php?' .
                                http_build_query([
                                    'search' => $search,
                                    'entity_type' => $entityType,
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
                            href="<?= escapeAudit(
                                'audit_logs.php?' .
                                http_build_query([
                                    'search' => $search,
                                    'entity_type' => $entityType,
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
