<?php
/**
 * Student & Faculty "My Clearances" Portal
 * Authoritative Institutional Green Design System (stripe-DESIGN.md)
 * Empowers students to view assessed damage liabilities, submit official cashier receipts,
 * and track verification clearance status in real-time.
 * 
 * Strict OWASP Compliance: CSRF protection, HTML entity encoding, XSS & SQLi safe.
 */
$title = 'My Clearances';
$breadcrumb = 'Clearances';

require __DIR__ . '/../layouts/shell.php';
?>

<!-- ── Page Header ────────────────────────────────────────── -->
<div class="section-header" style="margin-bottom:var(--space-xl);">
  <div>
    <h1 class="section-title" style="display:flex;align-items:center;gap:10px;">
      <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-primary);display:inline-block;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
      My Clearances &amp; Damage Liabilities
    </h1>
    <div class="section-subtitle">View reported equipment damage assessments, submit official cashier payment receipts, and restore your borrowing privileges.</div>
  </div>
  <div style="display:flex;gap:var(--space-sm);flex-wrap:wrap;align-items:center;">
    <a href="<?= url('/scan') ?>" class="btn btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:6px;border-radius:9999px;">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><path d="M3 9h18"></path><path d="M3 15h18"></path><path d="M9 3v18"></path><path d="M15 3v18"></path></svg>
      Scanner Station
    </a>
    <a href="<?= url('/records') ?>" class="btn btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:6px;border-radius:9999px;">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
      My Borrowal Records
    </a>
  </div>
</div>

<!-- ── Summary Metric Strip ───────────────────────────────── -->
<div class="stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(210px, 1fr));gap:var(--space-md);margin-bottom:var(--space-xl);">
  <?php
  $props = [
    'title'    => 'Total Remaining Balance',
    'value'    => '₱' . number_format($totalUnsettledFee, 2),
    'icon'     => 'alert-triangle',
    'color'    => ($totalUnsettledFee > 0 ? 'ruby' : 'green'),
    'subtitle' => ($activeHoldCount > 0 ? "{$activeHoldCount} incident liability active" : 'No outstanding liabilities')
  ];
  require __DIR__ . '/../components/stat-card.php';

  $props = [
    'title'    => 'Partially Paid',
    'value'    => $partiallyPaidCount,
    'icon'     => 'credit-card',
    'color'    => ($partiallyPaidCount > 0 ? 'blue' : 'gray'),
    'subtitle' => 'Installments credited to balance'
  ];
  require __DIR__ . '/../components/stat-card.php';

  $props = [
    'title'    => 'Pending Verification',
    'value'    => $pendingVerifCount,
    'icon'     => 'clock',
    'color'    => ($pendingVerifCount > 0 ? 'gold' : 'blue'),
    'subtitle' => 'Receipts uploaded & awaiting review'
  ];
  require __DIR__ . '/../components/stat-card.php';

  $props = [
    'title'    => 'Cleared Records',
    'value'    => $clearedCount,
    'icon'     => 'user-check',
    'color'    => 'green',
    'subtitle' => 'Damage incidents settled and verified'
  ];
  require __DIR__ . '/../components/stat-card.php';
  ?>
</div>

<!-- ── Main Incidents Content ─────────────────────────────── -->
<?php if (empty($incidents)): ?>
  <!-- Reusable Empty State -->
  <div style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--radius-lg);padding:64px 24px;text-align:center;box-shadow:var(--shadow-sm);">
    <div style="width:56px;height:56px;border-radius:50%;background:rgba(22, 101, 52, 0.08);color:var(--color-primary);display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
      <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><polyline points="9 12 11 14 15 10"></polyline></svg>
    </div>
    <h3 style="font-size:18px;font-weight:600;color:var(--color-ink);margin-bottom:6px;">Your Clearance is Clean</h3>
    <p style="font-size:14px;color:var(--color-ink-mute);max-width:500px;margin:0 auto 20px;line-height:1.5;">
      You do not have any reported equipment damages or pending payment liabilities. Your borrowing privileges are in good standing!
    </p>
    <a href="<?= url('/scan') ?>" class="btn btn-primary btn-sm" style="border-radius:9999px;padding:8px 20px;">Scan Equipment to Borrow</a>
  </div>
<?php else: ?>

  <div style="display:flex;flex-direction:column;gap:var(--space-lg);">
    <?php foreach ($incidents as $inc): 
      $status = $inc['incident_status'];
      $isCleared = ($status === 'cleared');
      $isPendingVerif = ($status === 'pending_verification');
      $isPartiallyPaid = ($status === 'partially_paid');
      $isRejected = ($status === 'rejected');
      $isAwaiting = ($status === 'awaiting_payment' || $status === 'open_hold');
      
      $estCost = $inc['estimated_cost'] !== null ? (float)$inc['estimated_cost'] : 0.0;
      $amtPaid = $inc['amount_paid'] !== null ? (float)$inc['amount_paid'] : 0.0;
      $remBalance = $inc['remaining_balance'] !== null ? (float)$inc['remaining_balance'] : max(0.0, $estCost - $amtPaid);
      
      $cardBorder = $isCleared 
        ? 'border:1px solid var(--color-hairline);' 
        : ($isRejected ? 'border:2px solid rgba(220, 38, 38, 0.3);background:rgba(220, 38, 38, 0.02);' : ($isPartiallyPaid ? 'border:2px solid rgba(59, 130, 246, 0.35);background:rgba(59, 130, 246, 0.01);' : ($isPendingVerif ? 'border:1px solid rgba(217, 119, 6, 0.3);background:rgba(217, 119, 6, 0.02);' : 'border:2px solid rgba(220, 38, 38, 0.25);background:var(--color-canvas);')));
    ?>
      <div class="incident-card" style="border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);padding:24px;background:var(--color-canvas);<?= $cardBorder ?>position:relative;overflow:hidden;">
        
        <!-- Status Indicator Bar -->
        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
          <div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
              <h2 style="font-size:17px;font-weight:600;color:var(--color-ink);margin:0;">
                <?= htmlspecialchars($inc['item_name'], ENT_QUOTES, 'UTF-8') ?>
              </h2>
              <span style="font-size:12px;font-family:monospace;color:var(--color-primary);background:var(--color-primary-bg-subdued-hover, #dcfce7);padding:2px 8px;border-radius:4px;font-weight:600;">
                <?= htmlspecialchars($inc['barcode'], ENT_QUOTES, 'UTF-8') ?>
              </span>
              <?php if (!empty($inc['property_number'])): ?>
                <span style="font-size:12px;font-family:monospace;color:var(--color-ink-mute);">
                  SN: <?= htmlspecialchars($inc['property_number'], ENT_QUOTES, 'UTF-8') ?>
                </span>
              <?php endif; ?>
            </div>
            <div style="font-size:12px;color:var(--color-ink-mute);margin-top:4px;">
              Reported on <?= date('M j, Y — g:i A', strtotime($inc['created_at'])) ?> by Custodian <?= htmlspecialchars($inc['reported_by_name'] ?? 'Staff', ENT_QUOTES, 'UTF-8') ?>
            </div>
          </div>

          <div>
            <?php
            $props = ['status' => $status];
            require __DIR__ . '/../components/status-badge.php';
            ?>
          </div>
        </div>

        <!-- Middle Grid: Damage Details & Financial Assessment -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:16px;background:var(--color-canvas-soft, #f8fafc);padding:16px;border-radius:var(--radius-md);margin-bottom:16px;">
          
          <!-- Inspection Findings -->
          <div>
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:var(--color-ink-mute);font-weight:600;margin-bottom:4px;">
              Inspection Findings &amp; Damage Severity
            </div>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
              <span class="badge" style="font-size:11px;font-weight:600;background:<?= $inc['damage_condition'] === 'damaged' ? 'rgba(217, 119, 6, 0.15);color:var(--color-gold-deep)' : 'rgba(220, 38, 38, 0.15);color:var(--color-ruby)' ?>;padding:2px 8px;border-radius:4px;">
                <?= $inc['damage_condition'] === 'damaged' ? 'Damaged (Repairable)' : 'Not Working (Defective)' ?>
              </span>
            </div>
            <p style="font-size:13px;color:var(--color-ink-secondary);line-height:1.4;margin:0;">
              &ldquo;<?= htmlspecialchars($inc['damage_description'], ENT_QUOTES, 'UTF-8') ?>&rdquo;
            </p>
          </div>

          <!-- Financial Liability & Balance Breakdown -->
          <div>
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:var(--color-ink-mute);font-weight:600;margin-bottom:6px;">
              Financial Liability &amp; Balance Breakdown
            </div>
            <?php if ($estCost > 0): ?>
              <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(105px, 1fr));gap:8px;margin-bottom:8px;">
                <div style="background:#ffffff;border:1px solid var(--color-hairline);border-radius:var(--radius-sm);padding:8px 10px;">
                  <div style="font-size:10px;color:var(--color-ink-mute);font-weight:600;text-transform:uppercase;">Assessed Total</div>
                  <div style="font-size:15px;font-weight:600;color:var(--color-ink);font-feature-settings:'tnum';">
                    ₱<?= number_format($estCost, 2) ?>
                  </div>
                </div>
                <div style="background:#ffffff;border:1px solid var(--color-hairline);border-radius:var(--radius-sm);padding:8px 10px;">
                  <div style="font-size:10px;color:var(--color-ink-mute);font-weight:600;text-transform:uppercase;">Paid / Credited</div>
                  <div style="font-size:15px;font-weight:600;color:var(--color-primary);font-feature-settings:'tnum';">
                    ₱<?= number_format($amtPaid, 2) ?>
                  </div>
                </div>
                <div style="background:#ffffff;border:1px solid <?= $remBalance > 0 ? 'rgba(220, 38, 38, 0.35)' : 'var(--color-hairline)' ?>;border-radius:var(--radius-sm);padding:8px 10px;">
                  <div style="font-size:10px;color:<?= $remBalance > 0 ? 'var(--color-ruby)' : 'var(--color-primary)' ?>;font-weight:600;text-transform:uppercase;">Remaining Due</div>
                  <div style="font-size:15px;font-weight:700;color:<?= $remBalance > 0 ? 'var(--color-ruby)' : 'var(--color-primary)' ?>;font-feature-settings:'tnum';">
                    ₱<?= number_format($remBalance, 2) ?>
                  </div>
                </div>
              </div>

              <?php 
                $pendingPay = null;
                if (!empty($inc['payments'])) {
                    foreach ($inc['payments'] as $p) {
                        if ($p['payment_status'] === 'pending') {
                            $pendingPay = $p;
                            break;
                        }
                    }
                }
              ?>
              <?php if ($isPartiallyPaid): ?>
                <div style="font-size:11px;color:var(--color-ruby);font-weight:500;">
                  ⚠️ Partial payment credited (₱<?= number_format($amtPaid, 2) ?>). Pay remaining ₱<?= number_format($remBalance, 2) ?> at the cashier desk to restore your borrowing privileges.
                </div>
              <?php elseif ($isCleared): ?>
                <div style="font-size:11px;color:var(--color-primary);font-weight:500;">
                  ✓ Liability settled in full. Borrowing privileges restored.
                </div>
              <?php elseif ($isPendingVerif): ?>
                <div style="font-size:11px;color:var(--color-gold-deep);font-weight:500;">
                  ⏳ Receipt <?= $pendingPay ? '(₱' . number_format((float)$pendingPay['amount_paid'], 2) . ')' : '' ?> submitted and currently under review by custodian. Editing is locked until verification is complete.
                </div>
              <?php elseif ($isRejected): ?>
                <div style="font-size:11px;color:var(--color-ruby);font-weight:500;">
                  ✗ Submitted receipt was rejected<?= !empty($inc['rejection_reason']) ? ': &ldquo;' . htmlspecialchars($inc['rejection_reason'], ENT_QUOTES, 'UTF-8') . '&rdquo;' : '' ?>. Please upload a valid official cashier receipt below.
                </div>
              <?php else: ?>
                <div style="font-size:11px;color:var(--color-ink-mute);">
                  Present this assessment at the university cashier desk with your Student ID.
                </div>
              <?php endif; ?>

            <?php else: ?>
              <div style="font-size:16px;font-weight:500;color:var(--color-ink-mute);">
                Fee Pending Assessment
              </div>
              <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">
                Custodian is evaluating replacement / repair quotations.
              </div>
            <?php endif; ?>
          </div>

        </div>

        <!-- Payment History & Installment Ledger -->
        <?php if (!empty($inc['payments'])): ?>
          <div style="background:#ffffff;border:1px solid var(--color-hairline);border-radius:var(--radius-md);overflow:hidden;margin-bottom:16px;">
            <div style="padding:8px 14px;background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:var(--color-ink);display:flex;align-items:center;justify-content:space-between;">
              <span style="display:flex;align-items:center;gap:6px;">
                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                Payment History &amp; Installment Ledger (<?= count($inc['payments']) ?> <?= count($inc['payments']) === 1 ? 'record' : 'records' ?>)
              </span>
              <span style="font-size:11px;color:var(--color-ink-mute);font-weight:normal;text-transform:none;">
                Total Credited: <strong style="color:var(--color-primary);font-family:monospace;">₱<?= number_format($amtPaid, 2) ?></strong>
              </span>
            </div>
            <div style="overflow-x:auto;">
              <table style="width:100%;font-size:12px;border-collapse:collapse;text-align:left;">
                <thead>
                  <tr style="border-bottom:1px solid var(--color-hairline);color:var(--color-ink-mute);font-size:10px;text-transform:uppercase;letter-spacing:0.5px;background:rgba(0,0,0,0.01);">
                    <th style="padding:6px 12px;font-weight:600;">O.R. #</th>
                    <th style="padding:6px 12px;font-weight:600;">Payment Date</th>
                    <th style="padding:6px 12px;font-weight:600;">Amount</th>
                    <th style="padding:6px 12px;font-weight:600;">Status</th>
                    <th style="padding:6px 12px;font-weight:600;">Receipt</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($inc['payments'] as $pay): ?>
                    <tr style="border-bottom:1px solid var(--color-hairline);">
                      <td style="padding:6px 12px;font-family:monospace;font-weight:600;color:var(--color-ink);">
                        <?= htmlspecialchars($pay['receipt_number'], ENT_QUOTES, 'UTF-8') ?>
                      </td>
                      <td style="padding:6px 12px;color:var(--color-ink-secondary);">
                        <?= htmlspecialchars($pay['payment_date'], ENT_QUOTES, 'UTF-8') ?>
                      </td>
                      <td style="padding:6px 12px;font-family:monospace;font-weight:600;color:var(--color-ink);">
                        ₱<?= number_format((float)$pay['amount_paid'], 2) ?>
                      </td>
                      <td style="padding:6px 12px;">
                        <?php if ($pay['payment_status'] === 'verified'): ?>
                          <span class="badge badge-approved" style="font-size:10px;padding:2px 6px;">Verified</span>
                        <?php elseif ($pay['payment_status'] === 'pending'): ?>
                          <span class="badge badge-pending" style="font-size:10px;padding:2px 6px;">Pending</span>
                        <?php else: ?>
                          <span class="badge badge-rejected" style="font-size:10px;padding:2px 6px;">Rejected</span>
                        <?php endif; ?>
                      </td>
                      <td style="padding:6px 12px;">
                        <?php if (!empty($pay['clearance_document_path'])): ?>
                          <a href="<?= url('/clearances/document?id=' . (int)$inc['incident_id'] . '&payment_id=' . (int)$pay['payment_id']) ?>" target="_blank" rel="noopener noreferrer" style="color:var(--color-primary);text-decoration:none;font-weight:500;display:inline-flex;align-items:center;gap:4px;">
                            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path></svg>
                            Receipt
                          </a>
                        <?php else: ?>
                          <span style="color:var(--color-ink-mute);">&mdash;</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif; ?>

        <!-- Rejection Feedback Alert (If Rejected) -->
        <?php if (!empty($inc['rejection_reason']) && !$isPendingVerif && !$isCleared): ?>
          <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:var(--radius-md);padding:14px 16px;margin-bottom:16px;display:flex;align-items:flex-start;gap:12px;">
            <div style="color:var(--color-ruby);flex-shrink:0;margin-top:1px;">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            </div>
            <div style="flex:1;">
              <strong style="color:#991b1b;font-size:13px;display:block;">Custodian Rejection Feedback:</strong>
              <div style="color:#7f1d1d;font-size:13px;margin-top:2px;line-height:1.4;">
                <?= htmlspecialchars($inc['rejection_reason'], ENT_QUOTES, 'UTF-8') ?>
              </div>
              <div style="font-size:12px;color:#991b1b;margin-top:6px;font-weight:500;">
                Please review the feedback above, secure a valid Official Receipt, and submit again below.
              </div>
            </div>
          </div>
        <?php endif; ?>

        <!-- Submission Status & Actions Footer -->
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;padding-top:12px;border-top:1px solid var(--color-hairline);">
          
          <div>
            <?php if (!empty($inc['receipt_number'])): ?>
              <div style="font-size:12px;color:var(--color-ink-secondary);">
                <strong>Latest Submitted Receipt:</strong> O.R. #<span style="font-family:monospace;font-weight:600;"><?= htmlspecialchars($inc['receipt_number'], ENT_QUOTES, 'UTF-8') ?></span>
                &bull; Amount: <span style="font-family:monospace;font-weight:600;">₱<?= number_format((float)($inc['amount_paid'] ?? 0), 2) ?></span>
                <?php if (!empty($inc['payment_date'])): ?>
                  &bull; Date: <?= htmlspecialchars($inc['payment_date'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
              </div>
              <?php if (!empty($inc['clearance_document_path'])): ?>
                <div style="margin-top:4px;">
                  <a href="<?= url('/clearances/document?id=' . (int)$inc['incident_id']) ?>" target="_blank" rel="noopener noreferrer" style="font-size:12px;color:var(--color-primary);text-decoration:none;display:inline-flex;align-items:center;gap:4px;font-weight:500;">
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    View Uploaded Receipt Document
                  </a>
                </div>
              <?php endif; ?>
            <?php else: ?>
              <div style="font-size:12px;color:var(--color-ruby);font-weight:500;">
                ⏳ Payment Receipt Not Yet Submitted. Settle at university cashier and upload receipt below.
              </div>
            <?php endif; ?>
          </div>

          <div style="display:flex;align-items:center;gap:8px;">
            <?php if ($isCleared): ?>
              <span class="badge badge-approved" style="padding:6px 14px;border-radius:9999px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:6px;">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                Cleared on <?= !empty($inc['cleared_at']) ? date('M j, Y', strtotime($inc['cleared_at'])) : 'N/A' ?>
              </span>
            <?php elseif ($isPendingVerif): ?>
              <span class="badge" style="padding:6px 14px;border-radius:9999px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:6px;background:#fef3c7;color:#92400e;border:1px solid #fde68a;">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                Under Review (Pending Approval)
              </span>
            <?php elseif ($isPartiallyPaid): ?>
              <button 
                type="button" 
                class="btn btn-primary btn-sm" 
                style="border-radius:9999px;padding:8px 18px;font-size:13px;font-weight:600;display:inline-flex;align-items:center;gap:6px;box-shadow:0 1px 3px rgba(22, 101, 52, 0.2);"
                onclick="openSubmitReceiptModal(<?= (int)$inc['incident_id'] ?>)"
              >
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                Pay Remaining Balance (₱<?= number_format($remBalance, 2) ?>)
              </button>
            <?php else: ?>
              <button 
                type="button" 
                class="btn btn-primary btn-sm" 
                style="border-radius:9999px;padding:8px 18px;font-size:13px;font-weight:600;display:inline-flex;align-items:center;gap:6px;box-shadow:0 1px 3px rgba(22, 101, 52, 0.2);"
                onclick="openSubmitReceiptModal(<?= (int)$inc['incident_id'] ?>)"
              >
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                <?= $isRejected ? 'Re-Upload Valid Receipt' : 'Submit Official Receipt' ?>
              </button>
            <?php endif; ?>
          </div>

        </div>

      </div>
    <?php endforeach; ?>
  </div>

<?php endif; ?>

<!-- ── Reusable Submit Receipt Modal ───────────────────────── -->
<div id="modal-submit-receipt" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-submit-receipt-title">
  <div class="modal-panel md" style="background:#ffffff;border-radius:var(--radius-lg);width:100%;max-width:540px;box-shadow:0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.04);overflow:hidden;">
    
    <div class="modal-header" style="padding:18px 24px;border-bottom:1px solid var(--color-hairline);display:flex;align-items:center;justify-content:space-between;background:var(--color-canvas-soft);">
      <div>
        <h2 class="modal-title" id="modal-submit-receipt-title" style="font-size:17px;font-weight:600;color:var(--color-ink);margin:0;">
          Submit Payment Receipt
        </h2>
        <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;" id="modal-asset-subtitle">
          Uploading official proof of damage payment
        </div>
      </div>
      <button type="button" class="modal-close" data-modal-close="modal-submit-receipt" onclick="closeSubmitReceiptModal()" aria-label="Close" style="background:none;border:none;color:var(--color-ink-mute);cursor:pointer;padding:4px;">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>

    <form id="form-submit-receipt" onsubmit="handleReceiptSubmit(event)" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="incident_id" id="input-incident-id" value="">

      <div class="modal-body" style="padding:20px 24px;max-height:calc(85vh - 140px);overflow-y:auto;">
        
        <!-- Assessed & Balance Due Banner -->
        <div id="modal-assessed-banner" style="background:var(--color-canvas-soft, #f8fafc);border:1px solid var(--color-hairline);border-radius:var(--radius-md);padding:12px 16px;margin-bottom:18px;">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
            <div style="font-size:12px;color:var(--color-ink-secondary);">Total Assessed Fee:</div>
            <div id="modal-assessed-amount" style="font-size:14px;font-weight:600;color:var(--color-ink);font-feature-settings:'tnum';">₱0.00</div>
          </div>
          <div id="modal-paid-row" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
            <div style="font-size:12px;color:var(--color-ink-secondary);">Verified / Credited to Date:</div>
            <div id="modal-paid-amount" style="font-size:14px;font-weight:600;color:var(--color-primary);font-feature-settings:'tnum';">₱0.00</div>
          </div>
          <div id="modal-remaining-row" style="display:flex;align-items:center;justify-content:space-between;padding-top:6px;border-top:1px dashed var(--color-hairline);">
            <div style="font-size:12px;font-weight:600;color:var(--color-ruby);">Remaining Balance Due:</div>
            <div id="modal-remaining-amount" style="font-size:18px;font-weight:700;color:var(--color-ruby);font-feature-settings:'tnum';">₱0.00</div>
          </div>
        </div>

        <!-- O.R. Number & Amount Fields -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
          <div>
            <label class="form-label" for="receipt-number" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;color:var(--color-ink);">
              Official Receipt (O.R.) # <span style="color:var(--color-ruby);">*</span>
            </label>
            <input 
              type="text" 
              id="receipt-number" 
              name="receipt_number" 
              required 
              placeholder="e.g. 9482103" 
              class="form-input" 
              style="width:100%;font-size:13px;font-family:monospace;"
            >
          </div>
          <div>
            <label class="form-label" for="amount-paid" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;color:var(--color-ink);">
              Price on Receipt (₱) <span style="color:var(--color-ruby);">*</span>
            </label>
            <input 
              type="number" 
              step="0.01" 
              min="0.01" 
              id="amount-paid" 
              name="amount_paid" 
              required 
              placeholder="0.00" 
              class="form-input" 
              style="width:100%;font-size:13px;font-family:monospace;"
            >
            <div style="font-size:11px;color:var(--color-ink-mute);margin-top:4px;">
              Partial payments accepted. Balance updates on review.
            </div>
          </div>
        </div>

        <!-- Payment Date Field -->
        <div class="form-group" style="margin-bottom:16px;">
          <label class="form-label" for="payment-date" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;color:var(--color-ink);">
            Date of Payment (as printed on receipt) <span style="color:var(--color-ruby);">*</span>
          </label>
          <input 
            type="date" 
            id="payment-date" 
            name="payment_date" 
            required 
            value="<?= date('Y-m-d') ?>"
            max="<?= date('Y-m-d') ?>"
            class="form-input" 
            style="width:100%;font-size:13px;"
          >
        </div>

        <!-- Receipt File Upload Dropzone -->
        <div class="form-group" style="margin-bottom:16px;">
          <label class="form-label" for="receipt-document" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;color:var(--color-ink);">
            Receipt Photo or PDF Scan <span id="req-star" style="color:var(--color-ruby);">*</span>
          </label>
          
          <div class="file-upload-dropzone" id="receipt-dropzone" style="border:2px dashed var(--color-hairline, #cbd5e1);border-radius:var(--radius-md);padding:18px 14px;text-align:center;background:var(--color-canvas-soft);cursor:pointer;position:relative;transition:border-color 0.15s ease;">
            <input 
              type="file" 
              id="receipt-document" 
              name="receipt_document" 
              accept=".pdf,application/pdf,image/jpeg,image/png,image/webp" 
              style="position:absolute;top:0;left:0;width:100%;height:100%;opacity:0;cursor:pointer;z-index:2;"
              onchange="handleReceiptFileSelected(event)"
            >
            
            <div id="dropzone-prompt" style="display:flex;flex-direction:column;align-items:center;gap:6px;pointer-events:none;">
              <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" style="color:var(--color-primary);"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
              <div style="font-size:13px;font-weight:600;color:var(--color-ink);">Click or drag official cashier receipt here</div>
              <div style="font-size:11px;color:var(--color-ink-mute);">Supported formats: PDF, JPG, PNG, WEBP (Max 8 MB)</div>
            </div>

            <!-- Preview Card -->
            <div id="dropzone-preview" style="display:none;align-items:center;justify-content:center;gap:10px;font-size:12px;color:var(--color-ink);">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--color-primary);"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
              <span id="preview-filename" style="font-weight:600;"></span>
              <span id="preview-filesize" style="font-size:11px;color:var(--color-ink-mute);"></span>
              <button type="button" onclick="clearSelectedReceiptFile(event)" style="background:none;border:none;color:var(--color-ruby);cursor:pointer;font-size:11px;margin-left:6px;text-decoration:underline;z-index:3;position:relative;">Remove</button>
            </div>
          </div>
          <div id="existing-file-notice" style="display:none;font-size:11px;color:var(--color-ink-mute);margin-top:5px;">
            A document is already on file. Uploading a new file will replace the current one.
          </div>
        </div>

        <!-- Student Optional Notes -->
        <div class="form-group" style="margin-bottom:6px;">
          <label class="form-label" for="student-notes" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;color:var(--color-ink);">
            Remarks / Payment Notes (Optional)
          </label>
          <textarea 
            id="student-notes" 
            name="student_notes" 
            class="form-input" 
            rows="2" 
            placeholder="e.g. Paid at window 2, official receipt attached..." 
            style="width:100%;font-size:13px;resize:vertical;"
          ></textarea>
        </div>

      </div>

      <!-- Modal Footer -->
      <div class="modal-footer" style="padding:14px 24px;border-top:1px solid var(--color-hairline);background:var(--color-canvas-soft);display:flex;align-items:center;justify-content:flex-end;gap:10px;">
        <button type="button" class="btn btn-ghost btn-sm" data-modal-close="modal-submit-receipt" onclick="closeSubmitReceiptModal()" style="border-radius:9999px;">Cancel</button>
        <button type="submit" id="btn-submit-receipt-save" class="btn btn-primary btn-sm" style="border-radius:9999px;padding:7px 20px;font-weight:600;display:inline-flex;align-items:center;gap:6px;">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
          Submit for Custodian Verification
        </button>
      </div>

    </form>
  </div>
</div>

<script>
// Incidents map for zero-escaping-error lookups
const studentIncidents = <?= json_encode(array_column($incidents, null, 'incident_id'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?: '{}' ?>;
let currentModalIncident = null;

function openSubmitReceiptModal(incidentOrId) {
  const incident = (typeof incidentOrId === 'object' && incidentOrId !== null)
    ? incidentOrId
    : (studentIncidents[incidentOrId] || null);

  if (!incident) {
    console.error('Incident data not found for:', incidentOrId);
    return;
  }

  if (incident.incident_status === 'pending_verification') {
    if (window.Toast) {
      window.Toast.info('Under Review', 'Your submitted receipt is currently under review by the custodian and cannot be modified.');
    } else if (window.alertDialog) {
      window.alertDialog('Your submitted receipt is currently under review by the custodian and cannot be modified.', 'Submission Under Review');
    }
    return;
  }

  if (incident.incident_status === 'cleared') {
    if (window.Toast) {
      window.Toast.info('Already Cleared', 'This liability has already been verified and cleared.');
    }
    return;
  }

  currentModalIncident = incident;
  document.getElementById('input-incident-id').value = incident.incident_id;
  document.getElementById('modal-asset-subtitle').textContent = `Submitting proof for ${incident.item_name || 'Asset'} (${incident.barcode || ''})`;
  
  const assessed = incident.estimated_cost !== null && !isNaN(incident.estimated_cost) ? Number(incident.estimated_cost) : 0;
  const amtPaid = incident.amount_paid !== null && !isNaN(incident.amount_paid) ? Number(incident.amount_paid) : 0;
  const remBalance = incident.remaining_balance !== null && !isNaN(incident.remaining_balance) ? Number(incident.remaining_balance) : Math.max(0, assessed - amtPaid);

  document.getElementById('modal-assessed-amount').textContent = '₱' + assessed.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  document.getElementById('modal-paid-amount').textContent = '₱' + amtPaid.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  document.getElementById('modal-remaining-amount').textContent = '₱' + remBalance.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  document.getElementById('modal-paid-row').style.display = amtPaid > 0 ? 'flex' : 'none';

  // Pre-fill amount paid: default to remaining balance if > 0, otherwise assessed
  const amountInput = document.getElementById('amount-paid');
  if (remBalance > 0) {
    amountInput.value = remBalance.toFixed(2);
  } else if (assessed > 0) {
    amountInput.value = assessed.toFixed(2);
  } else {
    amountInput.value = '';
  }

  document.getElementById('receipt-number').value = '';
  document.getElementById('payment-date').value = new Date().toISOString().split('T')[0];
  document.getElementById('student-notes').value = '';

  document.getElementById('existing-file-notice').style.display = 'none';
  document.getElementById('req-star').style.display = 'inline';
  document.getElementById('receipt-document').required = true;

  // Reset file input
  clearSelectedReceiptFile();

  // Open modal using system Modal engine
  if (window.Modal && typeof window.Modal.open === 'function') {
    window.Modal.open('modal-submit-receipt');
  } else {
    const modal = document.getElementById('modal-submit-receipt');
    if (modal) {
      if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
      }
      modal.classList.add('open');
      modal.style.display = 'flex';
    }
  }
}

function closeSubmitReceiptModal() {
  if (window.Modal && typeof window.Modal.close === 'function') {
    window.Modal.close('modal-submit-receipt');
  } else {
    const modal = document.getElementById('modal-submit-receipt');
    if (modal) {
      modal.classList.remove('open');
      modal.style.display = 'none';
    }
  }
  currentModalIncident = null;
}

function handleReceiptFileSelected(e) {
  const file = e.target.files?.[0];
  if (!file) return;

  if (file.size > 8 * 1024 * 1024) {
    clearSelectedReceiptFile();
    if (window.alertDialog) {
      window.alertDialog('The selected file exceeds the maximum size limit of 8 MB. Please choose a smaller image or compressed PDF.', 'File Size Limit Exceeded');
    } else if (window.Toast) {
      window.Toast.warning('File Too Large', 'File exceeds maximum size of 8 MB.');
    }
    return;
  }

  document.getElementById('dropzone-prompt').style.display = 'none';
  const preview = document.getElementById('dropzone-preview');
  preview.style.display = 'flex';
  document.getElementById('preview-filename').textContent = file.name;
  document.getElementById('preview-filesize').textContent = `(${(file.size / (1024 * 1024)).toFixed(2)} MB)`;
}

function clearSelectedReceiptFile(e) {
  if (e) {
    e.preventDefault();
    e.stopPropagation();
  }
  const input = document.getElementById('receipt-document');
  input.value = '';
  document.getElementById('dropzone-prompt').style.display = 'flex';
  document.getElementById('dropzone-preview').style.display = 'none';
  document.getElementById('preview-filename').textContent = '';
  document.getElementById('preview-filesize').textContent = '';
}

function handleReceiptSubmit(e) {
  e.preventDefault();
  const form = document.getElementById('form-submit-receipt');
  const receiptNumInput = document.getElementById('receipt-number');
  const amountPaidInput = document.getElementById('amount-paid') || document.getElementById('receipt-amount');

  const receiptNum = receiptNumInput ? receiptNumInput.value.trim() : '';
  const amountPaid = amountPaidInput ? amountPaidInput.value.trim() : '';

  if (!receiptNum || !amountPaid) {
    if (window.Toast) {
      window.Toast.warning('Missing Required Fields', 'Please enter your Official Receipt Number and the amount paid.');
    }
    return;
  }

  const formattedAmt = parseFloat(amountPaid) ? '₱' + parseFloat(amountPaid).toFixed(2) : amountPaid;
  window.confirmAction(
    `Submit Official Receipt #${receiptNum} (${formattedAmt}) to the Property Custodian for clearance verification?`,
    function() {
      executeReceiptSubmission(form);
    },
    'Submit Receipt for Clearance',
    'Submit for Review',
    false
  );
}

function executeReceiptSubmission(form) {
  const btn = document.getElementById('btn-submit-receipt-save');
  const origText = btn.innerHTML;

  btn.disabled = true;
  btn.innerHTML = `<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite;"><circle cx="12" cy="12" r="10" stroke-dasharray="32" stroke-dashoffset="10"></circle></svg> Submitting...`;

  const formData = new FormData(form);
  const endpoint = (window.appUrl ? window.appUrl('/api/incident/submit-receipt') : '<?= url('/api/incident/submit-receipt') ?>');

  fetch(endpoint, {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(res => {
    btn.disabled = false;
    btn.innerHTML = origText;

    if (res.success) {
      closeSubmitReceiptModal();
      if (window.Toast) {
        window.Toast.success('Receipt Submitted', res.message);
      }
      setTimeout(() => window.location.reload(), 1000);
    } else {
      if (window.Toast) {
        window.Toast.error('Submission Error', res.message || 'Error submitting receipt.');
      }
    }
  })
  .catch(err => {
    btn.disabled = false;
    btn.innerHTML = origText;
    if (window.Toast) {
      window.Toast.error('Connection Error', 'Failed to connect to server. Please check your network connection.');
    }
  });
}
</script>

<?php require __DIR__ . '/../layouts/shell-footer.php'; ?>
