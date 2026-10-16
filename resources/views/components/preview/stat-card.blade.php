@props(['value', 'label', 'tone' => 'indigo'])
<div class="abk-stat-card h-100">
    <div class="abk-stat-icon abk-icon-{{ $tone }}">•</div>
    <div><div class="abk-stat-number">{{ $value }}</div><div class="abk-stat-label">{{ $label }}</div></div>
</div>
