@extends('layouts.app')
@section('title', 'New Competition')

@section('content')
<div class="min-h-full flex flex-col items-center justify-center px-4 py-12">

    <div class="w-full max-w-lg">

        {{-- Logo / Brand --}}
        <div class="text-center mb-10">
            <div class="text-6xl mb-3">🔔</div>
            <h1 class="text-4xl font-black tracking-tight text-white">Contest Bells</h1>
            <p class="text-gray-400 mt-2 text-sm">Real-time quiz buzzer system</p>
        </div>

        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="mb-6 bg-red-900/60 border border-red-500 rounded-xl p-4 text-sm text-red-200">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('competition.store') }}" method="POST" class="bg-gray-900 rounded-2xl p-8 shadow-2xl space-y-6">
            @csrf

            {{-- Competition title --}}
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-1">Competition Title</label>
                <input
                    type="text"
                    name="title"
                    value="{{ old('title', 'Quiz Competition') }}"
                    placeholder="e.g. Science Quiz Round 1"
                    class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
            </div>

            {{-- Contestant count selector --}}
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">Number of Contestants</label>
                <div class="flex gap-3" id="count-buttons">
                    @foreach ([2, 3, 4] as $n)
                        <button type="button"
                            data-count="{{ $n }}"
                            onclick="setCount({{ $n }})"
                            class="count-btn flex-1 py-3 rounded-xl font-bold text-lg transition
                                   {{ old('contestant_count', 2) == $n ? 'bg-indigo-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">
                            {{ $n }}
                        </button>
                    @endforeach
                </div>
                <input type="hidden" name="contestant_count" id="contestant_count" value="{{ old('contestant_count', 2) }}">
            </div>

            {{-- Contestant name inputs --}}
            <div id="name-fields" class="space-y-3">
                {{-- rendered by JS --}}
            </div>

            <button type="submit"
                class="w-full py-4 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700
                       text-white font-bold text-lg rounded-xl transition shadow-lg shadow-indigo-900/40">
                🚀 Start Competition
            </button>
        </form>

        <p class="text-center text-gray-600 text-xs mt-6">
            Contestants will receive a join link after setup.
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script>
const oldNames  = @json(old('names', []));
let currentCount = {{ old('contestant_count', 2) }};

function setCount(n) {
    currentCount = n;
    document.getElementById('contestant_count').value = n;
    document.querySelectorAll('.count-btn').forEach(btn => {
        const active = parseInt(btn.dataset.count) === n;
        btn.className = btn.className
            .replace(/bg-indigo-600 text-white|bg-gray-800 text-gray-400 hover:bg-gray-700/g, '')
            .trim();
        btn.className += active
            ? ' bg-indigo-600 text-white'
            : ' bg-gray-800 text-gray-400 hover:bg-gray-700';
    });
    renderNames();
}

function renderNames() {
    const container = document.getElementById('name-fields');
    container.innerHTML = '';
    for (let i = 0; i < currentCount; i++) {
        const val = oldNames[i] || '';
        container.innerHTML += `
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-full bg-indigo-700 text-white flex items-center justify-center font-bold text-sm">${i+1}</span>
                <input type="text" name="names[]" value="${val}"
                    placeholder="Contestant ${i+1} name"
                    required maxlength="50"
                    class="flex-1 bg-gray-800 border border-gray-700 rounded-xl px-4 py-3
                           text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>`;
    }
}

// Init on page load
renderNames();
</script>
@endpush
