<?php
// ============================================================
// ADMIN — PAYMENTS MANAGEMENT
// ============================================================
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role('admin');

$page_title = 'Payments';

// ADD PAYMENT
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $student_id   = (int)$_POST['student_id'];
    $payment_type = clean($conn, $_POST['payment_type']);
    $amount       = (float)$_POST['amount'];
    $term         = clean($conn, $_POST['term']);
    $session      = clean($conn, $_POST['session']);
    $payment_date = clean($conn, $_POST['payment_date']);
    $status       = clean($conn, $_POST['status']);
    $notes        = clean($conn, $_POST['notes']);
    $receipt_no   = 'RCP-' . strtoupper(uniqid());

    $sql = "INSERT INTO payments (student_id, payment_type, amount, term, session, payment_date, receipt_no, status, notes)
            VALUES ($student_id,'$payment_type',$amount,'$term','$session','$payment_date','$receipt_no','$status','$notes')";

    if (mysqli_query($conn, $sql)) {
        $pay_id = mysqli_insert_id($conn);
        alert('success', 'Payment recorded! Receipt: ' . $receipt_no);
        redirect("payments.php?receipt=$pay_id");
    } else {
        alert('danger', mysqli_error($conn));
        redirect('payments.php');
    }
}

// DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM payments WHERE id=$id");
    alert('success', 'Payment record deleted.');
    redirect('payments.php');
}

// FILTERS
$filter_status = isset($_GET['status']) ? clean($conn, $_GET['status']) : '';
$filter_term   = isset($_GET['term'])   ? clean($conn, $_GET['term'])   : '';
$where = "WHERE 1=1";
if ($filter_status) $where .= " AND p.status='$filter_status'";
if ($filter_term)   $where .= " AND p.term='$filter_term'";

$payments = mysqli_query($conn, "
    SELECT p.*, s.fullname, s.student_id as sid
    FROM payments p
    JOIN students s ON p.student_id = s.id
    $where
    ORDER BY p.created_at DESC
");

$total_paid    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as s FROM payments WHERE status='Paid'"))['s'] ?? 0;
$total_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as s FROM payments WHERE status='Pending'"))['s'] ?? 0;
$total_partial = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as s FROM payments WHERE status='Partial'"))['s'] ?? 0;

$students = mysqli_query($conn, "SELECT id, fullname, student_id FROM students ORDER BY fullname");
$terms    = ['First Term', 'Second Term', 'Third Term'];
$yr = (int)date('Y');
$sessions = [];
for ($y = $yr; $y >= $yr-3; $y--) $sessions[] = $y . '/' . ($y+1);

// Receipt view
$receipt_data = null;
if (isset($_GET['receipt'])) {
    $rid = (int)$_GET['receipt'];
    $receipt_data = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT p.*, s.fullname, s.student_id as sid, c.class_name
        FROM payments p
        JOIN students s ON p.student_id = s.id
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE p.id=$rid
    "));
}

require_once '../includes/header.php';
?>

<?php show_alert(); ?>

<!-- RECEIPT MODAL -->
<?php if ($receipt_data): ?>
<div class="modal-overlay active" id="receiptModal">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            Payment Receipt
            <button class="modal-close" onclick="closeModal('receiptModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body" id="receiptPrint">
            <div style="text-align:center;margin-bottom:18px;">
                <i class="fa-solid fa-graduation-cap" style="font-size:36px;color:var(--primary);"></i>
                <h2 style="color:var(--primary);font-size:18px;margin:6px 0 2px;">SchoolMS</h2>
                <p style="color:var(--text-muted);font-size:12px;">Payment Receipt</p>
                <div style="border-top:2px dashed var(--border);margin:12px 0;"></div>
            </div>
            <table style="width:100%;font-size:13px;border-collapse:collapse;">
                <tr><td style="padding:6px 0;color:var(--text-muted);">Receipt No:</td><td style="font-weight:700;"><?= $receipt_data['receipt_no'] ?></td></tr>
                <tr><td style="padding:6px 0;color:var(--text-muted);">Student:</td><td><?= htmlspecialchars($receipt_data['fullname']) ?></td></tr>
                <tr><td style="padding:6px 0;color:var(--text-muted);">Student ID:</td><td><?= $receipt_data['sid'] ?></td></tr>
                <tr><td style="padding:6px 0;color:var(--text-muted);">Class:</td><td><?= $receipt_data['class_name'] ?? '—' ?></td></tr>
                <tr><td style="padding:6px 0;color:var(--text-muted);">Payment Type:</td><td><?= $receipt_data['payment_type'] ?></td></tr>
                <tr><td style="padding:6px 0;color:var(--text-muted);">Term:</td><td><?= $receipt_data['term'] ?></td></tr>
                <tr><td style="padding:6px 0;color:var(--text-muted);">Session:</td><td><?= $receipt_data['session'] ?></td></tr>
                <tr><td style="padding:6px 0;color:var(--text-muted);">Date:</td><td><?= date('F d, Y', strtotime($receipt_data['payment_date'])) ?></td></tr>
                <tr><td style="padding:6px 0;color:var(--text-muted);">Status:</td><td><span class="badge badge-success"><?= $receipt_data['status'] ?></span></td></tr>
            </table>
            <div style="background:var(--primary);color:#fff;border-radius:8px;padding:14px;text-align:center;margin-top:16px;">
                <div style="font-size:11px;letter-spacing:1px;text-transform:uppercase;opacity:0.7;">Amount Paid</div>
                <div style="font-size:28px;font-weight:800;">₦<?= number_format($receipt_data['amount'], 2) ?></div>
            </div>
            <?php if ($receipt_data['notes']): ?>
            <p style="font-size:12px;color:var(--text-muted);margin-top:10px;">Note: <?= htmlspecialchars($receipt_data['notes']) ?></p>
            <?php endif; ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('receiptModal')">Close</button>
            <button type="button" class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px;">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div class="stat-info">
            <div class="stat-value">₦<?= number_format($total_paid, 0) ?></div>
            <div class="stat-label">Total Paid</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fa-solid fa-clock"></i></div>
        <div class="stat-info">
            <div class="stat-value">₦<?= number_format($total_pending, 0) ?></div>
            <div class="stat-label">Total Pending</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-circle-half-stroke"></i></div>
        <div class="stat-info">
            <div class="stat-value">₦<?= number_format($total_partial, 0) ?></div>
            <div class="stat-label">Partial Payments</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">
    <!-- Payments Table -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Payment Records</span>
            <!-- Filter -->
            <form method="GET" style="display:flex;gap:8px;">
                <select name="status" class="form-control" style="width:130px;padding:7px 10px;">
                    <option value="">All Status</option>
                    <option value="Paid"    <?= $filter_status=='Paid'?'selected':'' ?>>Paid</option>
                    <option value="Pending" <?= $filter_status=='Pending'?'selected':'' ?>>Pending</option>
                    <option value="Partial" <?= $filter_status=='Partial'?'selected':'' ?>>Partial</option>
                </select>
                <select name="term" class="form-control" style="width:140px;padding:7px 10px;">
                    <option value="">All Terms</option>
                    <?php foreach ($terms as $t): ?>
                    <option value="<?= $t ?>" <?= $filter_term==$t?'selected':'' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-outline btn-sm"><i class="fa-solid fa-filter"></i></button>
            </form>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Student</th><th>Type</th><th>Amount</th><th>Term</th><th>Date</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($payments) === 0): ?>
                    <tr><td colspan="7" style="text-align:center;padding:24px;color:var(--text-muted);">No payment records found.</td></tr>
                <?php else: ?>
                <?php while ($p = mysqli_fetch_assoc($payments)): ?>
                    <tr>
                        <td>
                            <div class="table-avatar">
                                <div class="avatar-circle" style="background:var(--success);"><?= strtoupper(substr($p['fullname'],0,1)) ?></div>
                                <div>
                                    <div><?= htmlspecialchars($p['fullname']) ?></div>
                                    <div style="font-size:11px;color:var(--text-muted);"><?= $p['sid'] ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($p['payment_type']) ?></td>
                        <td><strong>₦<?= number_format($p['amount'], 2) ?></strong></td>
                        <td><?= $p['term'] ?></td>
                        <td><?= date('M d, Y', strtotime($p['payment_date'])) ?></td>
                        <td>
                            <?php
                            $bc = ['Paid'=>'badge-success','Pending'=>'badge-warning','Partial'=>'badge-info'][$p['status']] ?? 'badge-info';
                            echo "<span class='badge $bc'>{$p['status']}</span>";
                            ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="?receipt=<?= $p['id'] ?>" class="btn btn-outline btn-sm" title="View Receipt"><i class="fa-solid fa-receipt"></i></a>
                                <button class="btn btn-danger btn-sm" onclick="confirmDelete('?delete=<?= $p['id'] ?>','this payment')"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Payment Form -->
    <div class="card">
        <div class="card-header"><span class="card-title">Record Payment</span></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group" style="margin-bottom:10px;">
                    <label>Student *</label>
                    <select name="student_id" class="form-control" required>
                        <option value="">-- Select Student --</option>
                        <?php while ($s = mysqli_fetch_assoc($students)): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['fullname']) ?> (<?= $s['student_id'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:10px;">
                    <label>Payment Type *</label>
                    <select name="payment_type" class="form-control" required>
                        <option value="School Fees">School Fees</option>
                        <option value="PTA Levy">PTA Levy</option>
                        <option value="Examination Fee">Examination Fee</option>
                        <option value="Development Levy">Development Levy</option>
                        <option value="Uniform">Uniform</option>
                        <option value="Books">Books</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:10px;">
                    <label>Amount (₦) *</label>
                    <input type="number" name="amount" class="form-control" min="0" step="0.01" required>
                </div>
                <div class="form-group" style="margin-bottom:10px;">
                    <label>Term</label>
                    <select name="term" class="form-control">
                        <?php foreach ($terms as $t): ?>
                        <option value="<?= $t ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:10px;">
                    <label>Session</label>
                    <select name="session" class="form-control">
                        <?php foreach ($sessions as $s): ?>
                        <option value="<?= $s ?>"><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:10px;">
                    <label>Payment Date *</label>
                    <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group" style="margin-bottom:10px;">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="Paid">Paid</option>
                        <option value="Pending">Pending</option>
                        <option value="Partial">Partial</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:14px;">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                    <i class="fa-solid fa-save"></i> Record Payment
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
