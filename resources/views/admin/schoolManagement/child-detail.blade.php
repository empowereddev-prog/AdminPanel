@extends('layout.headerFooter')

@section('content')
<div class="content-wrapper">
    <div class="content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <h2>Child Details</h2>
            <a href="{{ route('school.show', $school->id) }}#childTable" class="btn btn-secondary">
                <i class="mdi mdi-arrow-left" aria-hidden="true"></i> Back to School
            </a>
        </div>
        <ul class="admin-breadcrumb mb-4">
            <li><a href="{{ route('school.index') }}">School Management</a></li>
            <li><a href="{{ route('school.show', $school->id) }}">{{ $school->name }}</a></li>
            <li class="active">Child Details</li>
        </ul>
        <div class="card shadow p-4">
            <h3 class="mb-4">{{ $child->name ?: 'Child Details' }}</h3>
            <div class="row">
                @foreach ([
                    'Name' => $child->name,
                    'Email' => $child->email,
                    'Phone no.' => trim(($child->country_code ?? '').' '.($child->phone_no ?? '')),
                    'User Status' => ucfirst($child->status ?? ''),
                    'Username' => $child->username,
                    'Date of Birth' => $child->dob,
                    'School' => $school->name,
                    'Account Relationship' => $child->parent_id ? 'Parent-linked' : 'Independent',
                    'Parent Name' => $child->parent?->name,
                    'Parent Email' => $child->parent?->email,
                ] as $label => $value)
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold">{{ $label }}</label>
                        <div class="form-control h-auto py-3" style="background:#eef0f4;overflow-wrap:anywhere;">{{ $value ?: 'Not provided' }}</div>
                    </div>
                @endforeach
            </div>
            <section class="card p-3 mt-3 mb-4 bg-light" style="border:2px solid #afb2b5;" aria-labelledby="child-policy-heading">
                <h5 id="child-policy-heading" class="mb-3 text-primary">Policy Verification Details</h5>
                @foreach ([
                    'Parental consent policy verified at' => $child->popup_1_updated_at,
                    "Your child's privacy policy verified at" => $child->popup_2_updated_at,
                ] as $label => $verifiedAt)
                    <div class="mb-3">
                        <strong>{{ $label }}:</strong>
                        @if ($verifiedAt)
                            <span class="text-success">{{ $verifiedAt->format('d M Y, h:i A') }}</span>
                        @else
                            <span class="text-danger">Not Verified</span>
                        @endif
                    </div>
                @endforeach
            </section>
            @include('admin.schoolManagement.partials.user-progress', ['progressUser' => $child])
        </div>
    </div>
</div>
@endsection