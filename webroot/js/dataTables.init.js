/**
 * DataTables initialiser Logic.
 */
function DataTablesInit(options)
{
    this.options = options;

    var table = this.dataTable();

    if (this.options.batch) {
        this.batchToggle(table);
        this.batchSelect(table);
        this.batchClick(table);
    }

    return table;
}

DataTablesInit.prototype = {

    dataTable: function () {
        var that = this;

        var settings = {
            sDom:
            '<"row view-filter"<"col-sm-12"<"pull-left"l><"pull-right"f><"clearfix">>>t<"row view-pager"<"col-sm-12"<"text-center"p>>>',
            oLanguage: {
                oPaginate: {
                    sFirst: "First page", // This is the link to the first page
                    sPrevious:
                    "<i aria-hidden='true' class='qobrix-icon qobo-angle-left font-size-10'></i>", // This is the link to the previous page
                    sNext:
                    "<i aria-hidden='true' class='qobrix-icon qobo-angle-right font-size-10'></i>", // This is the link to the next page
                    sLast: "Last page", // This is the link to the last page
                },
            },
            searching: false,
            lengthMenu: [5, 10, 25, 50, 100],
            pageLength: 10,
            language: {
                processing:
                  '<i class="qobrix-icon qobo-refresh fa-spin fa-fw"></i> Processing...',
                sLengthMenu: "_MENU_",
            },
            columnDefs: [
                {targets: [-1], orderable: false}
            ],
            fnDrawCallback: function (oSettings) {
                if (oSettings._iDisplayLength > oSettings.fnRecordsDisplay()) {
                    $(oSettings.nTableWrapper).find(".dataTables_paginate").hide();
                } else {
                    $(oSettings.nTableWrapper).find(".dataTables_paginate").show();
                }
            }
        };

        settings.order = [ this.options.order ? this.options.order : [0, 'asc'] ];

        // ajax specific options
        if (this.options.ajax) {
            settings.processing = true;
            settings.serverSide = true;
            settings.deferRender = true;
            settings.ajax = {
                url: this.options.ajax.url,
                headers: {
                    'Authorization': 'Bearer ' + this.options.ajax.token
                },
                data: function (d) {
                    if (that.options.ajax.extras) {
                        d = $.extend({}, d, that.options.ajax.extras);
                    }

                    d.limit = d.length;
                    d.page = 1 + d.start / d.length;

                    var sort = that.options.ajax.columns[d.order[0].column];

                    // sort by virtual field
                    if (that.options.ajax.hasOwnProperty('virtualColumns') && that.options.ajax.virtualColumns[sort]) {
                        sort = that.options.ajax.virtualColumns[sort].join();
                    }

                    // sort by combined field
                    if (that.options.ajax.hasOwnProperty('combinedColumns') && that.options.ajax.combinedColumns[sort]) {
                        sort = that.options.ajax.combinedColumns[sort].join();
                    }

                    d.sort = sort;
                    d.direction = d.order[0].dir;

                    return d;
                },
                dataFilter: function (d) {
                    d = jQuery.parseJSON(d);
                    d.recordsTotal = d.pagination.count;
                    d.recordsFiltered = d.pagination.count;

                    d.data = that.dataFormatter(d.data);

                    return JSON.stringify(d);
                }
            };
        }

        // batch specific options
        var _self = this;
        settings.createdRow = function ( row, data, index ) {
            if (_self.options.batch) {
                $(row).attr('data-id', data[0]);
                $('td', row).eq(0).text('');
            }
            var $topRow = $(this).find(">thead>tr,>tr").eq(0);
            $.each($("td", row), function (colIndex) {
                var $html = $(this).html();
                var label = $topRow.find(">td,>th").eq(colIndex).html().trim();
                var emptyVal = "";
                var assignedClass = "";
                var specialClass = "";
                var selectionVal = "--";

                if (colIndex === 0) {
                    specialClass = "key-select";
                    selectionVal = "Select";
                }
                
                var trimmedLabel = label.toLowerCase().replace(/\s+/g, "_");
                if (trimmedLabel === "assigned_to" || trimmedLabel === "country") {
                    assignedClass = "center-image";
                } else if (trimmedLabel === "featured_photo") {
                    $(this).find("a img").addClass("lightbox-image-source");
                    assignedClass = "no-overflow-img";
                } else if (trimmedLabel === "files") {
                    $(this).find("a img").addClass("lightbox-image-source");
                    assignedClass = "no-overflow-img file-image";
                }

                if ($html.trim() == "") {
                    emptyVal += '<div class ="val ' + selectionVal + '"></div>';
                } else {
                    $(this)
                    .contents()
                    .wrapAll('<div class="val ' + assignedClass + '"></div>');
                }

                $(this).prepend(
                    '<div class="key ' + specialClass + '"> ' + label + '</div>' + emptyVal
                );
            });
        };
        if (_self.options.batch) {
            settings.select = {
                style: 'multi',
                selector: 'td:first-child'
            };

            settings.columnDefs[0].targets.push(0);
            settings.columnDefs.push({targets: [0], className: 'select-checkbox'});
        }

        // state specific options
        if (this.options.state) {
            settings.stateSave = true;
            settings.stateDuration = this.options.state.duration;
        }

        // Fetching alerted errors into callback
        $.fn.dataTable.ext.errMode = function (settings, techNote, message) {
            console.log(message);
        };

        var table = $(this.options.table_id).DataTable(settings);

        return table;
    },

    dataFormatter: function (data) {
        var result = [];

        var columns = this.options.ajax.columns;
        var combinedColumns = this.options.ajax.hasOwnProperty('combinedColumns') ?
            this.options.ajax.combinedColumns :
            [];

        var length = columns.length;
        for (var key in data) {
            if (!data.hasOwnProperty(key)) {
                continue;
            }

            result[key] = [];
            for (i = 0; i < length; i++) {
                var column = columns[i];
                var value = [];

                // normal field
                if (data[key][column]) {
                    value.push(data[key][column]);
                }

                // combined field
                if (combinedColumns[column]) {
                    var len = combinedColumns[column].length;
                    for (x = 0; x < len; x++) {
                        value.push(data[key][combinedColumns[column][x]]);
                    }
                }

                result[key].push(value.join(' '));
            }
        }

        return result;
    },

    batchToggle: function (table) {
        var that = this;

        table.on('select', function () {
            $(that.options.batch.id).attr('disabled', false);
        });

        table.on('deselect', function (e, dt, type, indexes) {
            if (0 === table.rows('.selected').count()) {
                $(that.options.batch.id).attr('disabled', true);
            }
        });
    },

    batchClick: function (table) {
        $('*[data-batch="1"]').click(function (e) {
            e.preventDefault();

            var confirmed = true;
            // show confirmation message, if required
            if ($(this).data('batch-confirm')) {
                confirmed = confirm($(this).data('batch-confirm'));
            }

            if (!confirmed) {
                return;
            }

            var $form = $(
                '<form method="post" action="' + $(this).data('batch-url') + '"></form>'
            );

            if ($(this).data('csrf-token')) {
                $form.append('<input type="text" name="_csrfToken" value="' + $(this).data('csrf-token') + '">');
            }

            $('#' + table.table().node().id + ' tr.selected').each(function () {
                $form.append('<input type="text" name="batch[ids][]" value="' + $(this).attr('data-id') + '">');
            });

            $form.appendTo('body').submit();
        });
    },

    batchSelect: function (table) {
        // select/deselect all table rows
        // @link https://stackoverflow.com/questions/42570465/datatables-select-all-checkbox?answertab=active#tab-top
        table.on('click', 'th.select-checkbox', function () {
            if ($('th.select-checkbox').hasClass('selected')) {
                table.rows().deselect();
                $('th.select-checkbox').removeClass('selected');
            } else {
                table.rows().select();
                $('th.select-checkbox').addClass('selected');
            }
        });

        // check/uncheck select-all checkbox based on rows select/deselect triggering
        // @link https://stackoverflow.com/questions/42570465/datatables-select-all-checkbox?answertab=active#tab-top
        table.on('select deselect', function () {
            if (table.rows({
                selected: true
            }).count() !== table.rows().count()) {
                $('th.select-checkbox').removeClass('selected');
            } else {
                $('th.select-checkbox').addClass('selected');
            }
        });
    }
};
