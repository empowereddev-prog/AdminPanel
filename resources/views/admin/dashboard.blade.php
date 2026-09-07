@extends('layout.headerFooter')
@section('content')
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-bar input {
            padding: 10px;
            font-size: 16px;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .main {
            display: flex;
            flex-direction: column;
        }

        .dashboard-summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .summary-card {
            background: #f5f5f5;
            padding: 20px;
            text-align: center;
            border-radius: 10px;
            flex: 1;
            margin-right: 10px;
        }

        .summary-card:last-child {
            margin-right: 0;
        }

        .active-plans {
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
        }

        .plan-filter button,
        .plan-filter input,
        .plan-filter span {
            margin-right: 10px;
        }

        .plan-filter button:last-child,
        .plan-filter input:last-child,
        .plan-filter span:last-child {
            margin-right: 0;
        }

        .plans-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .plans-table th,
        .plans-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .plans-table th {
            background: #f5f5f5;
        }

        .pagination {
            display: flex;
            justify-content: flex-end;
        }

        .filter-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .plan-filter {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .plan-filter>div {
            margin-right: 10px;
        }

        .plan-filter button {
            padding: 5px 10px;
            cursor: pointer;
        }
    </style>
    <div class="content-wrapper">
        <div class="content cards-wrapper">
            <div class="title_left">
                <h2>Dashboard</h2>
            </div>
            <div class="row">
                @if (hasPermission(2))
                    <div class="col-xl-4 col-md-6 col-sm-12" style="--clr: #D9F7E8">
                        <div class="card card-default">
                            <a href="{{ route('user.index') }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-left">
                                        <p>Total User</p>
                                        <h2>{{ $totalUser }}</h2>
                                    </div>
                                    <div class="icon">
                                        <img src="{{ url('assets/images/user.svg') }}">
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                @endif
                @if (hasPermission(3))
                    <div class="col-xl-4 col-md-6 col-sm-12" style="--clr: #FDF1F4">
                        <div class="card card-default">
                            <a href="{{ route('school.index') }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-left">
                                        <p>Total School</p>
                                        <h2>{{ $totalSchool }}</h2>
                                    </div>
                                    <div class="icon">
                                        <img src="{{ url('assets/images/school.svg') }}">
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                @endif
                
                 {{-- <div class="col-xl-4 col-md-6 col-sm-12" style="--clr: #FFDED1">
                    <div class="card card-default">
                        <a href="{{ route('user.index') }}">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-left">
                                    <p>Total Child</p>
                                    <h2>{{ $totalChild }}</h2>
                                </div>
                                <div class="icon">
                                <img src="{{ url('assets/images/child.svg') }}">
                                </div>
                            </div>
                        </a>
                    </div>
                </div>   --}}
                @if (hasPermission(23))
                    <div class="col-xl-4 col-md-6 col-sm-12" style="--clr: #FDF1F4">
                        <div class="card card-default">
                            <a href="{{ route('payment.history_index') }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-left">
                                        <p>Payment History</p>
                                        <h2>{{ $paymentHistory }}</h2>
                                    </div>
                                    <div class="icon">
                                        <img src="{{ url('assets/images/payment-history.svg') }}">
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>

    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/luxon/3.0.0/luxon.min.js"></script>
    <script type="text/javascript">
        //    $(function() {
        //     const { DateTime } = luxon;

        //     $.ajaxSetup({
        //         headers: {
        //             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        //         }
        //     });

        //     const currentUrl = window.location.href;
        //     const savedPage = localStorage.getItem('dataTablePage_' + encodeURIComponent(currentUrl));

        //     const desktopTable = $('#desktop-table').DataTable({
        //         processing: true,
        //         serverSide: true,
        //         ajax: {
        //             url: "{{ route('admin.active_plan') }}",
        //             type: 'GET',
        //             data: function(d) {
        //                 d.device_type = 'desktop';
        //                 d.date_range = $('#date-range-filter').val();
        //                 d.plan = $('#plan-filter').val();
        //                 d.sort = $('#sort-filter').val() || 'desc';
        //             }
        //         },
        //         columns: [
        //             { data: 'order_id', name: 'order_id',orderable: true,searchable: true },
        //             { data: 'license_key', name: 'license_key' ,orderable: true,searchable: true},
        //             { data: 'email', name: 'email', render: data => data || 'N/A' },
        //             { data: 'plan', name: 'plan' },
        //             { data: 'plan_type', name: 'plan_type', render: formatColumnName },
        //             { data: 'purchased_on', name: 'purchased_on' },
        //             { data: 'expires_on', name: 'expires_on' },
        //             { data: 'action', name: 'action', orderable: false },
        //             { data: 'id', name: 'id', visible: false }  // Add this line for the hidden ID column

        //         ],
        //         order:[8,'desc'],
        //         initComplete: function() {
        //             $(this.api().table().container()).find('input[type="search"]').attr('placeholder', 'Search');

        //         }
        //     });
        //       const mobileTable = $('#mobile-table').DataTable({
        //         processing: true,
        //         serverSide: true,
        //         ajax: {
        //             url: "{{ route('admin.active_plan') }}",
        //             type: 'GET',
        //             data: function(d) {
        //                 d.device_type = 'mobile';
        //                 d.date_range = $('#date-range-filter').val();
        //                 d.plan = $('#plan-filter').val();
        //                 d.sort = $('#sort-filter').val() || 'desc';
        //             }
        //         },
        //         columns: [
        //             { data: 'order_id', name: 'order_id' ,orderable: true,searchable: true},
        //             { data: 'license_key', name: 'license_key' ,orderable: true,searchable: true},
        //             { data: 'original_transaction_id', name: 'original_transaction_id', render: data => data || 'N/A' },
        //             { data: 'plan', name: 'plan' },
        //             { data: 'plan_type', name: 'plan_type', render: formatColumnName },
        //             { data: 'purchased_on', name: 'purchased_on' },
        //             { data: 'expires_on', name: 'expires_on' },
        //             { data: 'action', name: 'action', orderable: false },
        //             { data: 'id', name: 'id', visible: false }  // Add this line for the hidden ID column
        //         ],
        //         order:[8,'desc'],
        //         initComplete: function() {
        //             $(this.api().table().container()).find('input[type="search"]').attr('placeholder', 'Search');
        //         }
        //     });


        //     $('input[type="search"]').attr('placeholder', 'Search');
        //     $('#desktop-table-container').show();
        //     $('#mobile-table-container').hide();

        //     $('#desktop-filter').on('click', function() {
        //         $('#desktop-filter').addClass('active');
        //         $('#mobile-filter').removeClass('active');
        //         $('#desktop-table-container').show();
        //         $('#mobile-table-container').hide();
        //         $('#desktop-table').show();
        //         $('#mobile-table').hide();
        //         $("#mobile-table_wrapper").hide();
        //         $("#desktop-table_wrapper").show();
        //         desktopTable.ajax.reload();
        //     });

        //     $('#mobile-filter').on('click', function() {
        //         $('#desktop-filter').removeClass('active');
        //         $('#mobile-filter').addClass('active');
        //         $('#desktop-table-container').hide();
        //         $('#mobile-table-container').show();
        //         $('#desktop-table').hide();
        //         $('#mobile-table').show();
        //         $("#mobile-table_wrapper").show();
        //         $("#desktop-table_wrapper").hide();
        //         mobileTable.ajax.reload();

        //     });
        //     $('#reset').on('click', function() {
        //         $('input[name="daterange"]').val("{{ formatInitalDateRangetodMY() }}");
        //         $('#plan-filter').val('Plan');
        //         $('#sort-filter').val('sortBy');
        //         if ($('#desktop-filter').hasClass('active')) {
        //             desktopTable.ajax.reload();
        //         } else {
        //             mobileTable.ajax.reload();
        //         }
        //     });

        //     $('.filter-row input, .filter-row select').on('change', function() {
        //         if ($('#desktop-filter').hasClass('active')) {
        //             desktopTable.ajax.reload();
        //         } else {
        //             mobileTable.ajax.reload();
        //         }
        //     });

        //     $('input[name="daterange"]').daterangepicker({
        //         opens: 'right',
        //         showDropdowns: true,
        //         startDate:  DateTime.now().minus({ years: 1 }).startOf('year').toJSDate(), // Start date 1 year ago
        //         endDate: DateTime.now().endOf('day').toJSDate(),
        //         locale: {
        //             format: 'DD-MM-YYYY',
        //             cancelLabel: 'Clear'
        //         },
        //         ranges: {
        //             'Today': [DateTime.now().startOf('day').toJSDate(), DateTime.now().endOf('day').toJSDate()],
        //             'Yesterday': [
        //                 DateTime.now().minus({ days: 1 }).startOf('day').toJSDate(),
        //                 DateTime.now().minus({ days: 1 }).endOf('day').toJSDate()
        //             ],
        //             'Last 7 Days': [
        //                 DateTime.now().minus({ days: 6 }).startOf('day').toJSDate(),
        //                 DateTime.now().endOf('day').toJSDate()
        //             ],
        //             'Last 30 Days': [
        //                 DateTime.now().minus({ days: 29 }).startOf('day').toJSDate(),
        //                 DateTime.now().endOf('day').toJSDate()
        //             ],
        //             'This Month': [
        //                 DateTime.now().startOf('month').toJSDate(),
        //                 DateTime.now().endOf('month').toJSDate()
        //             ],
        //             'Last Month': [
        //                 DateTime.now().minus({ months: 1 }).startOf('month').toJSDate(),
        //                 DateTime.now().minus({ months: 1 }).endOf('month').toJSDate()
        //             ],
        //             'This Year': [
        //                 DateTime.now().startOf('year').toJSDate(),
        //                 DateTime.now().endOf('year').toJSDate()
        //             ],
        //             'Last Year': [
        //                 DateTime.now().minus({ years: 1 }).startOf('year').toJSDate(),
        //                 DateTime.now().minus({ years: 1 }).endOf('year').toJSDate()
        //             ]
        //         }
        //     }).on('apply.daterangepicker', function() {
        //         if ($('#desktop-filter').hasClass('active')) {
        //             desktopTable.ajax.reload();
        //         } else {
        //             mobileTable.ajax.reload();
        //         }
        //     }).on('cancel.daterangepicker', function() {
        //         $(this).val("{{ formatInitalDateRangetodMY() }}");
        //         if ($('#desktop-filter').hasClass('active')) {
        //             desktopTable.ajax.reload();
        //         } else {
        //             mobileTable.ajax.reload();
        //         }
        //     });

        //     $('#desktop-table, #mobile-table').on('page.dt', function() {
        //         const page = $(this).DataTable().page();
        //         localStorage.setItem('dataTablePage_' + encodeURIComponent(currentUrl), page);
        //     });

        // });
        function formatColumnName(name) {
            return name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }
    </script>
@endsection
