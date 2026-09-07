@extends('layout.headerFooter')
@section('content')
<div class="content-wrapper">&nbsp;&nbsp;
    <div class="content">
        <div class="title_left">
            <h2>Email Template</h2>
        </div>
        <div class="card card-default">
            <div class="table-responsive">
                <table class="table" id="user" summary="Data">
                    <thead>
                        <tr>
                            <th scope="col">Variable Name</th>
                            <th scope="col">subject</th>
                            @if(!empty($pre) && $pre->is_modify == 'yes')<th scope="col" width="100px">Action(s)</th>@endif
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function() {
        var check = "{{$pre->is_modify}}";
        if(check == 'no'){
        $('#user').DataTable({
            ajax: {
                url: "{{ route('email-template.index') }}"
            , }
            , paging: true
            , columns: [{
                    data: 'variable_name'
                    , name: 'variable_name'
                    , searchable: false
                    , orderable: true
                }
                ,{
                    data: 'subject'
                    , name: 'subject'
                    , searchable: false
                    , orderable: false
                }
            , ]

        });
    }else{
        $('#user').DataTable({
            ajax: {
                url: "{{ route('email-template.index') }}"
            , }
            , paging: true
            , columns: [{
                    data: 'variable_name'
                    , name: 'variable_name'
                    , searchable: false
                    , orderable: true
                }
                ,{
                    data: 'subject'
                    , name: 'subject'
                    , searchable: false
                    , orderable: false
                }
                , {
                    data: 'action'
                    , name: 'action'
                    , orderable: false
                }
            , ]

        });
    }
    });

</script>
@endsection
