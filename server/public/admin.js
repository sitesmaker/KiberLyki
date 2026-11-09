const sidebar = document.querySelector('.admin-sidebar');
const toggle = document.querySelector('.admin-menu-toggle');
toggle?.addEventListener('click', () => {
  const open = sidebar.classList.toggle('is-open');
  toggle.setAttribute('aria-expanded', String(open));
  toggle.textContent = open ? 'Закрыть ✕' : 'Меню ☰';
});

document.querySelectorAll('[data-media-picker]').forEach(picker => {
  const input = picker.querySelector('input[type=file]');
  const grid = picker.querySelector('[data-previews]');
  const choose = picker.querySelector('[data-choose]');
  const error = picker.querySelector('[data-media-error]');
  const multiple = picker.dataset.multiple === 'true';
  let files = [];
  const urls = [];

  function sync() {
    const transfer = new DataTransfer();
    files.forEach(file => transfer.items.add(file));
    input.files = transfer.files;
    urls.splice(0).forEach(url => URL.revokeObjectURL(url));
    grid.querySelectorAll('[data-new]').forEach(el => el.remove());
    const replacing = !multiple && files.length > 0;
    grid.querySelectorAll('[data-existing]').forEach(el => {
      el.hidden = replacing || !!el.querySelector('input[type=hidden]');
    });
    files.forEach((file, index) => {
      const card = document.createElement('article');
      card.className = 'media-picker__preview';
      card.dataset.new = 'true';
      const preview = document.createElement(file.type.startsWith('video/') ? 'video' : 'img');
      const url = URL.createObjectURL(file);
      urls.push(url);
      preview.src = url;
      if (preview.tagName === 'VIDEO') { preview.autoplay = true; preview.loop = true; preview.muted = true; preview.playsInline = true; }
      else preview.alt = file.name;
      const caption = document.createElement('p');
      caption.textContent = `${file.name} · ${(file.size / 1024 / 1024).toFixed(1)} МБ · новый`;
      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'danger';
      remove.textContent = 'Убрать выбранный файл';
      remove.onclick = () => { files.splice(index, 1); sync(); };
      card.append(preview, caption, remove);
      grid.append(card);
    });
    choose.textContent = multiple ? '＋ Добавить изображения' : files.length || grid.querySelector('[data-existing]:not([hidden])') ? 'Заменить файл' : '＋ Выбрать файл';
  }

  function select(selected) {
    const accepted = input.accept.split(',');
    const incoming = Array.from(selected);
    const invalid = incoming.find(file => !accepted.includes(file.type) || file.size > (file.type.startsWith('video/') ? 100 : 5) * 1024 * 1024);
    error.hidden = !invalid;
    if (invalid) {
      error.textContent = `Файл «${invalid.name}» не подходит: проверьте формат и размер.`;
      sync();
      return;
    }
    files = multiple ? [...files, ...incoming] : incoming.slice(0, 1);
    sync();
  }
  choose.onclick = () => input.click();
  input.addEventListener('change', () => select(input.files));
  grid.querySelectorAll('[data-remove]').forEach(button => button.onclick = () => {
    const card = button.closest('[data-existing]');
    const marker = document.createElement('input');
    marker.type = 'hidden';
    marker.name = `remove_${picker.dataset.name}${multiple ? '[]' : ''}`;
    marker.value = multiple ? card.dataset.existing : '1';
    card.append(marker);
    card.hidden = true;
    sync();
  });
  picker.addEventListener('dragover', event => { event.preventDefault(); picker.classList.add('is-dragging'); });
  picker.addEventListener('dragleave', () => picker.classList.remove('is-dragging'));
  picker.addEventListener('drop', event => {
    event.preventDefault();
    picker.classList.remove('is-dragging');
    select(event.dataTransfer.files);
  });
  window.addEventListener('pagehide', () => urls.splice(0).forEach(url => URL.revokeObjectURL(url)));
});

const seoEditor = document.querySelector('[data-seo-editor]');
const seoPreview = document.querySelector('[data-seo-preview]');
const seoSelect = document.querySelector('[data-seo-select]');
if (seoEditor && seoPreview && seoSelect) {
  const field = name => seoEditor.elements.namedItem(name);
  function updatePreview() {
    const title = field('title').value.trim() || seoPreview.dataset.title;
    const description = field('description').value.trim() || seoPreview.dataset.description;
    document.querySelector('[data-title-count]').textContent = `Символов: ${Array.from(title).length}`;
    document.querySelector('[data-description-count]').textContent = `Символов: ${Array.from(description).length}`;
    seoPreview.querySelector('[data-search-title]').textContent = title;
    seoPreview.querySelector('[data-search-description]').textContent = description;
    seoPreview.querySelector('[data-social-title]').textContent = field('ogTitle').value.trim() || title;
    seoPreview.querySelector('[data-social-description]').textContent = field('ogDescription').value.trim() || description;
    const selected = seoEditor.querySelector('[data-new] img, [data-existing]:not([hidden]) img');
    const src = selected?.src || seoPreview.dataset.image;
    const box = seoPreview.querySelector('[data-social-image]');
    if (src) {
      let image = box.querySelector('img');
      if (!image) { image = document.createElement('img'); image.alt = 'Предпросмотр изображения ссылки'; box.replaceChildren(image); }
      if (image.src !== src) image.src = src;
    } else box.textContent = 'Без изображения';
  }
  seoEditor.addEventListener('input', () => { seoEditor.dataset.dirty = 'true'; updatePreview(); });
  seoEditor.addEventListener('change', () => { seoEditor.dataset.dirty = 'true'; updatePreview(); });
  new MutationObserver(() => { seoEditor.dataset.dirty = 'true'; updatePreview(); }).observe(seoEditor.querySelector('[data-previews]'), { childList: true, subtree: true, attributes: true });
  seoSelect.querySelector('select').addEventListener('change', event => {
    if (seoEditor.dataset.dirty === 'true' && !window.confirm('Есть несохранённые изменения. Переключить страницу без сохранения?')) {
      event.target.value = field('target').value;
      return;
    }
    seoSelect.requestSubmit();
  });
  updatePreview();
}
