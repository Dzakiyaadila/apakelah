<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #222; line-height: 1.5; }
        h1 { font-size: 20px; margin-bottom: 2px; }
        .contact { font-size: 11px; color: #555; margin-bottom: 16px; }
        h2 { font-size: 14px; border-bottom: 1px solid #ccc; padding-bottom: 4px; margin-top: 18px; margin-bottom: 8px; }
        .item { margin-bottom: 10px; }
        .item-title { font-weight: bold; }
        .item-sub { font-size: 11px; color: #555; }
        .skills span { display: inline-block; background: #f0f0f0; padding: 3px 8px; border-radius: 4px; margin: 2px; font-size: 11px; }
        pre { white-space: pre-wrap; font-family: 'Helvetica', sans-serif; font-size: 12px; }
    </style>
</head>
<body>

@if($cv->personal_info)
    <h1>{{ $cv->personal_info['full_name'] ?? $cv->title }}</h1>
    <div class="contact">
        {{ $cv->personal_info['email'] ?? '' }}
        @if(!empty($cv->personal_info['phone'])) &bull; {{ $cv->personal_info['phone'] }} @endif
        @if(!empty($cv->personal_info['linkedin'])) &bull; {{ $cv->personal_info['linkedin'] }} @endif
    </div>

    @if($cv->summary)
        <h2>Summary</h2>
        <p>{{ $cv->summary }}</p>
    @endif

    @if(!empty($cv->skills))
        <h2>Skills</h2>
        <div class="skills">
            @foreach($cv->skills as $skill)
                <span>{{ $skill }}</span>
            @endforeach
        </div>
    @endif

    @if(!empty($cv->experience))
        <h2>Pengalaman</h2>
        @foreach($cv->experience as $exp)
            <div class="item">
                <div class="item-title">{{ $exp['role'] ?? '' }} — {{ $exp['company'] ?? '' }}</div>
                <div class="item-sub">{{ $exp['start'] ?? '' }} - {{ $exp['end'] ?? '' }}</div>
                <div>{{ $exp['description'] ?? '' }}</div>
            </div>
        @endforeach
    @endif

    @if(!empty($cv->education))
        <h2>Pendidikan</h2>
        @foreach($cv->education as $edu)
            <div class="item">
                <div class="item-title">{{ $edu['degree'] ?? '' }}</div>
                <div class="item-sub">{{ $edu['school'] ?? '' }} &bull; {{ $edu['start'] ?? '' }} - {{ $edu['end'] ?? '' }}</div>
            </div>
        @endforeach
    @endif

    @if(!empty($cv->projects))
        <h2>Project</h2>
        @foreach($cv->projects as $proj)
            <div class="item">
                <div class="item-title">{{ $proj['name'] ?? '' }}</div>
                @if(!empty($proj['link']))<div class="item-sub">{{ $proj['link'] }}</div>@endif
                <div>{{ $proj['description'] ?? '' }}</div>
            </div>
        @endforeach
    @endif
@else
    {{-- CV hasil upload/generate: cuma ada parsed_text, tampilkan apa adanya --}}
    <h1>{{ $cv->title }}</h1>
    <pre>{{ $cv->parsed_text }}</pre>
@endif

</body>
</html>