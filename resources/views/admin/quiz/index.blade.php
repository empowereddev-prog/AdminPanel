@extends('layout.headerFooter')

@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Quiz Management</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('quizCategory.index') }}">Quiz -
                                <strong>{{ $category->category_name }}</strong> Category</a>
                        </li>
                        <li class="active">
                            <a href="#">Quiz</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card card-default faq">
                <div class="card-head mb-2">
                    @if (!empty($pre) && $pre->is_modify == 'yes')
                        <a href="{{ route('quiz.create', ['id' => $id]) }}" class="btn btn-outline-secondary">Add Quiz</a>
                    @endif
                    <a href="{{ route('quizCategory.index') }}" class="btn btn-outline-secondary">
                        <i class="mdi mdi-arrow-left"></i> Back
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table" id="quiz-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Image</th>
                                <th>Quiz Title</th>

                                <th>Status</th>
                                <th width="150">Questions</th>
                                @if (!empty($pre) && $pre->is_modify == 'yes')
                                    <th width="200">Action(s)</th>
                                @endif
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(function() {
            let canModify = "{{ $pre->is_modify }}";
            $('#quiz-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('quiz.data', ['id' => $id]) }}",
                    type: 'GET'
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'image',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'title'
                    },


                    {
                        data: 'status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'question_count',
                        orderable: false,
                        searchable: false
                    },
                    ...(canModify === 'yes' ? [{
                        data: 'action',
                        orderable: false,
                        searchable: false
                    }] : [])
                ]
            });

            // Delete handler
            // Delete handler
            $("body").on('click', '.delete', function(e) {
                e.preventDefault();
                let id = $(this).data("id");
                let csrfToken = $('meta[name="csrf-token"]').attr('content');

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You want to delete this quiz!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: "DELETE",
                            url: '{{ url('quiz-delete') }}/' + id, // ✅ Matches your route
                            headers: {
                                'X-CSRF-TOKEN': csrfToken
                            },
                            success: function() {
                                $('#quiz-table').DataTable().ajax.reload();
                                Swal.fire('Deleted!', 'Quiz deleted successfully.',
                                    'success');
                            },
                            error: function() {
                                Swal.fire('Error!', 'Something went wrong.', 'error');
                            }
                        });
                    }
                });
            });

        });
    </script>
@endsection
