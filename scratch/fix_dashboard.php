<?php
$file = 'd:/xampp/htdocs/universal/dashboards/librarian/librarian_dashboard.php';
$content = file_get_contents($file);

// 1. Update KPI Card
$oldKpi = '                        <div class="stat-label">Late Returns</div>
                        <div class="stat-value text-danger"><?= number_format($libStats[\'overdue_books\']) ?></div>';
$newKpi = '                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-label">Late Returns</div>
                                <div class="stat-value text-danger"><?= number_format($libStats[\'overdue_books\']) ?></div>
                            </div>
                            <?php if ($libStats[\'overdue_books\'] > 0): ?>
                                <button id="sendRemindersBtn" class="btn btn-sm btn-outline-danger border-2 fw-bold px-3 shadow-sm" style="border-radius: 8px;">
                                    <i class="bi bi-bell-fill me-1"></i>Remind
                                </button>
                            <?php endif; ?>
                        </div>';
$content = str_replace($oldKpi, $newKpi, $content);

// 2. Update Feed Header
$oldHeader = '                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold m-0 d-flex align-items-center">
                            <span class="pulse-live"></span>
                            <i class="bi bi-activity me-2 text-primary"></i>Live Operations Feed
                        </h5>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3">
                            Last 10 Staff Actions
                        </span>
                    </div>';
$newHeader = '                    <div class="card-header bg-white border-0 py-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <h5 class="fw-bold m-0 d-flex align-items-center">
                            <span class="pulse-live"></span>
                            <i class="bi bi-activity me-2 text-primary"></i>Live Operations Feed
                        </h5>
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-calendar-event"></i></span>
                                <input type="date" id="feedStart" class="form-control border-start-0 ps-0" placeholder="Start">
                                <span class="input-group-text bg-light px-1">to</span>
                                <input type="date" id="feedEnd" class="form-control" placeholder="End">
                                <button class="btn btn-primary" id="applyFilterBtn"><i class="bi bi-funnel-fill"></i></button>
                            </div>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3">
                                Last 10 Staff Actions
                            </span>
                        </div>
                    </div>';
$content = str_replace($oldHeader, $newHeader, $content);

// 3. Update JS Polling
$oldJs = '    // 3. Real-Time Live Feed Polling
    function refreshLiveFeed() {
        fetch(\'ajax_operations_feed.php\')
            .then(response => response.text())
            .then(html => {
                const feedBody = document.getElementById(\'liveFeedBody\');
                if (feedBody) {
                    feedBody.innerHTML = html;
                }
            })
            .catch(error => console.warn(\'Live feed update failed:\', error));
    }

    // Refresh every 15 seconds
    setInterval(refreshLiveFeed, 15000);';
$newJs = '    // 3. Real-Time Live Feed Polling & Filtering
    function refreshLiveFeed() {
        const start = document.getElementById(\'feedStart\')?.value || \'\';
        const end = document.getElementById(\'feedEnd\')?.value || \'\';
        const url = `ajax_operations_feed.php?start=${start}&end=${end}`;

        fetch(url)
            .then(response => response.text())
            .then(html => {
                const feedBody = document.getElementById(\'liveFeedBody\');
                if (feedBody) {
                    feedBody.innerHTML = html;
                }
            })
            .catch(error => console.warn(\'Live feed update failed:\', error));
    }

    if (document.getElementById(\'applyFilterBtn\')) {
        document.getElementById(\'applyFilterBtn\').addEventListener(\'click\', refreshLiveFeed);
    }

    // 4. Overdue Reminders Implementation
    const sendRemindersBtn = document.getElementById(\'sendRemindersBtn\');
    if (sendRemindersBtn) {
        sendRemindersBtn.addEventListener(\'click\', function() {
            this.disabled = true;
            this.innerHTML = \'<i class="bi bi-hourglass-split me-1"></i>Sending...\';
            
            fetch(\'ajax_bulk_reminders.php\')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        refreshLiveFeed(); 
                    } else {
                        alert(\'Error: \' + data.message);
                    }
                })
                .finally(() => {
                    this.disabled = false;
                    this.innerHTML = \'<i class="bi bi-bell-fill me-1"></i>Remind\';
                });
        });
    }

    setInterval(refreshLiveFeed, 20000);';
$content = str_replace($oldJs, $newJs, $content);

file_put_contents($file, $content);
echo "Replacement completed\n";
?>
