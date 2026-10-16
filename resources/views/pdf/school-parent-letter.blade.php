<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>EmpowerED – Parent letter</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #1c2a3a; font-size: 14px; line-height: 1.6; }
        .banner { background: #1a5edb; color: #fff; padding: 22px 28px; text-align: center; }
        .banner h1 { margin: 0; font-size: 26px; }
        .banner p { margin: 6px 0 0; color: #d6e4ff; font-size: 13px; }
        .content { padding: 26px 28px; }
        .code { border: 2px dashed #1a5edb; background: #f4f7fb; text-align: center; padding: 14px; margin: 18px 0; }
        .code span { display: block; color: #8a97a6; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
        .code strong { color: #1a5edb; font-size: 28px; letter-spacing: 2px; }
        ol { padding-left: 20px; }
        .qr { text-align: center; margin-top: 18px; }
        .qr img { width: 170px; height: 170px; }
        .small { color: #8a97a6; font-size: 11px; margin-top: 26px; }
    </style>
</head>
<body>
    <div class="banner">
        <h1>EmpowerED</h1>
        <p>Teen Health &amp; Wellbeing App for Schools</p>
    </div>
    <div class="content">
        <p>Dear parent or guardian,</p>
        <p><strong>{{ $school->name }}</strong> is using EmpowerED, a health and wellbeing app for teens. You can create your own free parent account with your school's code.</p>

        <div class="code">
            <span>Your school code</span>
            <strong>{{ $school->school_code }}</strong>
        </div>

        <ol>
            <li>Download the EmpowerED app (scan the QR code below) or visit {{ $website }}.</li>
            <li>Choose <strong>Parent</strong>, then <strong>Sign up</strong>.</li>
            <li>Enter your details and the school code above.</li>
            <li>Confirm the code we text to your mobile number.</li>
        </ol>
        <p>Your child will receive their own login from the school.</p>

        <div class="qr">
            <img src="{{ $qr }}" alt="Scan to download the EmpowerED app">
        </div>

        <p class="small">Please keep the school code private to parents at {{ $school->name }}.</p>
    </div>
</body>
</html>
