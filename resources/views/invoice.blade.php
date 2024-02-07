<!DOCTYPE html>
<html lang="en">
<head>
    <title>Invoice</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="max-w-4xl mx-auto p-8 bg-white shadow-lg h-[278mm]">
    <div class="flex justify-between items-center">
        <div>
            <img src="{{public_path('company_logo.png')}}" alt="Company Logo" class="max-w-xs">
        </div>
        <div>
            <p class="text-3xl text-right font-bold text-gray-800">INVOICE</p>
        </div>
    </div>
    <div>
        <div class="text-right flex justify-between px-1 text-sm">
            <div class="mb-2">
                Invoice Number: <b>INV-000{{$assignment->id}}</b>
            </div>
            <div>
                Date: <b>{{\Carbon\Carbon::parse($assignment->payment_info["invoice_date"])->format('d M Y')??"A"}}</b>
            </div>
        </div>
    </div>
    <div class="mt-8 flex justify-between text-sm">
        <div class="p-1">
            <b>AMS Real Estate Services, Inc.</b><br>
            310 Comal St. Bld. A, Ste. 301<br>
            Austin, TX 78702<br>
            USA
        </div>
        <div class="mt-4 text-right text-sm text-sm">
            Terms: <p class="font-bold">Upon Receipt</p>
        </div>
    </div>
    <div class="mt-8 grid grid-cols-4 gap-4 text-sm">
        <div class="">
            <div class="font-bold mb-2 font-bold mb-0.5 border border-gray-300 p-1 bg-gray-200">Bill To:</div>
            <div>
                <b>{{$assignment->client??"A"}}</b><br>
                {{$assignment->property_name??"A"}}<br>
                {{$assignment->city??"A"}}, {{$assignment->state??"A"}}, {{$assignment->zip??"A"}}<br>
                USA
            </div>
        </div>
        <div class="col-start-3 col-span-2 text-sm">
            <div class="mb-2 font-bold border border-gray-300 p-1 bg-gray-200">Remittance Information:</div>
            <div>
                <b>Bank: </b>Wells Fargo Bank, NA<br>
                <b>Account Name: </b>AMS Real Estate Services, Inc.<br>
                <b>Account #: </b>8045689166<br>
                <b>Routing # for Wires: </b>121000248<br>
                <b>Routing # for ACH: </b>111900659
            </div>
        </div>
    </div>
    <div class="mt-8">
        <table class="w-full border border-gray-300 text-left text-sm">
            <thead>
            <tr class="bg-gray-300">
                <th class="px-4 py-2">Loan #</th>
                <th class="px-4 py-2">Investor #</th>
                <th class="px-4 py-2">Property Address</th>
                <th class="px-4 py-2">City</th>
                <th class="px-4 py-2">State</th>
                <th class="px-4 py-2">Amount</th>
            </tr>
            </thead>
            <tbody>
            <!-- Add your dynamic data here -->
            <tr>
                <td class="border px-4 py-2">{{$assignment->loan_number??""}}</td>
                <td class="border px-4 py-2">{{$assignment->investor_number??""}}</td>
                <td class="border px-4 py-2">{{$assignment->property_address??""}}</td>
                <td class="border px-4 py-2">{{$assignment->city??""}}</td>
                <td class="border px-4 py-2">{{$assignment->state??""}}</td>
                <td class="border px-4 py-2 text-right">$ {{$assignment->payment_info['invoice_amount']??"     -"}}</td>
            </tr>
            <tr>
                <td class="border px-4 py-2 italic" colspan="3">Thank you for your business!</td>
                <td class="border px-4 py-2 font-bold" colspan="2">Total</td>
                <td class="border px-4 py-2 text-right">$ {{$assignment->payment_info['invoice_amount']??"     -"}}</td>
            </tr>
            <!-- Repeat for each item -->
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
