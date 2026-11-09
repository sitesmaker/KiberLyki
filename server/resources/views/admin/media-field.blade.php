@php
    $gallery = $type === 'gallery';
    $current = $section === 'tournaments' ? $item?->cover?->toArray() : ($values[$name] ?? null);
    $entries = $gallery ? ($current ?? []) : ($current ? [$current] : []);
@endphp
<fieldset class="media-picker" data-media-picker data-name="{{ $name }}" data-multiple="{{ $gallery ? 'true' : 'false' }}">
    <legend>{{ $label }}</legend>
    <input class="media-picker__input" aria-label="{{ $label }}" type="file" name="{{ $name }}{{ $gallery ? '[]' : '' }}" accept="{{ $type === 'media' ? 'video/mp4,video/webm,image/jpeg,image/png,image/webp' : 'image/jpeg,image/png,image/webp' }}" @if($gallery) multiple @endif>
    <div class="media-picker__grid" data-previews>
        @foreach($entries as $m)
            <article class="media-picker__preview" data-existing="{{ $m['id'] }}">
                @if(str_starts_with($m['mime'] ?? '', 'video/') || preg_match('/\.(mp4|webm)$/i', $m['url']))
                    <video src="{{ $m['url'] }}" autoplay loop muted playsinline preload="metadata"></video>
                @else
                    <img src="{{ $m['url'] }}" alt="{{ $m['alternativeText'] ?? $label }}" loading="lazy">
                @endif
                <p>{{ $m['alternativeText'] ?? 'Текущий файл' }}</p>
                <button type="button" class="danger" data-remove>Удалить</button>
            </article>
        @endforeach
    </div>
    <button type="button" class="secondary" data-choose>{{ $gallery ? '＋ Добавить изображения' : (count($entries) ? 'Заменить файл' : '＋ Выбрать файл') }}</button>
    <p class="media-picker__hint">{{ $gallery ? 'Добавьте изображения или перетащите их сюда.' : 'Выберите файл или перетащите его сюда.' }} Изображения до 5 МБ{{ $type === 'media' ? ', видео до 100 МБ' : '' }}. Изменения применятся после сохранения.</p>
    <p class="form-error" data-media-error role="alert" hidden></p>
</fieldset>
