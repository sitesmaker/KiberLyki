import { useEffect, useRef, useState } from 'react';

export default function ImagePicker({ name, currentUrl }: { name: string; currentUrl?: string }) {
  const input = useRef<HTMLInputElement>(null);
  const [file, setFile] = useState<File | null>(null);
  const [removed, setRemoved] = useState(false);
  const [preview, setPreview] = useState('');
  const [error, setError] = useState('');

  useEffect(() => () => { if (preview) URL.revokeObjectURL(preview); }, [preview]);

  const url = file ? preview : removed ? '' : currentUrl;
  function select(selected?: File) {
    if (!selected || !input.current) return;
    if (!['image/jpeg', 'image/png', 'image/webp', 'image/gif'].includes(selected.type) || selected.size > 5 * 1024 * 1024) {
      setError('Выберите JPG, PNG, WebP или GIF размером до 5 МБ.');
      input.current.value = '';
      setFile(null);
      return;
    }
    const transfer = new DataTransfer();
    transfer.items.add(selected);
    input.current.files = transfer.files;
    setFile(selected);
    setPreview(URL.createObjectURL(selected));
    setError('');
  }

  return <fieldset className="image-picker" onDragOver={event => event.preventDefault()} onDrop={event => { event.preventDefault(); select(event.dataTransfer.files[0]); }}>
    <legend>Аватар игрока</legend>
    <input ref={input} className="image-picker__input" aria-label="Выбрать аватар" name={name} type="file" accept="image/jpeg,image/png,image/webp,image/gif" onChange={event => select(event.target.files?.[0])} />
    <input name="removeAvatar" type="hidden" value={removed ? '1' : '0'} />
    {url ? <img className="image-picker__preview" src={url} alt="Предпросмотр аватара" /> : <div className="image-picker__empty">Нет изображения</div>}
    <p>{file?.name ?? (removed ? 'Аватар будет удалён после сохранения' : 'JPG, PNG, WebP, GIF · до 5 МБ')}</p>
    <div className="image-picker__actions">
      <button type="button" className="club-button" onClick={() => input.current?.click()}>{url ? 'Заменить изображение' : 'Выбрать изображение'}</button>
      {url && <button type="button" className="club-button image-picker__remove" onClick={() => { setFile(null); setRemoved(true); setError(''); if (input.current) input.current.value = ''; }}>Удалить</button>}
      {removed && currentUrl && <button type="button" className="club-button" onClick={() => { setFile(null); setRemoved(false); if (input.current) input.current.value = ''; }}>Вернуть текущий</button>}
    </div>
    <small>Можно перетащить изображение сюда. Изменения применятся после сохранения профиля.</small>
    {error && <p className="form-error" role="alert">{error}</p>}
  </fieldset>;
}
