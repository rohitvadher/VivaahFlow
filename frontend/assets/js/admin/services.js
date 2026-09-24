(function () {
    'use strict';

    var records = {};
    var categories = [];
    var galleryService = null;
    var tableEl = document.getElementById('services-table');
    var modal = document.getElementById('service-modal');
    var form = document.getElementById('service-form');
    var submitBtn = form.querySelector('[data-submit]');
    var galleryModal = document.getElementById('gallery-modal');
    var isEdit = false;

    function imageUrl(path) {
        if (!path) {
            return '';
        }
        return window.APP.baseUrl + '/' + path;
    }

    function categoryOptions(selected) {
        return categories.map(function (category) {
            return '<option value="' + category.id + '"' + (Number(selected) === Number(category.id) ? ' selected' : '') + '>' + UI.escape(category.name) + '</option>';
        }).join('');
    }

    function uploader() {
        return window.Upload.bind({
            input: form.querySelector('[data-file-input]'),
            dropzone: form.querySelector('[data-dropzone]'),
            preview: form.querySelector('[data-preview]'),
            info: form.querySelector('[data-preview-name]')
        });
    }

    uploader();

    function rowActions(row) {
        return '' +
            '<button type="button" class="btn-icon" data-action="gallery" data-id="' + row.id + '" data-permission="services.update" title="Gallery"><i data-lucide="images"></i></button>' +
            '<button type="button" class="btn-icon" data-action="edit" data-id="' + row.id + '" data-permission="services.update" title="Edit"><i data-lucide="pencil"></i></button>' +
            '<button type="button" class="btn-icon" data-action="delete" data-id="' + row.id + '" data-permission="services.delete" title="Delete" style="color:var(--danger)"><i data-lucide="trash-2"></i></button>';
    }

    var table = window.Tables.create({
        table: tableEl,
        pagination: 'services-pagination',
        search: '[data-table-search]',
        status: '[data-table-status]',
        filters: [{ el: '[data-filter-category]', key: 'category_id' }],
        endpoint: '/services',
        perPage: 10,
        emptyText: 'No services yet',
        emptyHint: 'Add your first service to start building packages.',
        columns: [
            {
                label: 'Service',
                render: function (row) {
                    var thumb = row.image
                        ? '<img class="image-thumb" src="' + imageUrl(row.image) + '" alt="">'
                        : '<span class="image-thumb flex items-center justify-center"><i data-lucide="sparkles" style="width:20px;height:20px;color:var(--brand-500)"></i></span>';
                    return '<div class="flex items-center gap-3">' + thumb +
                        '<div class="min-w-0"><div class="font-semibold truncate">' + UI.escape(row.name) + '</div>' +
                        '<div class="text-muted text-sm">' + UI.escape(row.category_name || '') + '</div></div></div>';
                }
            },
            { label: 'Starting Price', render: function (row) { return '<span class="font-semibold">' + UI.escape(window.APP.money(Math.round(Number(row.starting_price) * 100))) + '</span>'; } },
            { label: 'Duration', render: function (row) { return row.duration_minutes ? window.APP.number(row.duration_minutes) + ' min' : '\u2014'; } },
            { label: 'Featured', render: function (row) { return Number(row.is_featured) ? '<span class="badge badge-brand">Featured</span>' : '\u2014'; } },
            { label: 'Status', render: function (row) { return UI.statusBadge(row.status); } }
        ],
        rowActions: rowActions,
        onLoaded: function (items, data) {
            records = {};
            items.forEach(function (item) { records[item.id] = item; });
            var count = UI.qs('[data-count-label]');
            if (count && data && data.pagination) {
                count.textContent = window.APP.number(data.pagination.total) + ' service' + (data.pagination.total === 1 ? '' : 's');
            }
        }
    });

    function openForm(record) {
        UI.clear(form);
        form.display_order.value = '0';
        form.querySelector('[data-category-select]').innerHTML = categoryOptions(record ? record.category_id : (categories[0] ? categories[0].id : null));
        UI.qs('[data-preview]', form).classList.add('hidden');
        isEdit = !!record;
        UI.qs('[data-modal-title]', modal).textContent = isEdit ? 'Edit Service' : 'Add Service';
        if (isEdit) {
            UI.fill(form, record);
            if (record.image) {
                var preview = form.querySelector('[data-preview]');
                var img = preview.querySelector('img');
                img.src = imageUrl(record.image);
                preview.classList.remove('hidden');
                form.querySelector('[data-preview-name]').textContent = 'Current cover image';
            }
        }
        UI.openModal(modal);
    }

    function openGallery(record) {
        galleryService = record;
        UI.qs('[data-gallery-service]').textContent = record.name;
        Loader.skeleton(UI.qs('[data-gallery-grid]'), { rows: 1 });
        UI.openModal(galleryModal);
        loadGallery();
    }

    function loadGallery() {
        API.get('/services/' + galleryService.id + '/gallery').then(function (data) {
            var items = Array.isArray(data) ? data : (data && data.items ? data.items : []);
            var grid = UI.qs('[data-gallery-grid]');
            if (!items.length) {
                grid.innerHTML = '<div class="table-empty"><div class="empty-title">No gallery images yet</div></div>';
                return;
            }
            grid.innerHTML = '<div class="grid" style="grid-template-columns:repeat(3,1fr);gap:12px">' + items.map(function (item) {
                return '<div class="card" style="overflow:hidden">' +
                    '<img src="' + imageUrl(item.image_path) + '" alt="" style="width:100%;height:120px;object-fit:cover">' +
                    '<div style="padding:10px">' +
                    '<div class="flex items-center justify-between gap-2">' +
                    (Number(item.is_primary) ? '<span class="badge badge-brand">Primary</span>' : '<span class="badge badge-muted">Gallery</span>') +
                    '<div class="cell-actions">' +
                    (Number(item.is_primary) ? '' : '<button type="button" class="btn-icon" data-gallery-primary="' + item.id + '" title="Set primary"><i data-lucide="star"></i></button>') +
                    '<button type="button" class="btn-icon" data-gallery-delete="' + item.id + '" title="Delete" style="color:var(--danger)"><i data-lucide="trash-2"></i></button>' +
                    '</div></div>' +
                    (item.caption ? '<div class="text-muted text-xs mt-2">' + UI.escape(item.caption) + '</div>' : '') +
                    '</div></div>';
            }).join('') + '</div>';
            if (window.Icons && typeof window.Icons.refresh === 'function') { window.Icons.refresh(document); } else if (window.lucide) { try { window.lucide.createIcons(); } catch (error) { /* ignore */ } }
        }).catch(function (error) { Alerts.error(error.message); });
    }

    document.querySelectorAll('[data-open-service]').forEach(function (button) {
        button.addEventListener('click', function () { openForm(null); });
    });

    tableEl.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-action]');
        if (!trigger) {
            return;
        }
        var id = Number(trigger.getAttribute('data-id'));
        var action = trigger.getAttribute('data-action');
        if (action === 'edit' && records[id]) {
            openForm(records[id]);
        } else if (action === 'gallery' && records[id]) {
            openGallery(records[id]);
        } else if (action === 'delete' && records[id]) {
            Alerts.confirm({ title: 'Delete service?', text: 'Services referenced by packages or bookings cannot be deleted.', confirmText: 'Delete', danger: true }).then(function (confirmed) {
                if (!confirmed) {
                    return;
                }
                API.del('/services/' + id).then(function () {
                    Alerts.success('Service deleted.');
                    table.reload();
                }).catch(function (error) { Alerts.error(error.message); });
            });
        }
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var data = new FormData();
        UI.qsa('input[name], select[name], textarea[name]', form).forEach(function (field) {
            if (field.type === 'file') {
                if (field.files && field.files[0]) {
                    data.append(field.name, field.files[0]);
                }
                return;
            }
            if (field.type === 'checkbox') {
                data.append(field.name, field.checked ? '1' : '0');
                return;
            }
            data.append(field.name, field.value);
        });
        var id = data.get('id');
        UI.setLoading(submitBtn, true);
        var request = isEdit ? API.upload('POST', '/services/' + id, data) : API.upload('POST', '/services', data);
        request.then(function () {
            UI.setLoading(submitBtn, false);
            UI.closeModal(modal);
            Alerts.success(isEdit ? 'Service updated.' : 'Service created.');
            table.reload();
        }).catch(function (error) {
            UI.setLoading(submitBtn, false);
            if (error.errors) {
                UI.showErrors(form, error.errors);
            }
            Alerts.error(error.message);
        });
    });

    galleryModal.addEventListener('click', function (event) {
        var upload = event.target.closest('[data-gallery-upload]');
        var primary = event.target.closest('[data-gallery-primary]');
        var remove = event.target.closest('[data-gallery-delete]');

        if (upload) {
            var fileInput = galleryModal.querySelector('[data-gallery-file]');
            if (!fileInput.files || !fileInput.files[0]) {
                Alerts.warning('Choose an image to upload.');
                return;
            }
            var data = new FormData();
            data.append('image', fileInput.files[0]);
            data.append('caption', galleryModal.querySelector('[data-gallery-caption]').value);
            API.upload('POST', '/services/' + galleryService.id + '/gallery', data).then(function () {
                Alerts.success('Gallery image uploaded.');
                fileInput.value = '';
                galleryModal.querySelector('[data-gallery-caption]').value = '';
                loadGallery();
                table.reload();
            }).catch(function (error) { Alerts.error(error.message); });
        }

        if (primary) {
            API.post('/services/' + galleryService.id + '/gallery/' + primary.getAttribute('data-gallery-primary') + '/primary').then(function () {
                Alerts.success('Primary image updated.');
                loadGallery();
                table.reload();
            }).catch(function (error) { Alerts.error(error.message); });
        }

        if (remove) {
            API.del('/services/' + galleryService.id + '/gallery/' + remove.getAttribute('data-gallery-delete')).then(function () {
                Alerts.success('Gallery image deleted.');
                loadGallery();
                table.reload();
            }).catch(function (error) { Alerts.error(error.message); });
        }
    });

    API.get('/categories').then(function (data) {
        categories = Array.isArray(data) ? data : (data && data.items ? data.items : []);
        var filter = document.querySelector('[data-filter-category]');
        filter.innerHTML = '<option value="">All categories</option>' + categories.map(function (category) {
            return '<option value="' + category.id + '">' + UI.escape(category.name) + '</option>';
        }).join('');
        form.querySelector('[data-category-select]').innerHTML = categoryOptions(null);
    }).catch(function (error) { Alerts.error(error.message); });
})();
