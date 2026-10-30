@extends('layout.headerFooter')

@section('content')
<div class="content-wrapper">
    <div class="content">
        <div class="title_left"><h2>Individual Subscription Waitlist</h2></div>
        <div class="card card-default">
            <div class="card-body">
                <p class="mb-3"><strong>Total unique registrations: {{ number_format($total) }}</strong></p>
                <form method="get" action="{{ route('individual-waitlist.index') }}" class="form-inline mb-4" role="search">
                    <label for="waitlist-search" class="mr-2">Search email</label>
                    <input id="waitlist-search" class="form-control mr-2" type="search" name="search" value="{{ $search }}" maxlength="254">
                    <button class="btn btn-primary" type="submit">Search</button>
                    @if ($search !== '')
                        <a class="ml-3" href="{{ route('individual-waitlist.index') }}">Clear</a>
                    @endif
                </form>
                <div class="table-responsive">
                    <table class="table" aria-label="Individual subscription waitlist registrations">
                        <thead><tr><th scope="col">S.No</th><th scope="col">Email</th><th scope="col">Registered On</th><th scope="col">Signup Source</th></tr></thead>
                        <tbody>
                            @forelse ($registrations as $registration)
                                <tr>
                                    <td>{{ $registrations->firstItem() + $loop->index }}</td>
                                    <td>{{ $registration->email }}</td>
                                    <td>{{ $registration->created_at->format('d M Y, h:i A') }} {{ config('app.timezone') }}</td>
                                    <td>Homepage</td>
                                </tr>
                            @empty
                                <tr><td colspan="4">{{ $search !== '' ? 'No registrations match your search.' : 'No waitlist registrations yet.' }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $registrations->links('pagination::bootstrap-4') }}
                <p class="text-muted mt-3">This count records unique email addresses expressing interest, not paid subscriptions or verified individuals.</p>
            </div>
        </div>
    </div>
</div>
@endsection
