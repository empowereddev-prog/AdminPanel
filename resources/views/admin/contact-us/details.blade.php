@extends('layout.headerFooter')

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Main content -->
        <section class="content">
            <div class="title_left mb-3">
                <h2>{{ $title }}</h2>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="get" enctype="multipart/form-data" action="" autocomplete="off">
                        <div class="form-group row">
                            <label for="name" class="col-sm-2 col-form-label">Name:</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="name" value="{{ $contact->name }}"
                                    readonly>
                            </div>
                        </div>

                        <div class="form-group row mt-3">
                            <label for="email" class="col-sm-2 col-form-label">Email:</label>
                            <div class="col-sm-10">
                                <input type="email" class="form-control" id="email" value="{{ $contact->email }}"
                                    readonly>
                            </div>
                        </div>

                        <div class="form-group row mt-3">
                            <label for="message" class="col-sm-2 col-form-label">Message:</label>
                            <div class="col-sm-10">
                                <textarea class="form-control" id="message" rows="3" readonly>{{ $contact->message }}</textarea>
                            </div>
                        </div>

                        @if ($contact->reply)
                            <div class="form-group row mt-3">
                                <label for="reply" class="col-sm-2 col-form-label">Reply:</label>
                                <div class="col-sm-10">
                                    <textarea class="form-control" id="reply" rows="3" readonly>{{ $contact->reply }}</textarea>
                                </div>
                            </div>
                        @endif
                        <div class="form-footer mt-4 text-right">
                            <a href="{{ route('contact-us.index') }}" class="btn btn-light"
                                style="border-radius: 50px;">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection
@push('inlinescript')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
@endpush
