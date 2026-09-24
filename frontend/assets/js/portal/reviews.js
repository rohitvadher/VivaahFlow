(function () {
    'use strict';

    var table = UI.qs('[data-table]');
    var form = UI.qs('[data-review-form]');
    var reviewsTable = null;

    function renderEligible(bookings) {
        var card = UI.qs('[data-eligible-card]');
        var node = UI.qs('[data-eligible]');
        if (!card || !node) {
            return;
        }
        if (!bookings || bookings.length === 0) {
            card.hidden = true;
            return;
        }
        node.innerHTML = '<div class="grid gap-3">' + bookings.map(function (booking) {
            return '' +
                '<div class="item-row flex items-center gap-3">' +
                    '<div class="flex-1 min-w-0">' +
                        '<div class="font-medium">' + UI.escape(booking.reference_no) + '</div>' +
                        '<div class="text-xs text-muted">' + UI.escape(booking.event_type || 'Event') + (booking.event_date ? ' \u00b7 ' + APP.formatDate(booking.event_date) : '') + '</div>' +
                    '</div>' +
                    '<button type="button" class="btn btn-primary btn-sm" data-write-review="' + booking.id + '">' + UI.icon('star') + ' Write review</button>' +
                '</div>';
        }).join('') + '</div>';
        card.hidden = false;
        UI.qsa('[data-write-review]', node).forEach(function (button) {
            button.addEventListener('click', function () {
                if (!form) {
                    return;
                }
                form.reset();
                form.booking_id.value = button.getAttribute('data-write-review');
                setRating(5);
                UI.openModal('review-modal');
            });
        });
    }

    function setRating(value) {
        var input = form.querySelector('input[name="rating"]');
        if (input) {
            input.value = value;
        }
        UI.qsa('[data-rating]').forEach(function (button) {
            var star = Number(button.getAttribute('data-rating'));
            var icon = button.querySelector('svg');
            button.style.color = star <= value ? '#f59e0b' : '#cbd5e1';
            if (icon) {
                icon.style.fill = 'currentColor';
            }
        });
    }

    function load() {
        API.get('/portal/reviews/eligible').then(function (data) {
            renderEligible(data || []);
        }).catch(function () {
            renderEligible([]);
        });
    }

    function boot() {
        reviewsTable = window.Tables.create({
            table: table,
            endpoint: '/portal/reviews',
            emptyText: 'No reviews yet',
            emptyHint: 'Once you complete a booking you can share your experience.',
            columns: [
                { label: 'Booking', render: function (row) { return UI.escape(row.booking_no || '\u2014'); } },
                { label: 'Rating', render: function (row) { return '<span class="flex gap-1" style="color:#f59e0b">' + Site.ratingStars(row.rating) + '</span>'; } },
                { label: 'Title', render: function (row) { return UI.escape(row.title || '\u2014'); } },
                { label: 'Status', render: function (row) { return UI.statusBadge(row.status); } },
                { label: 'Reply', render: function (row) { return row.reply ? UI.escape(row.reply) : '<span class="text-muted">\u2014</span>'; } },
                { label: 'Date', render: function (row) { return APP.formatDate(row.created_at); } }
            ]
        });

        load();

        if (form) {
            UI.qsa('[data-rating]').forEach(function (button) {
                button.addEventListener('click', function () {
                    setRating(Number(button.getAttribute('data-rating')));
                });
            });
            setRating(5);
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                var submit = form.querySelector('[data-submit]');
                UI.setLoading(submit, true);
                API.post('/portal/reviews', {
                    booking_id: Number(form.booking_id.value),
                    rating: Number(form.rating.value),
                    title: form.title.value.trim() || null,
                    comment: form.comment.value.trim()
                }).then(function () {
                    UI.setLoading(submit, false);
                    UI.closeModal('review-modal');
                    Alerts.success('Thank you for your review.');
                    load();
                    if (reviewsTable) {
                        reviewsTable.reload();
                    }
                }).catch(function (error) {
                    UI.setLoading(submit, false);
                    if (error.errors) {
                        UI.showErrors(form, error.errors);
                    }
                    Alerts.error(error.message || 'Unable to submit your review.');
                });
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
