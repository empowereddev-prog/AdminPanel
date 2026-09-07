@extends('layout.headerFooter')
@section('content')
<div class="content-wrapper">
    <div class="content">
        <div class="title_left">
            <h2>Role Management (Sub-Admin)</h2>
        </div>
        <div class="card card-default faq">
            <div class="card-head mb-2">
            @if(!empty($pre) && $pre->is_modify == 'yes')<a href="{{route('rolePermission.create')}}" class="btn btn-pill btn-primary">Add Role</a>@endif
            </div>
            <div class="table-responsive">
                <table class="table" id="users" summary="Data">
                    <thead>
                        <tr>
                          
                            <th scope="col">No</th>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Status</th>
                          <!-- <th scope="col" width="300px">Action(s)</th> -->
                          @if(!empty($pre) && $pre->is_modify == 'yes')
                          <th scope="col"  >Action(s)</th>
                          @endif

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
 $(function () {
  var check = "{{$pre->is_modify}}";
  if(check == 'no'){
      var table = $('#users').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ url('role-permission') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex',searchable: true, orderable: false},
            {data: 'name', name: 'name'},
            {data: 'email', name: 'email', searchable: true, orderable: false},
            { data: 'status', name: 'status', searchable: true, orderable: false },
            // {data: 'action', name: 'action', orderable: false, searchable: false},

        ]
      });
    }else{
      var table = $('#users').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ url('role-permission') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex',searchable: true, orderable: false},
            {data: 'name', name: 'name'},
            {data: 'email', name: 'email' ,searchable: true, orderable: false},
            { data: 'status', name: 'status', searchable: true, orderable: false },
            {data: 'action', name: 'action', orderable: false, searchable: false},

        ]
      });
    }
    //}
    // else{
    //   var table = $('#users').DataTable({
    //     processing: true,
    //     serverSide: true,
    //     ajax: "{{ url('admin/role-permission') }}",
    //     columns: [
    //         {data: 'DT_RowIndex', name: 'DT_RowIndex'},
    //         {data: 'name', name: 'name'},
    //         {data: 'email', name: 'email'},
    //         {data: 'action', name: 'action', orderable: false, searchable: false},
    //     ]
    //   });
    // }
  });


  $(document).on('click', '#myevent', function(){ 
     var status = $(this).attr("data-status"); 
     var id = $(this).attr("data-id"); 
      var url = $(this).attr("data-url");
      
      swal.fire({
          title: "Are you sure?",
          text: "You want to perform this action?",
          icon: "warning",
            buttons: true,
            dangerMode: true, 
        })
        .then((confirm) => {
            if (confirm) {
              $.ajax({
              type: "POST",
              dataType: "json",
              data: { 'id': id,'status': status, 
              "_token": "{{ csrf_token() }}"
            },
              url: url,
              success: function(data){
                if(data.status == 'success'){
                  swal.fire("Status has been changed successfully!", {
                      icon: "success",
                    });
                  $('#users').DataTable().ajax.reload();
                }
              }
          })
              
            } 
          });
  });

  $(document).on('click', '#myevent1', function(){ 
    var url = $(this).attr("data-url");
    
    Swal.fire({
        title: "Are you sure?",
        text: "You want to delete data!",
        icon: "warning",
        showCancelButton: true,  // Show cancel button
        confirmButtonText: "Yes", // Custom confirm button text
        cancelButtonText: "Cancel", // Custom cancel button text
         confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
        reverseButtons: false, // Ensures the cancel button is not the default
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                type: "DELETE",
                dataType: "json",
                data: {
                    "_token": "{{ csrf_token() }}",
                },
                url: url,
                success: function(data){
                    if(data.status === 'success'){
                        Swal.fire(
                    ''
                    , 'Sub-Admin has been deleted successfully.'
                    , 'success'
                    )
                        $('#users').DataTable().ajax.reload();
                    }
                }
            });
        } 
        else if (result.dismiss === Swal.DismissReason.cancel) {
            // Swal.fire({
            //     title: "Cancelled",
            //     text: "The action was cancelled.",
            //     icon: "info",
            //     timer: 2000,
            //     showConfirmButton: false
            // });
        }
    });
});



  

</script>

@endsection
