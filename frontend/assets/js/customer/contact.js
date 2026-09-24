(function () {
    'use strict';

    var form = UI.qs('[data-enquiry-form]');

    function fillContact(settings) {
        if (!settings) {
            return;
        }
        var phone = UI.qs('[data-contact-phone]');
        var email = UI.qs('[data-contact-email]');
        var address = UI.qs('[data-contact-address]');
        if (phone) {
            phone.textContent = settings.company_phone || '\u2014';
        }
        if (email) {
            email.textContent = settings.company_email || '\u2014';
        }
        if (address) {
            address.textContent = settings.company_address || '\u2014';
        }
    }

    function renderOptions(container, items, type) {
        if (!container) {
            return;
        }
        if (!items || items.length === 0) {
            container.innerHTML = '<div class="table-empty"><div class="empty-title">Nothing available</div></div>';
            return;
        }
        container.innerHTML = items.map(function (item) {
            var price = item.total_amount !== undefined ? item.total_amount : item.starting_price;
            return '' +
                '<label class="item-row flex items-center gap-3" style="cursor:pointer">' +
                    '<input type="checkbox" name="items" value="' + Number(item.id) + '" data-source-type="' + type + '" data-source-id="' + Number(item.id) + '">' +
                    '<span class="flex-1 min-w-0">' +
                        '<span class="block font-medium">' + UI.escape(item.name) + '</span>' +
                        (item.category_name ? '<span class="block text-xs text-muted">' + UI.escape(item.category_name) + '</span>' : '') +
                    '</span>' +
                    '<span class="site-price text-sm">' + Site.money(price) + '</span>' +
                '</label>';
        }).join('');
    }

    function preselect() {
        var params = new URLSearchParams(window.location.search);
        var service = params.get('service');
        var pkg = params.get('package');
        if (service) {
            var serviceBox = form.querySelector('input[data-source-type="service"][data-source-id]');
            UI.qsa('input[data-source-type="service"]', form).forEach(function (input) {
                var label = input.closest('label');
                if (label && label.textContent.toLowerCase().indexOf(service.replace(/-/g, ' ').toLowerCase()) !== -1) {
                    input.checked = true;
                }
            });
            if (serviceBox) {
                serviceBox.scrollIntoView({ block: 'center' });
            }
        }
        if (pkg) {
            UI.qsa('input[data-source-type="package"]', form).forEach(function (input) {
                var label = input.closest('label');
                if (label && label.textContent.toLowerCase().indexOf(pkg.replace(/-/g, ' ').toLowerCase()) !== -1) {
                    input.checked = true;
                }
            });
        }
    }

    function collectItems() {
        var items = [];
        UI.qsa('input[data-source-type]:checked', form).forEach(function (input) {
            items.push({
                source_type: input.getAttribute('data-source-type'),
                source_id: Number(input.getAttribute('data-source-id')),
                quantity: 1
            });
        });
        return items;
    }

    function prefillCustomer() {
        var fill = function (profile) {
            if (!profile) {
                return;
            }
            var nameField = form.elements.name;
            if (nameField && !nameField.value && profile.name) {
                nameField.value = profile.name;
            }
            if (form.email && !form.email.value && profile.email) {
                form.email.value = profile.email;
            }
            if (form.phone && !form.phone.value && profile.phone) {
                form.phone.value = profile.phone;
            }
            if (form.event_type && !form.event_type.value && profile.event_type) {
                form.event_type.value = profile.event_type;
            }
            if (form.event_date && !form.event_date.value && profile.wedding_date) {
                form.event_date.value = profile.wedding_date;
            }
        };
        // Public pages never populate APP.currentUser, so resolve the session
        // first; anonymous visitors get a 401 here which is silently ignored.
        API.me().then(function (data) {
            var user = data ? data.user : null;
            if (!user || user.role !== 'customer') {
                return;
            }
            API.get('/portal/profile').then(fill).catch(function () {});
        }).catch(function () {});
    }

    function submit(event) {
        event.preventDefault();
        var button = form.querySelector('[data-submit]');
        var payload = {
            name: form.elements.name.value.trim(),
            email: form.email.value.trim(),
            phone: form.phone.value.trim(),
            event_date: form.event_date.value || null,
            event_type: form.event_type.value.trim() || null,
            venue_address: form.venue_address.value.trim() || null,
            notes: form.notes.value.trim() || null,
            items: collectItems()
        };
        Object.keys(payload).forEach(function (key) {
            if (payload[key] === '') {
                payload[key] = key === 'items' ? payload[key] : null;
            }
        });
        UI.setLoading(button, true);
        API.post('/public/enquiries', payload).then(function (data) {
            UI.setLoading(button, false);
            form.hidden = true;
            var success = UI.qs('[data-success]');
            var reference = data ? data.reference_no : '';
            success.innerHTML = '' +
                '<div class="card card-pad" style="text-align:center">' +
                    '<div class="stat-icon mx-auto mb-3">' + UI.icon('circle-check') + '</div>' +
                    '<h3 class="site-section-title" style="font-size:22px">Enquiry received</h3>' +
                    '<p class="text-muted mt-2">Thank you. Our team will contact you shortly.</p>' +
                    (reference ? '<p class="mt-3">Your reference number is <strong>' + UI.escape(reference) + '</strong></p>' : '') +
                    '<a class="btn btn-soft mt-4" href="' + APP.route('home') + '">Back to home</a>' +
                '</div>';
            success.hidden = false;
            if (window.Icons && typeof window.Icons.refresh === 'function') { window.Icons.refresh(document); } else if (window.lucide) { try { window.lucide.createIcons(); } catch (error) { /* ignore */ } }
        }).catch(function (error) {
            UI.setLoading(button, false);
            if (error.errors) {
                UI.showErrors(form, error.errors);
            }
            Alerts.error(error.message || 'Unable to submit your enquiry.');
        });
    }

    function boot() {
        if (!form) {
            return;
        }
        if (APP.siteSettings) {
            fillContact(APP.siteSettings);
        } else if (window.Site && Site.settings) {
            Site.settings().then(fillContact);
        }
        API.get('/public/services').then(function (data) {
            renderOptions(UI.qs('[data-service-options]'), data ? data.services : [], 'service');
            preselect();
        });
        API.get('/public/packages').then(function (data) {
            renderOptions(UI.qs('[data-package-options]'), data || [], 'package');
            preselect();
        });
        prefillCustomer();
        form.addEventListener('submit', submit);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
