$(document).ready(function() {
  const params = new URLSearchParams(window.location.search);
  const csrfToken = window.csrfToken;

  createDatatable('#joinRequestsTable', '/dashboard/join-requests/data', [
    { data: 'chat_id' },
    { data: 'user_id' },
    { data: 'username' },
    { data: 'bio' },
    { data: 'invite_link' },
    { data: 'requested_at' },
    { data: 'status' },
    { data: 'decided_at' },
    { data: 'decided_by' },
    {
      data: null,
      className: 'text-end',
      render: function(data, type, row) {
        return '<a href="/dashboard/join-requests/' + row.chat_id + '/' + row.user_id + '" class="btn btn-sm btn-outline-secondary me-1">View</a>'
          + '<form method="post" action="/dashboard/join-requests/' + row.chat_id + '/' + row.user_id + '/approve" class="d-inline">'
          + '<input type="hidden" name="_csrf_token" value="' + csrfToken + '">' + '<button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-check-lg" title="Одобрить"></i></button>' + '</form>'
          + '<form method="post" action="/dashboard/join-requests/' + row.chat_id + '/' + row.user_id + '/decline" class="d-inline ms-1">'
          + '<input type="hidden" name="_csrf_token" value="' + csrfToken + '">' + '<button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg" title="Отклонить"></i></button>' + '</form>';
      },
      orderable: false,
      searchable: false
    }
  ], function(d) {
    ['status','chat_id'].forEach(function(key) {
      if (params.has(key)) {
        d[key] = params.get(key);
      }
    });
  });
});
