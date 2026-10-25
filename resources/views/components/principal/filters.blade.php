@props(['mode'=>'students','schoolYears'=>[],'periods'=>[],'zipAvailable'=>false])
<form method="get" class="ks-filters abk-panel">
    <input type="hidden" name="tingkat" value="{{ request('tingkat','Semua') }}">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama siswa..." aria-label="Cari nama siswa">
    <div class="ks-chips">@foreach(['Semua','X','XI','XII'] as $level)<button name="tingkat" value="{{ $level }}" class="ks-chip {{ request('tingkat','Semua')===$level?'active':'' }}">{{ $level }}</button>@endforeach</div>
    @if($mode==='recaps')
        <label class="visually-hidden" for="school-year">Tahun ajaran kelas saat ini</label><select id="school-year" name="tahun_ajaran" data-auto-submit><option value="">Semua Tahun Ajaran</option>@foreach($schoolYears as $year)<option value="{{ $year }}" @selected(request('tahun_ajaran')===$year)>{{ $year }}</option>@endforeach</select>
        <label class="visually-hidden" for="period">Periode resmi</label><select id="period" name="periode" data-auto-submit><option value="">Semua Periode Resmi</option>@foreach($periods as $period)<option @selected(request('periode')===$period)>{{ $period }}</option>@endforeach</select>
    @endif
    @if(request('siswa'))<input type="hidden" name="siswa" value="{{ request('siswa') }}">@endif
    @if($mode==='recaps')@if($zipAvailable)<a class="ks-button ks-primary ks-filter-zip" data-download href="{{ route('dev.principal.recaps.export',['format'=>'zip']+request()->except('page')) }}">Unduh ZIP</a>@else<button class="ks-button ks-primary ks-filter-zip" disabled>Unduh ZIP</button>@endif @endif
    <button type="submit" class="ks-button ks-outline ks-apply">Cari / Terapkan</button>
</form>
