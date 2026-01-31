$(function () {
  const csrfToken = window.csrfToken || $('meta[name="csrf-token"]').attr('content');
  window.csrfToken = csrfToken;
  const searchUrl = window.tgUserSearchUrl;
  const addUrl = window.tgGroupAddUrl;
  const removeUrl = window.tgGroupRemoveUrl;

  const $searchInput = $('#userSearchInput');
  const $results = $('#userSearchResults');
  const $membersTable = $('#membersTable');
  const $membersBody = $('#membersTable tbody');
  const $noMembers = $('#noMembers');

  let searchTimer;

  $searchInput.on('input', function () {
    const q = $(this).val().trim();
    clearTimeout(searchTimer);
    if (q.length < 2) {
      $results.empty();
      return;
    }
    searchTimer = setTimeout(function () {
      $.post(searchUrl, {
        _csrf_token: csrfToken,
        user_id: q,
        username: q,
        first_name: q,
        last_name: q
      }, function (data) {
        $results.empty();
        data.forEach(function (u) {
          const name = u.username ? '@' + u.username : ((u.first_name || '') + ' ' + (u.last_name || '')).trim();
          $results.append('<li class="list-group-item d-flex justify-content-between align-items-center">'
            + '<span>' + name + ' (' + u.user_id + ')</span>'
            + '<button type="button" class="btn btn-sm btn-secondary add-user-btn" data-user-id="' + u.user_id + '">Add</button>'
            + '</li>');
        });
      }, 'json');
    }, 300);
  });

  $results.on('click', '.add-user-btn', function () {
    const telegramId = $(this).data('user-id');
    $.post(addUrl, {_csrf_token: csrfToken, user_id: telegramId}, function (resp) {
      if (!resp.user) {
        return;
      }
      const u = resp.user;
      $membersBody.prepend('<tr data-member-id="' + u.id + '">'
        + '<td>' + u.id + '</td>'
        + '<td>' + u.user_id + '</td>'
        + '<td>' + (u.username || '') + '</td>'
        + '<td><button type="button" class="btn btn-sm btn-danger remove-member-btn" data-user-id="' + u.id + '">Remove</button></td>'
        + '</tr>');
      $membersTable.removeClass('d-none');
      $noMembers.addClass('d-none');
    }, 'json');
  });

  $membersTable.on('click', '.remove-member-btn', function () {
    const $btn = $(this);
    const userId = $btn.data('user-id');
    $.post(removeUrl, {_csrf_token: csrfToken, user_id: userId}, function () {
      $btn.closest('tr').remove();
      if ($membersBody.children().length === 0) {
        $membersTable.addClass('d-none');
        $noMembers.removeClass('d-none');
      }
    }, 'json');
  });
});
