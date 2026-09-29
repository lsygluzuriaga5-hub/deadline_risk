@extends('layouts.risk')

@section('title', 'Friends')
@section('eyebrow', 'Study together')
@section('heading', 'Friends')
@section('subtitle', 'Connect with classmates and build a study circle.')

@section('content')
    <style>
        .friends-layout { display: grid; grid-template-columns: minmax(0, 1fr) minmax(260px, .65fr); gap: 14px; align-items: start; }
        .friend-groups { display: grid; gap: 14px; }
        .friend-list { display: grid; }
        .friend-row { display: flex; align-items: center; gap: 12px; padding: 13px 0; border-top: 1px solid var(--panel-line); }
        .friend-row:first-child { border-top: 0; }
        .friend-avatar { display: grid; width: 38px; height: 38px; flex: 0 0 38px; place-items: center; border-radius: 50%; background: #d9efe8; color: #173c3a; font-size: 13px; font-weight: 700; }
        .friend-details { min-width: 0; flex: 1; }
        .friend-details strong { display: block; overflow: hidden; font-size: 13px; text-overflow: ellipsis; white-space: nowrap; }
        .friend-details small { display: block; margin-top: 4px; overflow: hidden; color: var(--muted); font-size: 11px; text-overflow: ellipsis; white-space: nowrap; }
        .friend-actions { display: flex; gap: 7px; }
        .request-form { display: grid; gap: 12px; }
        .request-form .field { margin: 0; }
        .request-form label { color: #bed4cf; font-size: 11px; font-weight: 600; }
        .request-form input { width: 100%; min-height: 40px; padding: 8px 10px; border: 1px solid #315557; border-radius: 5px; outline: 0; background: #092325; color: var(--text); font-size: 12px; }
        .request-form input:focus { border-color: var(--teal); }
        .section-count { color: var(--muted); font-size: 11px; font-weight: 500; }
        @media (max-width: 780px) { .friends-layout { grid-template-columns: 1fr; } }
        @media (max-width: 460px) { .friend-row { align-items: flex-start; flex-wrap: wrap; } .friend-actions { width: 100%; padding-left: 50px; } }
    </style>

    <div class="friends-layout">
        <div class="friend-groups">
            <section class="panel" aria-labelledby="friends-title">
                <div class="panel-heading"><div><h2 id="friends-title">Your friends <span class="section-count">· {{ $friends->count() }}</span></h2><p>Students who accepted your friend request or whose request you accepted.</p></div></div>
                @if ($friends->isNotEmpty())
                    <div class="friend-list">
                        @foreach ($friends as $friend)
                            <article class="friend-row"><span class="friend-avatar" aria-hidden="true">{{ strtoupper(substr($friend->name, 0, 1)) }}</span><div class="friend-details"><strong>{{ $friend->name }}</strong><small>{{ $friend->email }}</small></div><span class="risk-badge low">Connected</span></article>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">Your study circle starts with a request to someone you know.</div>
                @endif
            </section>

            <section class="panel" aria-labelledby="incoming-title">
                <div class="panel-heading"><div><h2 id="incoming-title">Incoming requests <span class="section-count">· {{ $incomingRequests->count() }}</span></h2><p>Choose which classmates to connect with.</p></div></div>
                @if ($incomingRequests->isNotEmpty())
                    <div class="friend-list">
                        @foreach ($incomingRequests as $friendRequest)
                            <article class="friend-row"><span class="friend-avatar" aria-hidden="true">{{ strtoupper(substr($friendRequest->sender->name, 0, 1)) }}</span><div class="friend-details"><strong>{{ $friendRequest->sender->name }}</strong><small>{{ $friendRequest->sender->email }}</small></div><div class="friend-actions"><form method="POST" action="{{ route('friends.requests.respond', $friendRequest) }}">@csrf<input type="hidden" name="action" value="accept"><button class="button small" type="submit">Accept</button></form><form method="POST" action="{{ route('friends.requests.respond', $friendRequest) }}">@csrf<input type="hidden" name="action" value="decline"><button class="button secondary small" type="submit">Decline</button></form></div></article>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">No pending requests.</div>
                @endif
            </section>

            <section class="panel" aria-labelledby="outgoing-title">
                <div class="panel-heading"><div><h2 id="outgoing-title">Sent requests <span class="section-count">· {{ $outgoingRequests->count() }}</span></h2><p>Requests waiting for a response.</p></div></div>
                @if ($outgoingRequests->isNotEmpty())
                    <div class="friend-list">
                        @foreach ($outgoingRequests as $friendRequest)
                            <article class="friend-row"><span class="friend-avatar" aria-hidden="true">{{ strtoupper(substr($friendRequest->receiver->name, 0, 1)) }}</span><div class="friend-details"><strong>{{ $friendRequest->receiver->name }}</strong><small>{{ $friendRequest->receiver->email }}</small></div><span class="risk-badge medium">Pending</span></article>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">You haven’t sent any requests.</div>
                @endif
            </section>
        </div>

        <aside class="panel">
            <div class="panel-heading"><div><h2>Add a friend</h2><p>Send a request using their account email.</p></div></div>
            <form class="request-form" method="POST" action="{{ route('friends.requests.store') }}">
                @csrf
                <div class="field"><label for="friend-email">Student email</label><input id="friend-email" type="email" name="email" value="{{ old('email') }}" placeholder="classmate@example.com" required autocomplete="email"></div>
                @error('email')<span class="field-error">{{ $message }}</span>@enderror
                <button class="button" type="submit">＋ Send request</button>
            </form>
        </aside>
    </div>
@endsection