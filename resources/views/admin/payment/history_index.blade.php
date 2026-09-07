@extends('layout.headerFooter')

@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left d-flex justify-content-between">
                <span>
                    <h2>Payment History</h2>
                </span>
                <span>
                </span>
            </div>

            <div class="card card-default">
                <div class="card-body">
                    <div class="filter-row">
                        <div>
                            <div class="view-on">
                            </div>
                            <div class="filters-and-sortings">
                                <div class="filter-and-sortings-inner">
                                    <input type="text" class="daterange" id="date-range-filter" name="daterange"
                                        value="{{ formatInitalDateRangetodMY() }}" />
                                    <select class="pan" id="status-filter">
                                        <option value="status" selected>Status</option>
                                        <option value="successful">Successful</option>
                                        <option value="unsuccessful">Unsuccessful</option>
                                    </select>
                                    <!-- <select class="sort-by" id="sort-filter">
                                        <option value="sortBy" selected>Sort By</option>
                                        <option value="ASC">Asc</option>
                                        <option value="DESC">Desc</option>
                                    </select> -->
                                    <button id="reset">
                                        <img src="{{url('assets/images/reset.png')}}" alt="Reset">
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <table class="table" id="data">
                        <thead>
                            <tr>
                                <th scope="col" style="min-width: 60px;">#</th>
                                <th scope="col" style="min-width: 60px;"> User Name </th>
                                <th scope="col" style="min-width: 60px;">Subscription Type</th>
                                <th scope="col" style="min-width: 60px;">Amount($)</th>
                                <th scope="col" style="min-width: 60px;">Purchased On</th>
                                <th scope="col" style="min-width: 60px;">Status</th>
                                @if(!empty($pre))<th scope="col"  style="min-width: 60px;">Action(s)</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/luxon/3.0.0/luxon.min.js"></script>
    <script type="text/javascript">
        $(function() {

            const {
                DateTime
            } = luxon;
            var startYear = 2000;
            var currentYear = new Date().getFullYear();
            var table;

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            var currentUrl = window.location.href;
            var savedPage = localStorage.getItem('dataTablePage_' + encodeURIComponent(currentUrl));

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            var currentUrl = window.location.href;
            var savedPage = localStorage.getItem('dataTablePage_' + encodeURIComponent(currentUrl));
            var check = "{{$pre->is_modify}}";
            if(check == 'no'){
            var table = $('#data').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('payment.history_index') }}",
                    type: 'GET',
                    data: function(d) {
                        d.date_range = $('#date-range-filter').val();
                        d.status = $('#status-filter').val();
                        d.sort = $('#sort-filter').val() ?? 'desc';
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        searchable: false, orderable: false,
                    },
                    {
                        data: 'user_name',
                        name: 'user_name',
                        searchable: true, orderable: true,
                        render: function(data, type, row) {
                            return data ? data : 'N/A';
                        } 
                    },
                    { data: 'subscription_type', name: 'subscription_type', searchable: true, orderable: true, render: function(data, type, row) {
                        return data ? data : 'N/A';
                    } },
                    {
                        data: 'price',
                        name: 'price',
                        searchable: false, orderable: false,
                        render: function (data, type, row) {
                            return `${data} `;
                        }
                    },
                    {
                    data : {'_': 'start_date', 'sort': 'start_date'},
                    name: 'start_date',
                    orderable: true,
                    searchable:true
                  },
                    {
                        data: 'status',
                        name: 'status',
                    },
                    {
                        data: 'action',
                        name: 'action',
                        searchable: false,
                        orderable: false
                    },
                      { data: 'id', name: 'id', visible: false }
                ],
                // order: [[7, 'desc']],
                // columnDefs: [{
                //     className: 'text-center',
                //     targets: [3, 6, 7]
                // }],
                initComplete: function(settings, json) {
                    var pageNumber = parseInt(savedPage);
                    var totalPages = table.page.info().pages;

                    if (!isNaN(pageNumber) && pageNumber >= 0 && pageNumber < totalPages) {
                        setTimeout(function() {
                            table.page(pageNumber).draw(false);
                        }, 100);
                    } else {
                        table.page(0).draw(false);
                    }
                },
                language: {
                    paginate: {
                        previous: "<",
                        next: ">"
                    }
                }
            });
        }else{
            var table = $('#data').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('payment.history_index') }}",
                    type: 'GET',
                    data: function(d) {
                        d.date_range = $('#date-range-filter').val();
                        d.status = $('#status-filter').val();
                        // d.sort = $('#sort-filter').val() ?? 'desc';
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        searchable: false, orderable: false,
                    },
                    {
                        data: 'user_name',
                        name: 'user_name',
                        searchable: true, orderable: true,
                        render: function(data, type, row) {
                            return data ? data : 'N/A';
                        } 
                    },
                    { data: 'subscription_type', name: 'subscription_type', searchable: true, orderable: true, render: function(data, type, row) {
                        return data ? data : 'N/A';
                    } },
                    {
                        data: 'price',
                        name: 'price',
                        searchable: false, orderable: false,
                        render: function (data, type, row) {
                            return `${data} `;
                        }
                    },
                    {
                    data : {'_': 'start_date', 'sort': 'start_date'},
                    name: 'start_date',
                    orderable: true,
                    searchable:true
                  },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                    },
                    {
                        data: 'action',
                        name: 'action',
                        searchable: false,
                        orderable: false
                    },
                      { data: 'id', name: 'id', visible: false }
                ],
                // order: [[8, 'desc']],
                // columnDefs: [{
                //     className: 'text-center',
                //     targets: [3, 6, 7]
                // }],
                initComplete: function(settings, json) {
                    var pageNumber = parseInt(savedPage);
                    var totalPages = table.page.info().pages;

                    if (!isNaN(pageNumber) && pageNumber >= 0 && pageNumber < totalPages) {
                        setTimeout(function() {
                            table.page(pageNumber).draw(false);
                        }, 100);
                    } else {
                        table.page(0).draw(false);
                    }
                },
                language: {
                    paginate: {
                        previous: "<",
                        next: ">"
                    }
                }
            });
        }

            table.on('page.dt', function() {
                var info = table.page.info();
                localStorage.setItem('dataTablePage_' + encodeURIComponent(currentUrl), info.page);
            });

            $('input[type="search"]').attr('placeholder', 'Search');

            $('#reset').on('click', function() {
                $('input[name="daterange"]').val("{{ formatInitalDateRangetodMY() }}");
                $('#status-filter').val('status');
                // $('#sort-filter').val('sortBy');
                table.ajax.reload();
            });

            $('.filter-row input, .filter-row select').on('change', function() {
                table.ajax.reload();
            });

            // Date Range Picker

            const today = DateTime.now().startOf('day');
            const endOfDay = DateTime.now().endOf('day');
            $('input[name="daterange"]').daterangepicker({
                opens: 'right',
                showDropdowns: true,
                startDate: DateTime.now().minus({ years: 1 }).startOf('year').toJSDate(), // Start date 1 year ago
                endDate: today.toJSDate(),
                locale: {
                    format: 'DD-MM-YYYY',
                    cancelLabel: 'Clear'
                },
                ranges: {
                'Today': [DateTime.now().startOf('day').toJSDate(), DateTime.now().endOf('day').toJSDate()],
                'Yesterday': [DateTime.now().minus({ days: 1 }).startOf('day').toJSDate(), DateTime.now().minus({ days: 1 }).endOf('day').toJSDate()],
                'Last 7 Days': [DateTime.now().minus({ days: 6 }).startOf('day').toJSDate(), DateTime.now().endOf('day').toJSDate()],
                'Last 30 Days': [DateTime.now().minus({ days: 29 }).startOf('day').toJSDate(), DateTime.now().endOf('day').toJSDate()],
                'This Month': [DateTime.now().startOf('month').toJSDate(), DateTime.now().endOf('month').toJSDate()],
                'Last Month': [DateTime.now().minus({ months: 1 }).startOf('month').toJSDate(), DateTime.now().minus({ months: 1 }).endOf('month').toJSDate()],
                'This Year': [
                               DateTime.now().startOf('year').toJSDate(),
                               DateTime.now().endOf('year').toJSDate()
                 ],
                'Last Year': [
                             DateTime.now().minus({ years: 1 }).startOf('year').toJSDate(),
                             DateTime.now().minus({ years: 1 }).endOf('year').toJSDate()
                 ]
                }
           
            });

            $('input[name="daterange"]').on('apply.daterangepicker', function(ev, picker) {
                table.ajax.reload();
            });

            $('input[name="daterange"]').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val("{{ formatInitalDateRangetodMY() }}");
                $(this).trigger('change');

                table.ajax.reload();
            });



            function formatColumnName(name) {
                return name.replace(/_/g, ' ').replace(/\b\w/g, function(l) {
                    return l.toUpperCase();
                });
            }
        });
    </script>
@endsection
