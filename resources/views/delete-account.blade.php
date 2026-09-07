<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Account</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container">
    <div class="row justify-content-center align-items-center vh-100">
        <div class="col-md-5">
            <div class="card shadow border-0 rounded-4">
                <div class="card-body p-4">

                    <h4 class="text-center text-danger mb-3">Delete Account</h4>
                    <p class="text-center text-muted small">
                        This action is permanent and cannot be undone.
                    </p>

                    @if(session('error'))
                        <div class="alert alert-danger text-center">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success text-center">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="/delete-account">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Email address</label>
                            <input 
                                type="email" 
                                name="email" 
                                class="form-control" 
                                placeholder="Enter your email"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input 
                                type="password" 
                                name="password" 
                                class="form-control" 
                                placeholder="Enter your password"
                                required
                            >
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-danger">
                                Delete My Account
                            </button>
                        </div>
                    </form>

                    <p class="text-center mt-3 small text-muted">
                        Please think carefully before proceeding.
                    </p>

                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
