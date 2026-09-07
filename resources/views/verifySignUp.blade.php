
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <meta http-equiv="x-ua-compatible" content="ie=edge">
      <title>Empower Ed Client Healthcare | Admin</title>
      <meta name="csrf-token" content="{{csrf_token()}}">
      <!-- Font Awesome Icons -->
      <link rel="shortcut icon" href="{{ asset('public/images/Logo_v-01.jpg') }}" />
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
  <style>
  .green-success-btn{
    font-size: 19px;
    text-align: center;
    border-left: 10px solid #3c763d;
    border-right: 10px solid #3c763d;
    border-radius: 0px;
    padding: 18px;
  }
  .green-danger-btn{
    font-size: 19px;
    text-align: center;
    border-left: 10px solid #FF5733;
    border-right: 10px solid #FF5733;
    border-radius: 0px;
    padding: 18px;
  }
</style>
</head>

<body>
​
<div class="container">
  @if(@$success)
  <div class="alert alert-success green-success-btn">
    <strong>Success!</strong> {{$success}}
  </div>
  @endif
  @if(@$message)
  <div class="alert alert-danger green-danger-btn">
    <strong>{{$message}}</strong> 
  @endif
  @if(@$error)
  <div class="alert alert-danger green-danger-btn">
    <strong>{{$error}}</strong> 
  @endif
</div>
​
</body>
</html>
​