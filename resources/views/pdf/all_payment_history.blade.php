<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ url('assets/images/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ url('assets/images/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ url('assets/images/favicon-16x16.png') }}">
    <title>Payment History</title>
    <style>
        body {
            font-family: 'Georgia', serif;
            margin: 0;
            padding: 0;
            color: #333;
            background-color: #f8f8f8;
        }

        .container {
            width: 90%;
            margin: 20px auto;
            padding: 20px;
            max-width: 900px;
            background-color: #ffffff;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #003366;
            padding-bottom: 10px;
        }

        .header img {
            max-width: 150px;
            height: auto;
        }

        .header h1 {
            margin: 10px 0;
            font-size: 28px;
            color: #003366;
            font-weight: 700;
        }

        .header p {
            margin: 5px 0;
            font-size: 16px;
            color: #666;
        }

        .content {
            margin: 20px 0;
        }

        h2 {
            font-size: 20px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 8px;
            color: #003366;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            font-size: 14px;
        }

        th {
            background-color: #f4f4f4;
            color: #003366;
            font-weight: 600;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }
    </style>

</head>
@foreach ($data as $item)

    <body>
        <div class="container">
            <div class="header">
                @if ($image)
                     
                    <h2>Payment History</h2>
                @else
                    <h1>Payment History</h1>
                    <h2>Payment History</h2>
                @endif

                <p>Invoice: {{ $item->order_id }}</p>
                <p>Date Generated: {{ now()->format('d-m-Y') }}</p>
            </div>

            <div class="content">
                <table>
                    <tr>
                        <td><strong>Invoice</strong></td>
                        <td>{{ $item->order_id ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Customer Name</strong></td>
                        <td>{{ $item->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Customer Email </strong></td>
                        <td>{{ $item->email ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Subscription Plan</strong></td>
                        <td>{{ ucfirst($item->plan) ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Plan Type</strong></td>
                        <td>{{ ucfirst($item->plan_type) ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Device Type</strong></td>
                        <td>{{ ucfirst($item->device_type) ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Amount Paid</strong></td>
                        <td>{{ $item->amount ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Payment Status</strong></td>
                        <td>{{ ucfirst($item->status) ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Purchased On</strong></td>
                        <td>{{ $item->purchased_on ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Expires On</strong></td>
                        <td>{{ $item->expires_on ?? 'N/A' }}</td>
                    </tr>
                </table>
            </div>

            <div class="footer">
                {{-- <p>&copy; {{ date('Y') }} All rights reserved.</p> --}}
            </div>
        </div>
    </body>
@endforeach

</html>
