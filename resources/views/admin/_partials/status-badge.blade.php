{{--
    Partial: admin status badge
    Usage: @include('admin._partials.status-badge', ['status' => 'active'])
--}}
@php
    $colors = [
        'setup' => 'bg-blue-100 text-blue-700',
        'active' => 'bg-green-100 text-green-700',
        'ended' => 'bg-gray-200 text-gray-600',
        'pending' => 'bg-yellow-100 text-yellow-700',
        'locked' => 'bg-red-100 text-red-700',
        'completed' => 'bg-indigo-100 text-indigo-700',
    ];
    $class = $colors[$status] ?? 'bg-gray-100 text-gray-600';
@endphp
<span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $class }}">
    {{ __('admin.status.' . $status, ['default' => $status]) }}
</span>
