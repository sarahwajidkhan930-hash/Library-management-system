<?php
/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  SNIPPET 1 — ROLE-BASED ACCESS CONTROL (RBAC)               ║
 * ║  Paste at the TOP of: librarian_dashboard.php               ║
 * ║  (after session_start / require header)                     ║
 * ╚══════════════════════════════════════════════════════════════╝
 *
 * The sidebar already hides pages via the DB role_access table.
 * This snippet adds a PHP-level gate so the "Audit Trail" quick-
 * action card and any "Delete" operations in the dashboard body
 * are ALSO hidden from assistant_manager at the PHP layer.
 *
 * Usage:  <?php if ($isLibrarian): ?> ... <?php endif; ?>
 */

// ── Role helpers — add once near top of any page ──────────────
$currentRole  = $_SESSION['role']  ?? '';
$currentUser  = $_SESSION['user_id'] ?? 0;
$isLibrarian  = in_array($currentRole, ['librarian', 'super_admin']);
$isAssistant  = ($currentRole === 'assistant_manager');
$isPrivileged = ($isLibrarian || $isAssistant);   // both can operate

// ── Audit Trail quick-action guard (in librarian_dashboard.php) ─
// Wrap the Audit Trail card like this in the dashboard HTML:
//
//   <?php if ($isLibrarian): ?>
//       <div class="col-md-3">
//           <a href="audit_trail.php" class="card glass-card ...">
//               ... Audit Trail card HTML ...
//           </a>
//       </div>
//   <?php endif; ?>
//
// The sidebar link is already blocked via DB (assistant_manager
// has NO role_access row for the Digital Audit Trail page).
// This PHP guard adds a second layer of protection on the card.
