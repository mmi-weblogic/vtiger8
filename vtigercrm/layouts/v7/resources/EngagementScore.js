/**
 * Engagement Score - star rating for Leads, Contacts, Accounts list & detail views.
 */
(function($) {
    'use strict';

    var ENG_MODULES = ['Leads', 'Contacts', 'Accounts'];
    var STAR_HTML   = {
        filled: '<i class="fa fa-star" style="color:#f39c12;"></i>',
        empty:  '<i class="fa fa-star-o" style="color:#ccc;"></i>'
    };

    function starsHtml(n, tooltip) {
        var h = '';
        for (var i = 1; i <= 5; i++) {
            h += (i <= n) ? STAR_HTML.filled : STAR_HTML.empty;
        }
        if (tooltip) {
            h = '<span title="' + tooltip + '" style="white-space:nowrap;cursor:default;">' + h + '</span>';
        }
        return h;
    }

    function getModule() {
        return $('input[name="module"]').val() || '';
    }

    function getView() {
        return $('input[name="view"], input#view').val() || '';
    }

    function getCurrentFilter() {
        var m = window.location.search.match(/engagement_score_filter=(\d)/);
        return m ? parseInt(m[1]) : 0;
    }

    function buildFilterUrl(stars) {
        var url = window.location.href;
        url = url.replace(/[?&]engagement_score_filter=\d/g, '');
        url = url.replace(/[?&]page=\d+/g, '');
        var sep = (url.indexOf('?') === -1) ? '?' : '&';
        return url + sep + 'engagement_score_filter=' + stars;
    }

    // ── List View ──────────────────────────────────────────────────────────────

    function initListView(module) {
        var currentFilter = getCurrentFilter();

        // Inject filter bar above list actions
        var filterBar = $('<div class="eng-filter-bar" style="' +
            'padding:8px 12px;background:#f8f8f8;border:1px solid #e3e3e3;border-radius:4px;' +
            'margin-bottom:10px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">' +
            '<span style="font-weight:600;font-size:13px;color:#555;">' +
            '<i class="fa fa-star" style="color:#f39c12;"></i> Engagement Filter:</span>' +
        '</div>');

        var labels = ['All', '1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'];
        for (var s = 0; s <= 5; s++) {
            var active  = (s === currentFilter);
            var btnHtml = s === 0
                ? '<a href="' + buildClearUrl() + '" class="btn btn-xs ' + (active ? 'btn-warning' : 'btn-default') + '" style="margin:2px;">All</a>'
                : '<a href="' + buildFilterUrl(s) + '" class="btn btn-xs ' + (active ? 'btn-warning' : 'btn-default') + '" style="margin:2px;white-space:nowrap;">' + starsHtml(s) + '</a>';
            filterBar.append(btnHtml);
        }

        // Recalculate button (admin)
        filterBar.append(
            '<button id="eng-recalc-btn" class="btn btn-xs btn-default" style="margin-left:auto;margin:2px;" title="Recalculate engagement scores for all ' + module + '">' +
            '<i class="fa fa-refresh"></i> Recalculate Scores</button>'
        );

        // Show active filter label
        if (currentFilter > 0) {
            filterBar.append(
                '<span class="label label-warning" style="margin-left:4px;">' +
                'Showing: ' + starsHtml(currentFilter) + ' only &nbsp;' +
                '<a href="' + buildClearUrl() + '" style="color:#fff;" title="Clear filter">×</a></span>'
            );
        }

        $('#listview-actions, .listview-actions-container').prepend(filterBar);

        // Recalculate handler
        $(document).on('click', '#eng-recalc-btn', function() {
            var btn = $(this);
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Calculating…');
            $.post('index.php', {module: 'Vtiger', action: 'EngagementScore', mode: 'calculate', eng_module: module},
                function(res) {
                    if (res && res.result) {
                        btn.html('<i class="fa fa-check"></i> Done (' + (res.result.updated || '?') + ' records)');
                        setTimeout(function() { window.location.reload(); }, 1200);
                    } else {
                        btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> Recalculate Scores');
                        alert('Recalculation failed.');
                    }
                }, 'json'
            ).fail(function() {
                btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> Recalculate Scores');
            });
        });

        // ── Inject star column into list table ──────────────────────────────

        // Add header column (after checkbox column)
        var $thead = $('#listview-table thead tr.listViewContentHeader');
        $thead.find('th:first').after(
            '<th style="white-space:nowrap;min-width:100px;">' +
            '<i class="fa fa-star" style="color:#f39c12;"></i> Engagement</th>'
        );

        // Add placeholder cells per row
        var ids = [];
        $('#listview-table tbody tr[data-id]').each(function() {
            var id = $(this).data('id');
            if (id) {
                ids.push(id);
                $(this).find('td:first').after(
                    '<td class="eng-cell" data-crmid="' + id + '" style="vertical-align:middle;">' +
                    '<i class="fa fa-spinner fa-spin" style="color:#ccc;"></i></td>'
                );
            }
        });

        if (!ids.length) return;

        // Batch-fetch scores
        $.post('index.php',
            {module: 'Vtiger', action: 'EngagementScore', mode: 'get_scores', crmids: ids.join(',')},
            function(res) {
                if (!res || !res.result || !res.result.scores) {
                    $('.eng-cell').html('<span style="color:#ccc;">—</span>');
                    return;
                }
                var scores = res.result.scores;
                $('.eng-cell[data-crmid]').each(function() {
                    var id    = $(this).data('crmid');
                    var score = scores[id];
                    if (score) {
                        var tip = 'Activities: ' + score.activities +
                                  ' | Comments: ' + score.comments +
                                  ' | Notes: ' + score.notes +
                                  ' | Attachments: ' + score.attachments +
                                  ' | Campaigns: ' + score.campaigns +
                                  ' | Raw score: ' + score.raw;
                        $(this).html(starsHtml(score.stars, tip));
                    } else {
                        $(this).html('<span title="Not yet calculated — click Recalculate Scores" style="color:#ddd;">' + starsHtml(0) + '</span>');
                    }
                });
            }, 'json'
        ).fail(function() {
            $('.eng-cell').html('<span style="color:#ccc;">—</span>');
        });
    }

    function buildClearUrl() {
        var url = window.location.href;
        url = url.replace(/[?&]engagement_score_filter=\d/g, '');
        url = url.replace(/[?&]page=\d+/g, '');
        return url;
    }

    // ── Detail View ────────────────────────────────────────────────────────────

    function initDetailView(module) {
        var crmid = $('input#recordId, input[name="record"]').first().val();
        if (!crmid) return;

        // Find a good insertion point in the summary header
        var $target = $('.summary-title, .recordBasicInfo, .detailViewTitle').first();
        if (!$target.length) return;

        var $badge = $('<span class="eng-detail-badge" style="margin-left:12px;vertical-align:middle;display:inline-block;">' +
            '<i class="fa fa-spinner fa-spin" style="color:#ccc;font-size:13px;"></i></span>');
        $target.append($badge);

        $.post('index.php',
            {module: 'Vtiger', action: 'EngagementScore', mode: 'get_scores', crmids: crmid},
            function(res) {
                if (res && res.result && res.result.scores && res.result.scores[crmid]) {
                    var s   = res.result.scores[crmid];
                    var tip = 'Engagement Score: ' + s.stars + '/5 stars | Activities: ' + s.activities +
                              ', Comments: ' + s.comments + ', Notes: ' + s.notes +
                              ', Attachments: ' + s.attachments + ', Campaigns: ' + s.campaigns;
                    $badge.html('<span title="' + tip + '" style="cursor:default;font-size:15px;">' + starsHtml(s.stars) + '</span>');
                } else {
                    $badge.html('<span title="Engagement not calculated yet" style="color:#ddd;font-size:15px;">' + starsHtml(0) + '</span>');
                }
            }, 'json'
        ).fail(function() { $badge.remove(); });
    }

    // ── Bootstrap ──────────────────────────────────────────────────────────────

    $(document).ready(function() {
        var mod  = getModule();
        var view = getView();
        if (ENG_MODULES.indexOf(mod) === -1) return;

        if (view === 'List' || view === '') {
            // small delay to let vtiger finish rendering
            setTimeout(function() { initListView(mod); }, 300);
        } else if (view === 'Detail' || view === 'DetailDuplicate') {
            setTimeout(function() { initDetailView(mod); }, 400);
        }
    });

})(jQuery);
