<?php 
// require('includes/application_top.php');  
if(isset($_SESSION['TMPLOGIN']) === true) {
  header("Location: '" . route('website') . "'");
}
 $error = false;  
    // if(isset($_POST['btn_submit'])) {
    //     $login_user = trim($_POST['login_user']);
    //     $login_password = trim($_POST['login_password']);
    //     if($login_user == 'nh8to9' && $login_password == '8goto9' ) {
    //         $_SESSION['TMPLOGIN'] = true;
    //         $error = false;  
    //         header("Location: temp-login.blade.php");
    //         exit();
    //     } else {
    //       $error = true;  
    //     }
    // }
    // if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //     $username = $_POST['username'];
    //     $password = $_POST['password'];
    
    //     // Replace these with actual credentials
    //     $correct_username = "notepad";
    //     $correct_password = "notepad@123";
    
    //     // Validate credentials
    //     if ($username === $correct_username && $password === $correct_password) {
    //         // Set session variables
    //         $_SESSION['loggedin'] = true;
    //         $_SESSION['username'] = $username;
    
    //         // Show alert and redirect to dashboard
    //         echo "<script>
    //                 alert('Login successful!');
    //                 window.location.href='" . route('website') . "';
    //               </script>";
    //         exit;
    //     } else {
    //         $login_error = "Invalid username or password!";
    //     }
    // }
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
     <title> Temp Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes">    
    <link rel="shortcut icon" href="{{ url('assets/images/favicon.png') }}" type="image/x-icon">
    <link rel='stylesheet prefetch' href='https://fonts.googleapis.com/css?family=Open+Sans'>
   
            <link id="main-css-href" rel="stylesheet" href="{{ url('assets/css/style.css') }}" />

</head>


<!-- <body>
    
    <div class="cont">
        <div class="demo">
            <div class="login">
                <div class="login__check">
                    
                </div>
                <form method="POST" autocomplete="off" action="{{url('temp-login')}}">
                @csrf
                    <div class="login__form">
                    @if ($errors->any())
                        <p class="error">{{ $errors->first('login') }}</p>
                    @endif
                        <div class="login__row">
                            
                            <input type="text" class="login__input name" name="login_user" autocomplete="off" placeholder="Username" />
                        </div>
                        <div class="login__row">
                           
                            <input type="password" class="login__input pass" name="login_password" autocomplete="off" placeholder="Password" />
                        </div>
                        <input type="submit" name="btn_submit" class="login__submit" value="Login" />
                    </div>
                </form>
            </div>
        </div>
    </div>
</body> -->

<body class="login-page" id="body">
    <div class="container">
        <div class="d-flex flex-column justify-content-between">
            <div class="form-wrapper row justify-content-center align-items-center">
                <div class="col-sm-12 col-md-5 col-lg-5 branding">
                    <div class="d-flex align-items-end">
                        <a href="#">
                            <img src="../assets/images/AE-logo.png" alt="AE Logo"/>
                        </a>
                    </div>
                </div>
                <div class="col-sm-12 col-md-7 col-lg-7">
                    <div class="card form-container">
                        <div class="card-header border-0 p-0 bg-white">
                            <h2 class="text-center">Temp Login for Website</h2>
                            <p class="text-center">Please enter username and password to continue</p>
                        </div>
                        <div class="card-body p-0">
                          
                       
                            <form class="admin-form login" autocomplete="off" action="{{url('temp-login')}}" method="post" >
                                @csrf
                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="email">Username:</label>
                                        <input type="text"
                                        class="form-control input-lg @if ($errors->has('email')) border-danger @endif"
                                        id="login_user" name="login_user" aria-describedby="emailHelp"
                                        placeholder="Username">
                                        
                                    </div>
                                    <div class="form-group col-md-12 field password">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <label for="email">Password</label>
                                            <!-- <a class="forget-password" href="{{route('forgot-password')}}">Forgot Password?</a> -->
                                        </div>
                                        <input type="password"
                                            class="form-control input-lg @if ($errors->has('password')) border-danger @endif"
                                            id="login_password" name="login_password" placeholder="Password">
                                        
                                    </div>
                                    <div class="col-md-12">
                                        <div class="d-flex justify-content-between mb-3">
                                        
                                        </div>
                                        <input type="submit" name="btn_submit" style="background-color:#007bfe;" class="login__submit btn btn-primary d-block w-100 submit" value="Login" />
                                    </div>
                                    @if ($errors->any())
                                        <p style="
                                                color: red;
                                                font-size: 12px;
                                                border: 1px solid red;
                                                border-radius: 6px;
                                                padding: 3px 2px;
                                                text-align:center;
                                                margin-left: 35%;
                                                margin-top: 3%;
                                                ">{{ $errors->first('login') }}</p>
                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ url('assets/plugins/nprogress/nprogress.js') }}"></script>

</body>

</html>
