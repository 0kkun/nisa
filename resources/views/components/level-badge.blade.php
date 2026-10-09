@props(['level'])

<span @class([
    'inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold text-white',
    'bg-level-normal' => $level->key === 'normal',
    'bg-level-buy' => $level->key === 'buy',
    'bg-level-strong' => $level->key === 'strong',
])>{{ $level->label }}</span>
