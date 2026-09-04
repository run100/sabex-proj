@extends('trades.layout')

@section('content')
<div class="mb-4 flex items-center justify-between">
  <h1 class="text-2xl font-black">Notifications</h1>
  <form method="post" action="/api/v1/notifications/read-all" data-json-form>@csrf<button class="text-sm text-cyan-300">Mark all read</button></form>
</div>
<div class="space-y-3">
  @forelse($notifications as $row)
    <article class="rounded-xl border border-white/10 bg-slate-900/70 p-4 {{ $row->is_read ? 'opacity-70' : '' }}">
      <p class="font-bold">{{ $row->title }}</p>
      <p class="text-sm text-slate-400">{{ $row->message }}</p>
      <p class="mt-1 text-xs text-slate-500">{{ optional($row->created_at)->diffForHumans() }}</p>
      @if($row->listing)
        <a class="text-sm text-cyan-300" href="/t/{{ $row->listing->public_id }}">View trade</a>
      @endif
    </article>
  @empty
    <p class="text-slate-500">No notifications.</p>
  @endforelse
</div>
@endsection

@section('scripts')
<script>
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  document.querySelector('[data-json-form]')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    await fetch(event.target.action, { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token } });
    window.location.reload();
  });
</script>
@endsection
