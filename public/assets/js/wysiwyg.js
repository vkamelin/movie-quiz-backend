// Lightweight WYSIWYG helpers for Telegram HTML formatting
(function () {
  function execCmd(command, value = null) {
    document.execCommand(command, false, value);
  }

  function wrapSelection(tag) {
    const sel = window.getSelection();
    if (sel && sel.rangeCount) {
      const range = sel.getRangeAt(0);
      const wrapper = document.createElement(tag);
      wrapper.appendChild(range.extractContents());
      range.insertNode(wrapper);
    }
  }

  function insertSpoiler() {
    const sel = window.getSelection();
    if (!sel || !sel.rangeCount) return;
    const range = sel.getRangeAt(0);
    const spoiler = document.createElement('tg-spoiler');
    spoiler.appendChild(range.extractContents());
    range.insertNode(spoiler);
  }

  function insertBlockquote() {
    const sel = window.getSelection();
    if (!sel || !sel.rangeCount) return;
    const range = sel.getRangeAt(0);
    const blockquote = document.createElement('blockquote');
    blockquote.appendChild(range.extractContents());
    range.insertNode(blockquote);
  }

  function insertLink() {
    const url = prompt('Введите URL:', 'http://');
    if (url) execCmd('createLink', url);
  }

  function clearFormattingFor(editor) {
    const text = editor.innerText || '';
    editor.innerText = text;
  }

  function htmlToTelegramText(html) {
    // Normalize block wrappers and physical newlines to <br>
    let s = html
      .replace(/\r\n|\r|\n/g, '<br>')
      .replace(/<div>/gi, '<br>')
      .replace(/<\/div>/gi, '');

    // Sanitize to Telegram-allowed tags
    const clean = window.DOMPurify.sanitize(s, {
      ALLOWED_TAGS: [
        'b', 'strong', 'i', 'em', 'u', 'ins',
        's', 'strike', 'del', 'a',
        'code', 'pre', 'tg-spoiler', 'blockquote',
        'br'
      ],
      ALLOWED_ATTR: ['href']
    });

    // Convert <br> back to \n for Telegram
    return clean.replace(/<br\s*\/?>(?!\n)/gi, '\n');
  }

  function updateCounter(editor, $counter, limit) {
    const text = htmlToTelegramText(editor.innerHTML || '');
    const len = text.length;
    if ($counter && $counter.length) {
      $counter.text(len);
      $counter.toggleClass('text-danger', limit && len > limit);
    }
  }

  function bindToolbar($container) {
    $container.on('click', '.wysi-btn', function () {
      const cmd = $(this).data('cmd');
      const wrap = $(this).data('wrap');
      if (cmd) execCmd(cmd);
      if (wrap) wrapSelection(wrap);
    });
    $container.on('click', '[data-action="link"]', function () { insertLink(); });
    $container.on('click', '[data-action="spoiler"]', function () { insertSpoiler(); });
    $container.on('click', '[data-action="blockquote"]', function () { insertBlockquote(); });
    $container.on('click', '[data-action="clear"]', function () {
      const editor = $(this).closest('.message-fields').find('.wysiwyg-editor')[0];
      if (editor) clearFormattingFor(editor);
    });
  }

  function initEditorEl(editorEl, hiddenSelector, counterSelector, limit) {
    const $editor = $(editorEl);
    const editor = $editor[0];
    const $counter = counterSelector ? $(counterSelector) : null;
    if (!editor) return;

    // Enter -> newline, not <div>
    $editor.on('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        document.execCommand('insertText', false, '\n');
      }
    });

    // Update counter on input
    $editor.on('input', function () { updateCounter(editor, $counter, limit); });
    updateCounter(editor, $counter, limit);

    // Bind toolbar in the same section
    bindToolbar($editor.closest('.message-fields'));
  }

  $(function () {
    console.log('WYSIWYG init started');
    // Auto-scan editors by data-attributes to avoid inline config
    const editors = [];
    $('.wysiwyg-editor').each(function () {
      const hiddenId = $(this).data('hiddenId');
      const counterId = $(this).data('counterId');
      const limit = parseInt($(this).data('limit'), 10) || null;
      const hiddenSel = hiddenId ? ('#' + hiddenId) : null;
      const counterSel = counterId ? ('#' + counterId) : null;
      editors.push({ el: this, hidden: hiddenSel, counter: counterSel, limit: limit });
      initEditorEl(this, hiddenSel, counterSel, limit);
    });

    // Single submit handler that processes all editors
    $('#message-send-form').on('submit', function (e) {
      console.log('Form submit triggered, processing editors...');
      editors.forEach(function (item) {
        const $editor = $(item.el);
        const html = $editor.html() || '';
        const text = htmlToTelegramText(html);
        console.log('Editor:', item.hidden, 'HTML length:', html.length, 'Text:', text.substring(0, 50));
        if (item.hidden) $(item.hidden).val(text);
      });
      console.log('Form submit complete');
    });
  });
})();
