@extends('layout.headerFooter')

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Main content -->
        <section class="content">
            <div class="title_left">
                <h2>{{ $title }}</h2>
            </div>
            <div class="card">
                <form method="POST" enctype="multipart/form-data" action="{{ route('contact-us.update', $contact->id) }}"
                    autocomplete="off">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group row">
                            <label for="name" class="col-sm-2 col-form-label">Name:</label>
                            <div class="col-sm-10">
                                <input type="text" readonly class="form-control" id="name"
                                    value="{{ $contact->name }}" disabled>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="email" class="col-sm-2 col-form-label">Email:</label>
                            <div class="col-sm-10 mt-2">
                                <input type="email" readonly class="form-control" id="email"
                                    value="{{ $contact->email }}" disabled>
                            </div>
                        </div>
                        <div class="form-group row mt-2">
                            <label for="subject" class="col-sm-2 col-form-label">Message:</label>
                            <div class="col-sm-10">
                                <input type="text" name="" id="" value="{{ $contact->message }}"
                                    class="form-control" readonly>
                            </div>
                        </div>
                        <div class="form-group row mt-2">
                            <label for="reply" class="col-sm-2 col-form-label">Reply:</label>
                            <div class="col-md-10">
                                <textarea class="form-control" id="reply" name="reply" placeholder="Enter reply">{{ old('reply', $contact->reply) }}</textarea>
                                @error('reply')
                                    <p style="color: red">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="form-footer mt-4 text-right">
                            <button type="submit" class="btn btn-primary btn-pill mr-2">Reply</button>
                            <a href="{{ route('contact-us.index') }}" class="btn btn-light"
                                style="border-radius: 50px;">Cancel</a>
                        </div>
                    </div>

                </form>
        </section>
    </div>
    <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->
@endsection

@push('inlinescript')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <script type="text/javascript"></script>
@endpush
