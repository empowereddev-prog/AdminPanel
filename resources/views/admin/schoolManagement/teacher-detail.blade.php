@extends('layout.headerFooter')

@section('content')
<div class="content-wrapper">
    <div class="content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <h2>Teacher Details</h2>
            <a href="{{ route('school.show', $school->id) }}#teacherTable" class="btn btn-secondary">
                <i class="mdi mdi-arrow-left" aria-hidden="true"></i> Back to School
            </a>
        </div>
        <ul class="admin-breadcrumb mb-4">
            <li><a href="{{ route('school.index') }}">School Management</a></li>
            <li><a href="{{ route('school.show', $school->id) }}">{{ $school->name }}</a></li>
            <li class="active">Teacher Details</li>
        </ul>
        <div class="card shadow p-4">
            <h3 class="mb-4">{{ $teacher->name ?: 'Teacher Details' }}</h3>
            <div class="row">
                @foreach ([
                    'Name' => $teacher->name,
                    'Email' => $teacher->email,
                    'Phone no.' => trim(($teacher->country_code ?? '').' '.($teacher->phone_no ?? '')),
                    'User Status' => ucfirst($teacher->status ?? ''),
                    'Username' => $teacher->username,
                    'School' => $school->name,
                ] as $label => $value)
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold">{{ $label }}</label>
                        <div class="form-control h-auto py-3" style="background:#eef0f4;overflow-wrap:anywhere;">{{ $value ?: 'Not provided' }}</div>
                    </div>
                @endforeach
            </div>
            @include('admin.schoolManagement.partials.user-progress', ['progressUser' => $teacher])
        </div>
    </div>
</div>
@endsection