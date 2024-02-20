<!DOCTYPE html>
<html lang="en">
<head>
    <title>Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        .container {
            width: 90%;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .header img {
            width: 150px;
        }
        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .invoice-info .info {
            width: 50%;
        }
        .invoice-info .info h2 {
            margin-bottom: 10px;
        }
        .invoice-info .info p {
            margin: 0;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
        }
        .invoice-table th, .invoice-table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        .invoice-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .invoice-table tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .invoice-table tbody tr:hover {
            background-color: #ddd;
        }
        .invoice-table tfoot tr td {
            text-align: right;
            font-weight: bold;
        }
        .invoice-table tfoot tr td {
            text-align: right;
            font-weight: bold;
        }
        .payments-info {
            margin-top: 20px;
        }
        .payments-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .payments-info table th, .payments-info table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        .payments-info table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .payments-info table tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .payments-info table tbody tr:hover {
            background-color: #ddd;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <img src="{{'/company_logo.png'}}" alt="Company Logo">
        <div class="info">
            <h2>Invoice</h2>
            <p>Invoice Number: <b>INV-000{{$assignment->id}}</b></p>
            <p>Date: <b>{{\Carbon\Carbon::parse($assignment->payment_info["invoice_date"])->format('d M Y')??"A"}}</b></p>
        </div>
    </div>
    <div class="invoice-info">
        <div class="info">
            <h2>Billing Info</h2>
            <p>Name: {{$assignment->client??"A"}}</p>
            <p>Address: {{$assignment->property_address??"A"}}</p>
            <p>City: {{$assignment->city??"A"}}</p>
            <p>State: {{$assignment->state??"A"}}</p>
        </div>
    </div>

    <div class="invoice-table">
        <table>
            <thead>
            <tr>
                <th>Loan #</th>
                <th>Investor #</th>
                <th>Property Address</th>
                <th>City</th>
                <th>State</th>
                <th>Amount</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>{{$assignment->loan_number??""}}</td>
                <td>{{$assignment->investor_number??""}}</td>
                <td>{{$assignment->property_address??""}}</td>
                <td>{{$assignment->city??""}}</td>
                <td>{{$assignment->state??""}}</td>
                <td class="text-right">$ {{$assignment->payment_info['invoice_amount']??"     -"}}</td>
            </tr>
            </tbody>
            <tfoot>
            <tr>
                <td colspan="3">Thank you for your business!</td>
                <td colspan="2">Total</td>
                <td class="text-right">$ {{$assignment->payment_info['invoice_amount']??"     -"}}</td>
            </tr>
            </tfoot>
        </table>
    </div>

    @if($assignment->payments())
        <div class="payments-info">
            <h2>Payments Info</h2>
            <table>
                <thead>
                <tr>
                    <th>Date</th>
                    <th class="text-right">Amount</th>
                </tr>
                </thead>
                <tbody>
                @foreach($assignment->payments()->get() as $payment)
                    <tr>
                        <td>{{\Carbon\Carbon::parse($payment->date)->format('d M Y')}}</td>
                        <td class="text-right">$ {{number_format($payment->amount,2)}}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
@endif
