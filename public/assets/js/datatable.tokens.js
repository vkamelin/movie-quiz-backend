$(document).ready(function() {
  const params = new URLSearchParams(window.location.search);
  const csrfToken = window.csrfToken;

  createDatatable('#tokensTable', '/dashboard/tokens/data', [
    { data: 'id' },
    { data: 'user_id' },
    { data: 'jti' },
    { data: 'expires_at' },
    { data: 'revoked' },
    { data: 'created_at' },
    { data: 'updated_at' },
    {
      data: null,
      render: function(data, type, row) {
        return '<form method="post" action="/dashboard/tokens/' + row.id + '/revoke" class="d-inline">'
          + '<input type="hidden" name="_csrf_token" value="' + csrfToken + '">'
          + '<button type="submit" class="btn btn-sm btn-outline-danger">Revoke</button>'
          + '</form>';
      },
      orderable: false,
      searchable: false
    }
  ], function(d) {
    ['revoked', 'period'].forEach(function(key) {
      if (params.has(key)) {
        d[key] = params.get(key);
      }
    });
  });
});
