@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Preview Email Template</h2>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li><a href="{{ route('email-template.index') }}">Email Template</a></li>
                        <li class="active"><a href="#">Preview</a></li>
                    </ul>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success" role="status">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
            @endif

            <div class="card card-default">
                <div class="card-header py-0">
                    <div class="title_left mb-2">
                        <h2>{{ convertInCamelCase($var_name) }}</h2>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-1">Subject</p>
                    <p class="font-weight-bold">{{ $subject }}</p>
                    <p class="text-muted small">Shown with sample values (name, school, password). The app QR code and website link are added automatically.
                        @if ($hasAttachment) The real email also attaches the student credentials spreadsheet; the test email attaches a sample one. @endif
                    </p>

                    <iframe title="Email preview" sandbox="" srcdoc="{{ $body }}"
                        style="width:100%;height:760px;border:1px solid #d9e2ef;border-radius:8px;background:#fff;"></iframe>

                    <form action="{{ route('email-template.test-send', $var_name) }}" method="post" class="form-inline mt-4">
                        @csrf
                        <label for="to" class="mr-2">Send a test copy to</label>
                        <input type="email" id="to" name="to" class="form-control mr-2" style="min-width:280px" required placeholder="you@example.com" value="{{ old('to') }}">
                        <button type="submit" class="btn btn-primary">Send test email</button>
                        <a href="{{ route('email-template.edit', $var_name) }}" class="btn btn-outline-secondary ml-2">Edit template</a>
                    </form>
                    @error('to') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>
@endsection
