@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Quiz Question Options</h2>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('quizCategory.index') }}">Quiz Category</a>
                        </li>
                        <li>
                            <a href="{{ route('quiz.index', ['id' => $category_id]) }}">
                                Quiz </a>
                        </li>
                        <li>
                            <a href="{{ route('quiz-questions.index', ['id' => $quiz_id, 'category_id' => $category_id]) }}">
                                Quiz Question</a>
                        </li>
                        <li class="active">
                            <a href="#">Question Options</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card card-default">
                {{-- <div class="card-head mb-2">
                    <a href="{{ route('question-options.create', ['id' => $id, 'category_id' => $category_id, 'quiz_id' => $quiz_id]) }}"
                        class="btn btn-pill btn-primary" id="addOptionsBtn">Add Options</a>
                    <a href="{{ route('quiz-questions.index', ['id' => $quiz_id, 'category_id' => $category_id]) }}" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left"></i> Back
                    </a>
                </div> --}}
                <div class="card-head mb-2 d-flex justify-content-end gap-2">
                    <a href="{{ route('question-options.create', ['id' => $id, 'category_id' => $category_id, 'quiz_id' => $quiz_id]) }}"
                        class="btn btn-outline-secondary" id="addOptionsBtn">
                        Add Options
                    </a>
                    &nbsp;
                    <a href="{{ route('quiz-questions.index', ['id' => $quiz_id, 'category_id' => $category_id]) }}"
                        class="btn btn-outline-secondary">
                        <i class="mdi mdi-arrow-left"></i> Back
                    </a>
                </div>
                
                <div class="table-responsive">
                    <table class="table" id="user" summary="Data">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col" style="max-width:30%;">Question</th>
                                <th scope="col" style="max-width:30%;">Options</th>
                                <th scope="col" style="max-width:30%;">Correct Answer</th>
                                <th scope="col">Action(s)</th>

                            </tr>
                        </thead>

                    </table>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>

    <script type="text/javascript">
        $(function() {
            // console.log(url);
            var url = "{{ url('question-options/' . $id . '/' . $category_id . '/' . $quiz_id) }}";
            $('#user').DataTable({
                processing: true,
                serverSide: true,
                ajax: url,
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex'
                    },
                    {
                        data: 'question',
                        name: 'question'
                    },
                    {
                        data: 'options',
                        name: 'options'
                    },
                    {
                        data: 'correct_option',
                        name: 'correct_option'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false
                    },
                ],
                drawCallback: function(settings) {
                    checkOptionLimit();
                }
            });


        });

        function checkOptionLimit() {
            $.ajax({
                url: "{{ url('count-question-options/' . $id) }}",
                type: "GET",
                success: function(response) {
                    if (response.count >= 4) {
                        $("#addOptionsBtn").remove(); // Remove the button
                    } else {
                        $("#addOptionsBtn").prop('disabled', false).text('Add Options');
                    }
                }
            });
        }

        $("body").on('click', '.delete', function(e) {
            e.preventDefault();
            var id = $(this).data("id");
            // Get the CSRF token value from the meta tag in your HTML
            var csrfToken = $('meta[name="csrf-token"]').attr('content');

            Swal.fire({
                html: 'You want to delete options!',
                title: 'Are you sure?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "delete",
                        url: '{{ url('delete-question-options') }}' + "/" + id,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        },
                        success: function(data) {
                            Swal.fire(
                                'Deleted!',
                                'Options Deleted Successfully.',
                                'success'
                            ).then(() => {
                                location
                                    .reload(); // Reload the page after successful deletion
                            });
                        },
                        error: function(data) {
                            var dataTable = $('#user').DataTable();
                            dataTable.ajax.reload();

                        }
                    });
                    Swal.fire(
                        'Deleted!',
                        'Options Deleted Successfully.',
                        'success'
                    ).then(() => {
                        location.reload(); // Reload the page after successful deletion
                    });
                }
            });
        });
    </script>

    <!-- </div> -->
@endsection
