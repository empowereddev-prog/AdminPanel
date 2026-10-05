@if (session('teacher_import_rows'))
    <details class="mb-3" open>
        <summary class="mb-2">Teacher import results</summary>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>Row</th><th>Email</th><th>Result</th><th>Details</th></tr></thead>
                <tbody>
                    @foreach (session('teacher_import_rows') as $row)
                        <tr><td>{{ $row['row'] }}</td><td>{{ $row['email'] }}</td><td>{{ $row['result'] }}</td><td>{{ $row['reason'] }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
@endif
