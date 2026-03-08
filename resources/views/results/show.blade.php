@extends('layouts.app')
@section('title', __('scoreboard.final_results') . ' — ' . $competition->title)

@section('content')
    <div class="min-h-full flex flex-col items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">

            <div class="text-center mb-8">
                <div class="text-5xl mb-3">🏆</div>
                <h1 class="text-3xl font-black">{{ $competition->title }}</h1>
                <p class="text-gray-400 text-sm mt-1">{{ __('scoreboard.final_results') }}</p>
            </div>

            {{-- Winner spotlight --}}
            @if ($contestants->isNotEmpty())
                <div class="bg-yellow-900/40 border border-yellow-600/40 rounded-2xl p-6 text-center mb-6">
                    <p class="text-xs text-yellow-500 uppercase tracking-widest mb-1">{{ __('scoreboard.winner') }}</p>
                    <p class="text-4xl font-black text-yellow-300">{{ $contestants->first()->display_name }}</p>
                    <p class="text-2xl font-bold text-yellow-500 mt-1">{{ $contestants->first()->score }}
                        {{ __('common.pts') }}</p>
                </div>
            @endif

            {{-- Full ranking --}}
            <div class="bg-gray-900 rounded-2xl p-5 border border-gray-800 space-y-3">
                @foreach ($contestants as $i => $contestant)
                    <div class="flex items-center gap-4">
                        <span
                            class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm
                    {{ $loop->first ? 'bg-yellow-500 text-black' : ($loop->iteration == 2 ? 'bg-gray-400 text-black' : ($loop->iteration == 3 ? 'bg-orange-700 text-white' : 'bg-gray-800 text-gray-400')) }}">
                            {{ $loop->iteration }}
                        </span>
                        <span class="flex-1 font-semibold">{{ $contestant->display_name }}</span>
                        <span
                            class="font-bold text-xl {{ $loop->first ? 'text-yellow-400' : 'text-white' }}">{{ $contestant->score }}</span>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 text-center">
                <a href="{{ route('home') }}"
                    class="inline-block px-6 py-3 bg-indigo-700 hover:bg-indigo-600 text-white font-bold rounded-xl transition">
                    {{ __('scoreboard.new_competition') }}
                </a>
            </div>
        </div>
    </div>
@endsection
