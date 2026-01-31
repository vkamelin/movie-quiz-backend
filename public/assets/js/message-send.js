$(function () {
  const csrfToken = window.csrfToken || $('meta[name="csrf-token"]').attr('content');
  window.csrfToken = csrfToken;
  const searchUrl = $('#message-send-form').data('tgUserSearchUrl') || window.tgUserSearchUrl;

  const $typeSelect = $('#messageType');
  const $messageFields = $('.message-fields');

  function toggleMessageFields() {
    const type = $typeSelect.val();
    $messageFields.each(function () {
      const $section = $(this);
      const active = $section.data('type') === type;
      $section.toggleClass('d-none', !active);
      $section.find('input,textarea,select').prop('disabled', !active);
    });
  }
  $typeSelect.on('change', toggleMessageFields);
  toggleMessageFields();

  const $modeRadios = $('input[name="mode"]');
  const $sendModeRadios = $('input[name="send_mode"]');
  const $sendAfterInput = $('#sendAfterInput');
  const $singleSection = $('#singleUserSection');
  const $selectedSection = $('#selectedUsersSection');
  const $groupSection = $('#groupSection');

  function toggleSections() {
    const mode = $modeRadios.filter(':checked').val();
    $singleSection.toggleClass('d-none', mode !== 'single');
    $selectedSection.toggleClass('d-none', mode !== 'selected');
    $groupSection.toggleClass('d-none', mode !== 'group');
  }
  $modeRadios.on('change', toggleSections);
  toggleSections();

  function toggleSchedule() {
    const v = $sendModeRadios.filter(':checked').val();
    const enabled = v === 'schedule';
    $sendAfterInput.prop('disabled', !enabled);
  }
  $sendModeRadios.on('change', toggleSchedule);
  toggleSchedule();

  // Select2 for multi user selection (AJAX)
  const $userSelect = $('#userSelect');
  if ($userSelect.length && typeof $.fn.select2 === 'function') {
    $userSelect.select2({
      width: '100%',
      placeholder: $userSelect.data('placeholder') || 'Начните ввод для поиска...',
      allowClear: true,
      ajax: {
        url: searchUrl,
        method: 'POST',
        delay: 250,
        dataType: 'json',
        data: function (params) {
          return {
            _csrf_token: csrfToken,
            q: params.term || '',
            limit: 20
          };
        },
        processResults: function (data) {
          // data is an array of users: {user_id, username, first_name, last_name}
          const results = (data || []).map(function (u) {
            const title = u.username ? ('@' + u.username) : ((u.first_name || '') + ' ' + (u.last_name || '')).trim();
            const text = title ? (title + ' (' + u.user_id + ')') : String(u.user_id);
            return { id: String(u.user_id), text: text };
          });
          return { results: results };
        },
        cache: true
      }
    });
  }
});
