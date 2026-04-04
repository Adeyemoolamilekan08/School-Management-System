<?php
// ============================================================
// STUDENT — MY PAYMENTS
// ============================================================
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role('student');

$page_title = 'My Payments';

$user_id    = (int)$_SESSION['user_id'];
$student    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM students WHERE user_id=$user_id"));
$student_id = (int)$student['id'];

$total_paid    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as s FROM payments WHERE student_id=$student_id AND status='Paid'"))['s'] ?? 0;
$total_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as s FROM payments WHERE student_id=$student_id AND status='Pending'"))['s'] ?? 0;

$payments = mysqli_query($conn, "SELECT * FROM payments WHERE student_id=$student_id ORDER BY payment_date DESC");

// Receipt
$receipt_data = null;
if (isset($_GET['receipt'])) {
    $rid = (int)$_GET['receipt'];
    $receipt_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT p.*, s.fullname, s.student_id as sid, c.class_name FROM payments p JOIN students s ON p.student_id=s.id LEFT JOIN classes c ON s.class_id=c.id WHERE p.id=$rid AND p.student_id=$student_id"));
}

require_once '../includes/header.php';
?>

<?php if ($receipt_data): ?>
<div class="modal-overlay active" id="receiptModal">
    <div class="modal" style="max-width:460px;">
        <div class="modal-header">Receipt <button class="modal-close" onclick="closeModal('receiptModal')"><i class="fa-solid fa-xmark"></i></button></div>
        <div class="modal-body">
            <div style="text-align:center;margin-bottom:16px;">
                <i class="fa-solid fa-graduation-cap" style="font-size:36px;color:var(--primary);"></i>
                <h2 style="color:var(--primary);font-size:17px;margin:6px 0 2px;">SchoolMS</h2>
                <div style="border-top:2px dashed var(--border);margin:10px 0;"></div>
            </div>
            <table style="width:100%;font-size:13px;">
                <tr><td style="padding:5px 0;color:var(--text-muted);">Receipt No:</td><td style="font-weight:700;"><?= $receipt_data['receipt_no'] ?></td></tr>
                <tr><td style="padding:5px 0;color:var(--text-muted);">Student:</td><td><?= htmlspecialchars($receipt_data['fullname']) ?></td></tr>
                <tr><td style="padding:5px 0;color:var(--text-muted);">Payment Type:</td><td><?= $receipt_data['payment_type'] ?></td></tr>
                <tr><td style="padding:5px 0;color:var(--text-muted);">Term:</td><td><?= $receipt_data['term'] ?></td></tr>
                <tr><td style="padding:5px 0;color:var(--text-muted);">Session:</td><td><?= $receipt_data['session'] ?></td></tr>
                <tr><td style="padding:5px 0;color:var(--text-muted);">Date:</td><td><?= date('F d, Y', strtotime($receipt_data['payment_date'])) ?></td></tr>
            </table>
            <div style="background:var(--primary);color:#fff;border-radius:8px;padding:14px;text-align:center;margin-top:14px;">
                <div style="font-size:11px;opacity:0.7;text-transform:uppercase;">Amount</div>
                <div style="font-size:26px;font-weight:800;">₦<?= number_format($receipt_data['amount'], 2) ?></div>
                <span class="badge badge-success" style="margin-top:4px;"><?= $receipt_data['status'] ?></span>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('receiptModal')">Close</button>
            <button class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="stats-grid" style="grid-template-columns:repeat(2,1fr);">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div class="stat-info"><div class="stat-value">₦<?= number_format($total_paid, 2) ?></div><div class="stat-label">Total Paid</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fa-solid fa-clock"></i></div>
        <div class="stat-info"><div class="stat-value">₦<?= number_format($total_pending, 2) ?></div><div class="stat-label">Pending Balance</div></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><span class="card-title">Payment History</span></div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Payment Type</th><th>Amount</th><th>Term</th><th>Session</th><th>Date</th><th>Status</th><th>Receipt</th></tr></thead>
            <tbody>
            <?php if (mysqli_num_rows($payments) === 0): ?>
                <tr><td colspan="7" style="text-align:center;padding:24px;color:var(--text-muted);">No payment records found.</td></tr>
            <?php else: ?>
            <?php while ($p = mysqli_fetch_assoc($payments)):
                $bc = ['Paid'=>'badge-success','Pending'=>'badge-warning','Partial'=>'badge-info'][$p['status']] ?? 'badge-info';
            ?>
            <tr>
                <td><?= htmlspecialchars($p['payment_type']) ?></td>
                <td><strong>₦<?= number_format($p['amount'], 2) ?></strong></td>
                <td><?= $p['term'] ?></td>
                <td><?= $p['session'] ?></td>
                <td><?= date('M d, Y', strtotime($p['payment_date'])) ?></td>
                <td><span class="badge <?= $bc ?>"><?= $p['status'] ?></span></td>
                <td>
                    <a href="?receipt=<?= $p['id'] ?>" class="btn btn-outline btn-sm"><i class="fa-solid fa-receipt"></i></a>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
