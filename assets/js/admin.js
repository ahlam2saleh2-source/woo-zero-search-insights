/* ═══════════════════════════════════════════════════════
   Woo Zero Search Insights - Admin JS
   - Theme cycle toggle (6 themes)
   - Delete term AJAX
   - Clear all AJAX
   - Toast notifications
   ═══════════════════════════════════════════════════════ */
(function($) {
    'use strict';

    window.wzsiToast = function(message, type) {
        type = type || 'info';
        var colors = {
            success: { bg: '#00a32a', icon: '✅' },
            error:   { bg: '#d63638', icon: '❌' },
            warning: { bg: '#dba617', icon: '⚠️' },
            info:    { bg: '#2271b1', icon: 'ℹ️' }
        };
        var c = colors[type] || colors.info;
        if ($('#wzsi-toast-container').length === 0) {
            $('body').append('<div id="wzsi-toast-container" style="position:fixed; top:30px; right:20px; z-index:99999; max-width:400px;"></div>');
        }
        var $toast = $('<div style="background:' + c.bg + '; color:#fff; padding:14px 18px; margin-bottom:10px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.2); display:flex; gap:8px; font-weight:500; transform:translateX(420px); opacity:0; transition:all 0.3s ease;">' +
            '<span style="font-size:18px;">' + c.icon + '</span>' +
            '<span style="flex:1;">' + message + '</span>' +
            '<span style="cursor:pointer; font-size:18px;" onclick="this.parentElement.remove();">×</span>' +
        '</div>');
        $('#wzsi-toast-container').append($toast);
        setTimeout(function() { $toast.css({ transform: 'translateX(0)', opacity: '1' }); }, 100);
        var timeout = (type === 'error') ? 15000 : 5000;
        setTimeout(function() {
            $toast.css({ transform: 'translateX(420px)', opacity: '0' });
            setTimeout(function() { $toast.remove(); }, 300);
        }, timeout);
    };

    $(function() {
        // ════════ تبديل الموضوع (دورة 6 مواضيع) ════════
        $(document).on('click', '#wzsi-toggle-theme', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $icon = $btn.find('.dashicons');
            var $text = $btn.find('.toggle-text');
            var nextTheme = $btn.data('next-theme');
            $btn.prop('disabled', true);

            $.ajax({
                url: wzsm.ajax_url,
                method: 'POST',
                dataType: 'json',
                data: { action: 'wzsi_set_theme', nonce: wzsm.nonce, theme: nextTheme }
            })
            .done(function(resp) {
                if (resp && resp.success) {
                    var d = resp.data;
                    $('body').removeClass(function(i, cls) {
                        return (cls.match(/\bwzsi-theme-\S+/g) || []).join(' ');
                    });
                    if (d.theme !== 'light') {
                        $('body').addClass('wzsi-theme-' + d.theme);
                    }
                    $icon.attr('class', 'dashicons ' + d.theme_icon);
                    $text.text(d.theme_label);
                    $btn.data('current-theme', d.theme);
                    $btn.data('next-theme', d.next_theme);
                    $btn.css('transform', 'scale(1.15)');
                    setTimeout(function() { $btn.css('transform', ''); }, 250);
                    wzsiToast(wzsi_t_vars.theme_changed + ': ' + d.theme_label, 'success');
                } else {
                    wzsiToast('Failed', 'error');
                }
            })
            .fail(function(xhr) {
                wzsiToast('Error: ' + xhr.responseText.substring(0, 100), 'error');
            })
            .always(function() {
                $btn.prop('disabled', false);
            });
        });

        // ════════ حذف مصطلح ════════
        $(document).on('click', '.wzsm-delete-term', function(e) {
            e.preventDefault();
            if (!confirm(wzsm.i18n.confirm_delete_term)) return;
            var $btn = $(this);
            var term = $btn.data('term');
            $btn.prop('disabled', true);
            $.post(wzsm.ajax_url, {
                action: 'wzsi_delete_term',
                nonce: wzsm.nonce,
                term: term
            }, function(resp) {
                if (resp && resp.success) {
                    $btn.closest('tr').fadeOut(300, function(){ $(this).remove(); });
                    wzsiToast(wzsi_t_vars.term_deleted, 'success');
                } else {
                    wzsiToast(wzsi_t_vars.delete_failed, 'error');
                }
            }).fail(function() {
                wzsiToast(wzsi_t_vars.network_error, 'error');
            });
        });

        // ════════ مسح الكل ════════
        $(document).on('click', '#wzsm-clear-all', function(e) {
            e.preventDefault();
            if (!confirm(wzsm.i18n.confirm_delete)) return;
            var $btn = $(this);
            $btn.prop('disabled', true);
            $.post(wzsm.ajax_url, {
                action: 'wzsi_clear_all',
                nonce: wzsm.nonce
            }, function(resp) {
                if (resp && resp.success) {
                    wzsiToast(wzsi_t_vars.cleared, 'success');
                    setTimeout(function() { location.reload(); }, 1500);
                }
            }).fail(function() {
                wzsiToast(wzsi_t_vars.network_error, 'error');
                $btn.prop('disabled', false);
            });
        });

        // ════════ نسخ المصطلح ════════
        $('.wzsm-term').on('click', function() {
            var text = $(this).find('strong').text();
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(text).select();
            try {
                document.execCommand('copy');
                wzsiToast(wzsi_t_vars.copied + ': ' + text, 'success');
            } catch (e) {}
            $temp.remove();
        }).css('cursor', 'pointer').attr('title', wzsi_t_vars.click_to_copy);
    });
})(jQuery);
