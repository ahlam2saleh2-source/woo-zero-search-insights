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

        // ════════ F-4: الربط الذكي (اللوحة + صفحة الربط) ════════
        var mapState = { targetId: 0, targetType: 'product', targetLabel: '' };

        function wzsiMapReset() {
            mapState = { targetId: 0, targetType: 'product', targetLabel: '' };
            $('#wzsi-map-term-input').val('').prop('readonly', false);
            $('#wzsi-map-search').val('');
            $('#wzsi-map-url').val('');
            $('#wzsi-map-results').empty().hide();
            $('#wzsi-map-type').val('product');
            $('.wzsi-map-selected').hide();
            $('#wzsi-map-save').prop('disabled', false);
            wzsiMapSyncType();
        }

        function wzsiMapSyncType() {
            var t = $('#wzsi-map-type').val();
            mapState.targetType = t;
            if (t === 'url') {
                $('#wzsi-map-url-wrap').show();
                $('#wzsi-map-search-wrap').hide();
            } else {
                $('#wzsi-map-url-wrap').hide();
                $('#wzsi-map-search-wrap').show();
            }
        }

        // فتح من اللوحة (مصطلح محدد وغير قابل للتعديل)
        $(document).on('click', '.wzsm-map-btn', function(e) {
            e.preventDefault();
            wzsiMapReset();
            $('#wzsi-map-term-input').val($(this).data('term')).prop('readonly', true);
            $('#wzsi-map-modal').show();
        });

        // فتح من صفحة الربط (مصطلح جديد قابل للتعديل)
        $(document).on('click', '.wzsi-map-new', function(e) {
            e.preventDefault();
            wzsiMapReset();
            $('#wzsi-map-term-input').focus();
            $('#wzsi-map-modal').show();
        });

        $(document).on('click', '#wzsi-map-cancel, .wzsi-map-backdrop', function(e) {
            e.preventDefault();
            $('#wzsi-map-modal').hide();
        });

        $(document).on('change', '#wzsi-map-type', wzsiMapSyncType);

        // بحث لحظي عن المنتجات/التصنيفات (debounce)
        var mapSearchTimer = null;
        $(document).on('input', '#wzsi-map-search', function() {
            var q = $(this).val();
            clearTimeout(mapSearchTimer);
            if (!q || q.length < 2) {
                $('#wzsi-map-results').hide().empty();
                return;
            }
            mapSearchTimer = setTimeout(function() {
                $.post(wzsm.ajax_url, {
                    action: 'wzsi_search_products',
                    nonce: wzsm.nonce,
                    q: q
                }, function(resp) {
                    if (resp && resp.success && resp.data && resp.data.length) {
                        var html = '';
                        resp.data.forEach(function(item) {
                            html += '<button type="button" class="wzsi-map-result" data-id="' + item.id + '" data-type="' + (item.type || 'product') + '">' + item.title + '</button>';
                        });
                        $('#wzsi-map-results').html(html).show();
                    } else {
                        $('#wzsi-map-results').html('<span class="wzsi-map-noresult">—</span>').show();
                    }
                });
            }, 300);
        });

        $(document).on('click', '.wzsi-map-result', function(e) {
            e.preventDefault();
            mapState.targetId = parseInt($(this).data('id'), 10) || 0;
            mapState.targetType = $(this).data('type') || 'product';
            mapState.targetLabel = $(this).text();
            $('#wzsi-map-type').val(mapState.targetType);
            wzsiMapSyncType();
            $('#wzsi-map-search').val(mapState.targetLabel);
            $('#wzsi-map-results').hide();
            $('.wzsi-map-selected span').text(mapState.targetLabel);
            $('.wzsi-map-selected').show();
        });

        $(document).on('click', '#wzsi-map-save', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var term = $.trim($('#wzsi-map-term-input').val());
            var url = $.trim($('#wzsi-map-url').val());

            if (!term) { wzsiToast(wzsi_t_vars.map_error_term, 'warning'); return; }
            if (mapState.targetType === 'url' && !url) { wzsiToast(wzsi_t_vars.map_error_url, 'warning'); return; }
            if (mapState.targetType !== 'url' && !mapState.targetId) { wzsiToast(wzsi_t_vars.map_error_target, 'warning'); return; }

            $btn.prop('disabled', true);
            $.post(wzsm.ajax_url, {
                action: 'wzsi_add_mapping',
                nonce: wzsm.nonce,
                term: term,
                target_type: mapState.targetType,
                target_id: mapState.targetId,
                target_url: url
            }, function(resp) {
                if (resp && resp.success) {
                    wzsiToast(wzsi_t_vars.map_saved, 'success');
                    setTimeout(function() { location.reload(); }, 900);
                } else {
                    wzsiToast((resp && resp.data && resp.data.msg) ? resp.data.msg : wzsi_t_vars.map_failed, 'error');
                    $btn.prop('disabled', false);
                }
            }).fail(function() {
                wzsiToast(wzsi_t_vars.map_failed, 'error');
                $btn.prop('disabled', false);
            });
        });

        // صفحة الربط: إيقاف/تفعيل
        $(document).on('click', '.wzsm-map-toggle', function(e) {
            e.preventDefault();
            var $btn = $(this);
            $btn.prop('disabled', true);
            $.post(wzsm.ajax_url, {
                action: 'wzsi_toggle_mapping',
                nonce: wzsm.nonce,
                id: $btn.data('id')
            }, function(resp) {
                if (resp && resp.success) {
                    location.reload();
                } else {
                    $btn.prop('disabled', false);
                }
            }).fail(function() { $btn.prop('disabled', false); });
        });

        // صفحة الربط: حذف
        $(document).on('click', '.wzsm-map-delete', function(e) {
            e.preventDefault();
            if (!confirm(wzsm.i18n.confirm_delete_term)) return;
            var $btn = $(this);
            $.post(wzsm.ajax_url, {
                action: 'wzsi_delete_mapping',
                nonce: wzsm.nonce,
                id: $btn.data('id')
            }, function(resp) {
                if (resp && resp.success) {
                    $btn.closest('tr').fadeOut(250, function() { $(this).remove(); });
                }
            });
        });

        // ════════ F-9 (v1.2.0): إشعارات الزبائن — نافذة المؤلف ════════
        var ntfState = { mode: 'single', userIds: [], term: '', recipientsLoaded: false };

        function wzsiNtfTemplates() {
            return (window.wzsi_ntf_tpl || { ar: { subject: '', body: '' }, en: { subject: '', body: '' } });
        }

        function wzsiNtfFillTemplate() {
            var lang = $('#wzsi-ntf-lang').val();
            var tpl;
            if (lang === 'auto') {
                // نعرض قالب لغة الواجهة الحالية كنقطة بداية؛ auto يُقرر لكل مستخدم عند الإرسال
                var isRtl = $('body').hasClass('rtl');
                lang = isRtl ? 'ar' : 'en';
            }
            tpl = wzsiNtfTemplates()[lang] || wzsiNtfTemplates().ar;
            if (tpl) {
                $('#wzsi-ntf-subject').val(tpl.subject || '');
                $('#wzsi-ntf-body').val(tpl.body || '');
            }
        }

        function wzsiNtfReset() {
            ntfState = { mode: 'single', userIds: [], term: '', recipientsLoaded: false };
            $('#wzsi-ntf-product-id').val(0);
            $('#wzsi-ntf-product-search').val('');
            $('#wzsi-ntf-product-results').empty().hide();
            $('.wzsi-ntf-selected').hide();
            $('#wzsi-ntf-lang').val('auto');
            $('#wzsi-ntf-ch-email').prop('checked', true);
            $('#wzsi-ntf-ch-site').prop('checked', false);
            $('#wzsi-ntf-status').text('');
            $('#wzsi-ntf-send').prop('disabled', false);
        }

        // فتح — فردي (من صف السجل)
        $(document).on('click', '.wzsi-notify-one', function(e) {
            e.preventDefault();
            wzsiNtfReset();
            ntfState.mode = 'single';
            ntfState.userIds = [String($(this).data('user'))];
            ntfState.term = String($(this).data('term') || '');
            $('#wzsi-ntf-single-name').text($(this).data('name') || ('#' + $(this).data('user')));
            $('#wzsi-ntf-single-email').text($(this).data('email') ? ' — ' + $(this).data('email') : '');
            $('#wzsi-ntf-single-box').show();
            $('#wzsi-ntf-recipients-box').hide();
            $('#wzsi-ntf-term').text(ntfState.term);
            wzsiNtfFillTemplate();
            $('#wzsi-ntf-modal').show();
        });

        // فتح — جماعي (من العرض المجمع): نجلب الجمهور أولاً
        $(document).on('click', '.wzsi-notify-group', function(e) {
            e.preventDefault();
            var $btn = $(this);
            wzsiNtfReset();
            ntfState.mode = 'group';
            ntfState.term = String($btn.data('term') || '');
            $('#wzsi-ntf-single-box').hide();
            $('#wzsi-ntf-recipients-box').show();
            $('#wzsi-ntf-recipients-list').html('<em>' + (wzsi_t_vars.ntf_loading || '…') + '</em>');
            $('#wzsi-ntf-guests-note').text('');
            $('#wzsi-ntf-term').text(ntfState.term);
            wzsiNtfFillTemplate();
            $('#wzsi-ntf-modal').show();

            $.post(wzsm.ajax_url, {
                action: 'wzsi_notify_recipients',
                nonce: wzsm.nonce,
                term: ntfState.term
            }, function(resp) {
                if (!(resp && resp.success)) {
                    $('#wzsi-ntf-recipients-list').html('<em>—</em>');
                    return;
                }
                var rows = resp.data.registered || [];
                var html = '';
                if (!rows.length) {
                    html = '<em>' + (wzsi_t_vars.ntf_no_registered || '—') + '</em>';
                }
                rows.forEach(function(r) {
                    html += '<label style="display:block;margin:2px 0;">'
                        + '<input type="checkbox" class="wzsi-ntf-rcpt" value="' + r.id + '" checked> '
                        + '<strong>' + $('<i>').text(r.name).html() + '</strong> '
                        + '<span style="color:#666;">' + r.email + '</span>'
                        + ' <span style="color:#999;">(' + r.searches + 'x)</span></label>';
                });
                $('#wzsi-ntf-recipients-list').html(html);
                if (parseInt(resp.data.guests, 10) > 0) {
                    $('#wzsi-ntf-guests-note').text(
                        wzsi_t_vars.ntf_guests_note_prefix + ' ' + resp.data.guests + ' ' + wzsi_t_vars.ntf_guests_note_suffix
                    );
                }
            }).fail(function() {
                $('#wzsi-ntf-recipients-list').html('<em>—</em>');
            });
        });

        $(document).on('click', '#wzsi-ntf-cancel, #wzsi-ntf-modal .wzsi-map-backdrop', function(e) {
            e.preventDefault();
            $('#wzsi-ntf-modal').hide();
        });

        $(document).on('change', '#wzsi-ntf-lang', wzsiNtfFillTemplate);

        // بحث المنتج (نفس نقطة wzsi_search_products)
        var ntfSearchTimer = null;
        $(document).on('input', '#wzsi-ntf-product-search', function() {
            var q = $(this).val();
            clearTimeout(ntfSearchTimer);
            if (!q || q.length < 2) {
                $('#wzsi-ntf-product-results').hide().empty();
                return;
            }
            ntfSearchTimer = setTimeout(function() {
                $.post(wzsm.ajax_url, {
                    action: 'wzsi_search_products',
                    nonce: wzsm.nonce,
                    q: q
                }, function(resp) {
                    if (resp && resp.success && resp.data && resp.data.length) {
                        var html = '';
                        resp.data.forEach(function(item) {
                            html += '<button type="button" class="wzsi-map-result wzsi-ntf-result" data-id="' + item.id + '">' + item.title + '</button>';
                        });
                        $('#wzsi-ntf-product-results').html(html).show();
                    } else {
                        $('#wzsi-ntf-product-results').html('<span class="wzsi-map-noresult">—</span>').show();
                    }
                });
            }, 300);
        });

        $(document).on('click', '.wzsi-ntf-result', function(e) {
            e.preventDefault();
            var id = parseInt($(this).data('id'), 10) || 0;
            var label = $(this).text();
            $('#wzsi-ntf-product-id').val(id);
            $('#wzsi-ntf-product-search').val(label);
            $('#wzsi-ntf-product-results').hide();
            $('.wzsi-ntf-selected span').text(label);
            $('.wzsi-ntf-selected').show();
        });

        // الإرسال
        $(document).on('click', '#wzsi-ntf-send', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var channels = [];
            if ($('#wzsi-ntf-ch-email').is(':checked')) channels.push('email');
            if ($('#wzsi-ntf-ch-site').is(':checked')) channels.push('site');
            if (!channels.length) { wzsiToast(wzsi_t_vars.ntf_error_channel, 'warning'); return; }
            if (!$('#wzsi-ntf-product-id').val() || parseInt($('#wzsi-ntf-product-id').val(), 10) < 1) {
                wzsiToast(wzsi_t_vars.ntf_error_product, 'warning');
                return;
            }

            var userIds;
            if (ntfState.mode === 'group') {
                var checked = $('.wzsi-ntf-rcpt:checked').map(function() { return $(this).val(); }).get();
                userIds = checked.length ? checked.join(',') : 'all';
            } else {
                userIds = ntfState.userIds.join(',');
            }

            $btn.prop('disabled', true);
            $('#wzsi-ntf-status').text('…');
            $.post(wzsm.ajax_url, {
                action: 'wzsi_notify_send',
                nonce: wzsm.nonce,
                user_ids: userIds,
                term: ntfState.term,
                product_id: $('#wzsi-ntf-product-id').val(),
                lang: $('#wzsi-ntf-lang').val(),
                channels: channels.join(','),
                subject: $.trim($('#wzsi-ntf-subject').val()),
                body: $('#wzsi-ntf-body').val()
            }, function(resp) {
                if (resp && resp.success) {
                    var d = resp.data;
                    var line = wzsi_t_vars.ntf_done_ok
                        .replace('%site%', d.sent_site || 0)
                        .replace('%email%', d.sent_email || 0)
                        .replace('%fail%', d.failed || 0);
                    $('#wzsi-ntf-status').text(line);
                    wzsiToast(line, d.failed ? 'warning' : 'success');
                    setTimeout(function() { location.reload(); }, 1600);
                } else {
                    var msg = (resp && resp.data && resp.data.msg) ? resp.data.msg : wzsi_t_vars.ntf_error_send;
                    $('#wzsi-ntf-status').text('');
                    wzsiToast(msg, 'error');
                    $btn.prop('disabled', false);
                }
            }).fail(function() {
                $('#wzsi-ntf-status').text('');
                wzsiToast(wzsi_t_vars.ntf_error_send, 'error');
                $btn.prop('disabled', false);
            });
        });

        // فحص صحة البريد (صفحة فحص الصحة)
        $(document).on('click', '#wzsi-mail-test-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var to = $.trim($('#wzsi-mail-test-to').val());
            if (!to) { wzsiToast(wzsi_t_vars.ntf_error_email, 'warning'); return; }
            $btn.prop('disabled', true);
            $('#wzsi-mail-test-status').text('…');
            $.post(wzsm.ajax_url, {
                action: 'wzsi_mail_test',
                nonce: wzsm.nonce,
                to: to
            }, function(resp) {
                $btn.prop('disabled', false);
                if (resp && resp.success && resp.data.ok) {
                    $('#wzsi-mail-test-status').css('color', '#00a32a').text(wzsi_t_vars.mail_test_ok);
                } else {
                    var err = (resp && resp.data && resp.data.error) ? ' — ' + resp.data.error : '';
                    $('#wzsi-mail-test-status').css('color', '#d63638').text(wzsi_t_vars.mail_test_fail + err);
                }
            }).fail(function() {
                $btn.prop('disabled', false);
                $('#wzsi-mail-test-status').css('color', '#d63638').text(wzsi_t_vars.mail_test_fail);
            });
        });
    });
})(jQuery);
