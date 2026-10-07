<div>
    @if($ad)
        <a href="{{ $ad->link }}" target="_blank">
            <img src="{{ asset('storage/' . $ad->image) }}" alt="{{ $ad->title }}" class="banner img-fluid">
        </a>
    @else
        <a href="{{ route('web.anunciar') }}" target="_blank">
            <img src="{{ $plan?->getFallbackImageUrl() ?? asset('theme/images/banner728x90.jpg') }}" class="banner img-fluid">
        </a>
    @endif
</div>