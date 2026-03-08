@extends('layouts.app')
@section('title', __('contestant.select_name') . ' — ' . $competition->title)

@push('head')
    <meta name="room-code" content="{{ $competition->room_code }}">
@endpush

@section('content')
    <div class="min-h-full flex flex-col items-center justify-center px-4 py-12">
        <div class="w-full max-w-sm">

            <div class="text-center mb-8">
                <div class="text-5xl mb-3">🔔</div>
                <h1 class="text-2xl font-black text-white">{{ $competition->title }}</h1>
                <p class="text-gray-400 text-sm mt-1">{{ __('contestant.select_prompt') }}</p>
            </div>

            @if ($errors->any())
                <div class="mb-5 bg-red-900/60 border border-red-600 rounded-xl p-4 text-sm text-red-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('contestant.claim', $competition->room_code) }}" method="POST" class="space-y-3">
                @csrf

                @foreach ($competition->contestants as $contestant)
                    <button type="submit" name="contestant_id" value="{{ $contestant->id }}"
                        @if ($contestant->isClaimed()) disabled @endif
                        class="w-full flex items-center justify-between px-5 py-4 rounded-2xl text-left font-semibold text-lg transition shadow-lg
                       {{ $contestant->isClaimed()
                           ? 'bg-gray-800 text-gray-600 cursor-not-allowed opacity-60'
                           : 'bg-indigo-700 hover:bg-indigo-600 active:scale-95 text-white' }}">
                        <span>{{ $contestant->display_name }}</span>
                        @if ($contestant->isClaimed())
                            <span class="text-xs font-normal text-gray-500">{{ __('contestant.already_claimed') }}</span>
                        @else
                            <span class="text-xl">→</span>
                        @endif
                    </button>
                @endforeach

            </form>

            <p class="text-center text-gray-600 text-xs mt-8">
                {{ __('common.room') }}: <span class="font-mono text-gray-500">{{ $competition->room_code }}</span>
            </p>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const ROOM_CODE = document.querySelector('meta[name="room-code"]').content;
        const TRANS = @json(['already_claimed' => __('contestant.already_claimed')]);
        if (window.Echo) {
            window.Echo.channel('competition.' + ROOM_CODE)
                .listen('.ContestantClaimed', e => {
                    document.querySelectorAll('button[name="contestant_id"]').forEach(btn => {
                        if (btn.value == e.contestant_id) {
                            btn.disabled = true;
                            btn.className = btn.className
                                .replace('bg-indigo-700 hover:bg-indigo-600 active:scale-95 text-white', '') +
                                ' bg-gray-800 text-gray-600 cursor-not-allowed opacity-60';
                            btn.querySelector(':last-child').textContent = TRANS.already_claimed;
                        }
                    });
                });
        }
    </script>
@endpush
