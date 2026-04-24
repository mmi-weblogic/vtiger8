<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<style>
.lh-dashboard { padding: 24px; }
.lh-dashboard h2 { margin: 0 0 4px; font-size: 22px; }
.lh-period-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px; }
.lh-range { color:#888; font-size:13px; margin-bottom:20px; }
.lh-cards { display:flex; gap:16px; margin-bottom:24px; flex-wrap:wrap; }
.lh-card {
    flex:1; min-width:180px; border-radius:8px; padding:20px 20px 16px;
    color:#fff; display:flex; flex-direction:column; gap:6px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
}
.lh-card .card-icon { font-size:28px; opacity:0.75; }
.lh-card .card-val  { font-size:32px; font-weight:700; line-height:1; }
.lh-card .card-lbl  { font-size:13px; opacity:0.85; }
.lh-charts-row { display:flex; gap:16px; margin-bottom:20px; flex-wrap:wrap; }
.lh-chart-box {
    background:#fff; border:1px solid #e3e3e3; border-radius:8px;
    padding:18px 18px 12px; flex:1; min-width:280px;
}
.lh-chart-box h5 { margin:0 0 14px; font-size:14px; font-weight:600; color:#333; }
.lh-chart-box.wide { flex:2; min-width:420px; }
.lh-table-box {
    background:#fff; border:1px solid #e3e3e3; border-radius:8px;
    padding:18px; margin-bottom:20px; overflow-x:auto;
}
.lh-table-box h5 { margin:0 0 14px; font-size:14px; font-weight:600; color:#333; }
#productivity-table { margin:0; font-size:13px; }
#productivity-table thead th { background:#f8f8f8; border-bottom:2px solid #ddd; white-space:nowrap; }
#productivity-table td { vertical-align:middle; }
.score-bar-wrap { background:#eee; border-radius:4px; height:10px; min-width:80px; }
.score-bar      { height:10px; border-radius:4px; transition:width 0.5s; }
.grade-badge {
    display:inline-block; padding:2px 9px; border-radius:12px;
    font-size:12px; font-weight:700; color:#fff;
}
.lh-spinner { text-align:center; padding:40px; color:#aaa; font-size:16px; }
.btn-period { border-radius:4px !important; }
</style>

<div class="lh-dashboard">

    <div class="lh-period-bar">
        <h2><i class="fa fa-bar-chart"></i>&nbsp; Login Analytics</h2>
        <div class="btn-group" id="period-selector">
            <button class="btn btn-default btn-period active" data-period="day">Today</button>
            <button class="btn btn-default btn-period" data-period="week">This Week</button>
            <button class="btn btn-default btn-period" data-period="month">This Month</button>
            <button class="btn btn-default btn-period" data-period="year">This Year</button>
        </div>
    </div>
    <div class="lh-range" id="date-range">&nbsp;</div>

    <!-- Summary cards -->
    <div class="lh-cards">
        <div class="lh-card" style="background:#3498db;">
            <div class="card-icon"><i class="fa fa-sign-in"></i></div>
            <div class="card-val" id="stat-logins">—</div>
            <div class="card-lbl">Total Logins</div>
        </div>
        <div class="lh-card" style="background:#27ae60;">
            <div class="card-icon"><i class="fa fa-clock-o"></i></div>
            <div class="card-val" id="stat-hours">—</div>
            <div class="card-lbl">Hours Online</div>
        </div>
        <div class="lh-card" style="background:#e67e22;">
            <div class="card-icon"><i class="fa fa-pencil-square-o"></i></div>
            <div class="card-val" id="stat-records">—</div>
            <div class="card-lbl">Records Updated</div>
        </div>
        <div class="lh-card" style="background:#8e44ad;">
            <div class="card-icon"><i class="fa fa-users"></i></div>
            <div class="card-val" id="stat-users">—</div>
            <div class="card-lbl">Active Users</div>
        </div>
    </div>

    <div id="dashboard-spinner" class="lh-spinner">
        <i class="fa fa-spinner fa-spin"></i> Loading data…
    </div>

    <div id="dashboard-body" style="display:none;">

        <!-- Charts row 1 -->
        <div class="lh-charts-row">
            <div class="lh-chart-box">
                <h5>Logins per User</h5>
                <canvas id="chart-logins"></canvas>
            </div>
            <div class="lh-chart-box">
                <h5>Hours Online per User</h5>
                <canvas id="chart-hours"></canvas>
            </div>
        </div>

        <!-- Charts row 2 -->
        <div class="lh-charts-row">
            <div class="lh-chart-box wide">
                <h5>Daily Login Activity</h5>
                <canvas id="chart-timeline"></canvas>
            </div>
            <div class="lh-chart-box">
                <h5>Records Updated per User</h5>
                <canvas id="chart-records"></canvas>
            </div>
        </div>

        <!-- Productivity table -->
        <div class="lh-table-box">
            <h5><i class="fa fa-trophy" style="color:#f39c12;"></i>&nbsp; User Productivity Scores</h5>
            <table class="table table-hover" id="productivity-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Logins</th>
                        <th>Hours Online</th>
                        <th>Records Updated</th>
                        <th>Score</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody id="productivity-tbody"></tbody>
            </table>
        </div>

    </div><!-- /dashboard-body -->

    <a href="index.php?module=LoginHistory&parent=Settings&view=List" class="btn btn-default">
        <i class="fa fa-arrow-left"></i> Back to Login History
    </a>

</div><!-- /lh-dashboard -->

{literal}
<script>
(function() {
    var COLORS = [
        '#3498db','#27ae60','#e67e22','#8e44ad',
        '#e74c3c','#16a085','#f39c12','#2980b9',
        '#d35400','#1abc9c'
    ];

    var charts = {};

    function destroyCharts() {
        Object.values(charts).forEach(function(c) { if (c) c.destroy(); });
        charts = {};
    }

    function gradeFor(score) {
        if (score >= 80) return { label:'A', color:'#27ae60' };
        if (score >= 60) return { label:'B', color:'#3498db' };
        if (score >= 40) return { label:'C', color:'#f39c12' };
        if (score >= 20) return { label:'D', color:'#e67e22' };
        return { label:'F', color:'#e74c3c' };
    }

    function makeBar(canvasId, labels, values, color) {
        var ctx = document.getElementById(canvasId).getContext('2d');
        charts[canvasId] = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: labels.map(function(_, i) {
                        return COLORS[i % COLORS.length];
                    }),
                    borderRadius: 4,
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                    x: { ticks: { maxRotation: 30 } }
                },
                responsive: true,
                maintainAspectRatio: true,
            }
        });
    }

    function makeTimeline(dates, tlUsers, timeline) {
        var ctx = document.getElementById('chart-timeline').getContext('2d');
        var datasets = tlUsers.map(function(user, i) {
            return {
                label: user,
                data: dates.map(function(d) {
                    return (timeline[d] && timeline[d][user]) ? timeline[d][user] : 0;
                }),
                borderColor: COLORS[i % COLORS.length],
                backgroundColor: COLORS[i % COLORS.length] + '22',
                fill: true,
                tension: 0.35,
                pointRadius: dates.length > 30 ? 0 : 4,
            };
        });
        charts['chart-timeline'] = new Chart(ctx, {
            type: 'line',
            data: { labels: dates, datasets: datasets },
            options: {
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 12 } } } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                    x: { ticks: { maxTicksLimit: 12, maxRotation: 30 } }
                },
                responsive: true,
                maintainAspectRatio: true,
            }
        });
    }

    function renderDashboard(data) {
        var d = data.result;

        // Summary cards
        document.getElementById('stat-logins').textContent  = d.totals.logins;
        document.getElementById('stat-hours').textContent   = d.totals.hours + 'h';
        document.getElementById('stat-records').textContent = d.totals.records;
        document.getElementById('stat-users').textContent   = d.totals.active_users;
        document.getElementById('date-range').textContent   = d.range;

        if (!d.users || d.users.length === 0) {
            document.getElementById('dashboard-body').innerHTML =
                '<p style="text-align:center;color:#aaa;padding:40px;">No data found for this period.</p>';
            document.getElementById('dashboard-body').style.display = '';
            document.getElementById('dashboard-spinner').style.display = 'none';
            return;
        }

        destroyCharts();

        var names   = d.users.map(function(u) { return u.name; });
        var logins  = d.users.map(function(u) { return u.logins; });
        var hours   = d.users.map(function(u) { return u.hours; });
        var records = d.users.map(function(u) { return u.records; });

        makeBar('chart-logins',  names, logins);
        makeBar('chart-hours',   names, hours);
        makeBar('chart-records', names, records);

        var dates = Object.keys(d.timeline).sort();
        if (dates.length > 0 && d.tl_users.length > 0) {
            makeTimeline(dates, d.tl_users, d.timeline);
        } else {
            document.getElementById('chart-timeline').parentNode.innerHTML =
                '<h5>Daily Login Activity</h5><p style="color:#aaa;text-align:center;padding:30px 0;">No timeline data for this period.</p>';
        }

        // Productivity table
        var tbody = document.getElementById('productivity-tbody');
        tbody.innerHTML = '';
        d.users.forEach(function(u, i) {
            var g = gradeFor(u.score);
            var scoreColor = g.color;
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td><strong>' + u.name + '</strong></td>' +
                '<td>' + u.logins + '</td>' +
                '<td>' + u.hours.toFixed(1) + 'h</td>' +
                '<td>' + u.records + '</td>' +
                '<td style="min-width:140px;">' +
                    '<div style="display:flex;align-items:center;gap:8px;">' +
                        '<div class="score-bar-wrap" style="flex:1;">' +
                            '<div class="score-bar" style="width:' + u.score + '%;background:' + scoreColor + ';"></div>' +
                        '</div>' +
                        '<span style="font-weight:600;color:' + scoreColor + ';min-width:32px;">' + u.score + '</span>' +
                    '</div>' +
                '</td>' +
                '<td><span class="grade-badge" style="background:' + g.color + ';">' + g.label + '</span></td>';
            tbody.appendChild(tr);
        });

        document.getElementById('dashboard-spinner').style.display = 'none';
        document.getElementById('dashboard-body').style.display = '';
    }

    function loadPeriod(period) {
        document.getElementById('dashboard-spinner').style.display = '';
        document.getElementById('dashboard-body').style.display = 'none';

        jQuery.post('index.php',
            { module: 'LoginHistory', parent: 'Settings', action: 'DashboardData', mode: 'get', period: period },
            function(data) { renderDashboard(data); },
            'json'
        ).fail(function() {
            document.getElementById('dashboard-spinner').innerHTML =
                '<span style="color:#e74c3c;"><i class="fa fa-exclamation-circle"></i> Failed to load data.</span>';
        });
    }

    jQuery(document).ready(function() {
        jQuery('#period-selector .btn-period').on('click', function() {
            jQuery('#period-selector .btn-period').removeClass('active btn-primary').addClass('btn-default');
            jQuery(this).removeClass('btn-default').addClass('active btn-primary');
            loadPeriod(jQuery(this).data('period'));
        });

        // Load default period (week)
        loadPeriod('week');
        jQuery('#period-selector [data-period="week"]').removeClass('btn-default').addClass('btn-primary');
    });
})();
</script>
{/literal}
