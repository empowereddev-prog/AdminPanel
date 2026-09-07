@extends('layout.headerFooter')
@section('content')
<div class="content-wrapper">&nbsp;&nbsp;
    <div class="content">
        <div class="title_left">
            <h2>Static Content</h2>
        </div>
        <div class="card card-default static-content">
            <div class="table-wrapper">
                <table class="table" id="users" summary="Data">
                    <thead>
                        <tr>
                            <th scope="col">Key</th>
                            <th scope="col">Title</th>
                            <!-- <th scope="col" width="50px">Content</th> -->
                            <!-- <th scope="col">Language</th> -->
                            @if(!empty($pre) && $pre->is_modify == 'yes')<th scope="col" width="100px">Action(s)</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</div>

</div>

<script type="text/javascript">
    $(function() {
        var check = "{{$pre->is_modify}}";
        if(check == 'no'){
        $('#users').DataTable({
            ajax: {
                url: "{{ route('static-content.index') }}"
            , }
            , paging: true
            , columns: [{
                    data: 'slug'
                    , name: 'slug'
                    , searchable: false
                    , orderable: true
                }
                , {
                    data: 'title'
                    , name: 'title'
                    , searchable: true
                    , orderable: true
                }
            , ]

        });
    }else{
        $('#users').DataTable({
            ajax: {
                url: "{{ route('static-content.index') }}"
            , }
            , paging: true
            , columns: [{
                    data: 'slug'
                    , name: 'slug'
                    , searchable: false
                    , orderable: true
                }
                , {
                    data: 'title'
                    , name: 'title'
                    , searchable: true
                    , orderable: true
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
