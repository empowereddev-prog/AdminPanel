<div class="d-flex">
    <a href="{{route('amenities.edit', $data->id)}} " ><i class="fas fa-pencil-alt" title="Edit"></i></a>&nbsp;&nbsp;
    <a href="javascript:void(0)"><i class="fa fa-trash delete"  id="{{$data->id}}" title="Delete"></i></a>&nbsp;&nbsp;
    <a href="javascript:void(0);" class="toggle-link" id="{{$data->id}}">
    <!-- <i class="fas fa-toggle-on" data-state="{{$data->status}}" title="Toggle"></i></a> -->


    @if($data->status == 'active')
        <i class="fas fa-toggle-on" data-state="{{$data->status}}" title="Status" id="toggleIconId"></i></a>
    @else
        <i class="fas fa-toggle-off" data-state="{{$data->status}}" title="Status" id="toggleIconId"></i></a>
    @endif
</div>

<script>
   $(document).ready(function () {
        $(document).on("click", ".delete", function(){
            swal({
                title: " Delete Amenities",
                text: "Are you sure you want to delete this Amenities?",
                icon: "warning",
                buttons: true,
                dangerMode: true,
                showCancelButton: true,
            })
            .then((willDelete) => {
                if (willDelete) {
                    var id = $(this).attr("id");
                        $.ajax({
                            // url: "{{ route('amenities.destroy', $data->id) }}",
                            url: "{{url('admin/amenities')}}" + "/" + id,
                            type: 'post',
                        data: {
                        '_token': '{{ csrf_token() }}',
                        '_method': 'DELETE'
                        },
                        success : function(result){
                            swal({
                                icon: "success",
                                    text: "Deleted successfully.",
                                    // timer: 1500, // Display for 1.5 seconds
                                    buttons: false, // Hide buttons

                            });
                            setInterval(function() {
                                window.location.reload();
                            }, 1000);
                        }
                    });

                }
            });
        });
    });

    // Only for status

    $(document).ready(function () {
        $(document).on("click", ".toggle-link", function(e){
            var currentStatus = $(this).find('i').data("state");
            var newStatus = (currentStatus === 'active') ? 'inactive' : 'active';
            var titleText = `${newStatus.charAt(0).toUpperCase() + newStatus.slice(1)}`;

            swal({
                title: "Amenities " + titleText + " ?",
                text: "Are you sure you want to " + titleText + " this Amenities !",
                icon: "warning",
                buttons: true,
                dangerMode: true,
                showCancelButton: true,
            })
            .then((willDelete) => {
                if (willDelete) {
                    var id = $(this).attr("id");
                        $.ajax({
                        url:"{{url('admin/amenities-status')}}"+"/"+id,
                        type: 'post',
                        data: {
                        '_token': '{{ csrf_token() }}',
                        },
                        success : function(result){
                            swal({
                                icon:"success",
                                text: "Status updated successfully.",
                                buttons: false, // Hide buttons
                            });
                            setInterval(function() {
                                window.location.reload();
                            }, 1000);
                        }
                    });

                }
            });
        });
    });
</script>
