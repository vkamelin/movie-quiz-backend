$(document).ready(function() {
  const params = new URLSearchParams(window.location.search);

  createDatatable('#tgUsersTable', '/dashboard/tg-users/data', [
    { data: 'user_id' },
    { data: 'username' },
    { data: 'language_code' },
    { data: 'is_premium' },
    { data: 'is_subscribed' },
    { data: 'is_user_banned' },
    { data: 'is_bot_banned' },
    {
      data: null,
      class: 'text-end',
      render: function(data, type, row) {
        const viewBtn = '<a href="/dashboard/tg-users/' + row.id + '" class="btn btn-sm btn-outline-secondary me-1" title="Просмотр">' +
          '<i class="bi bi-eye"></i>' +
          '</a>';
        const chatBtn = '<a href="/dashboard/tg-users/' + row.id + '/chat" class="btn btn-sm btn-outline-primary" title="Чат">' +
          '<i class="bi bi-chat-dots"></i>' +
          '</a>';
        return viewBtn + chatBtn;
      },
      orderable: false,
      searchable: false
    }
  ], function(d) {
    ['is_premium','is_user_banned','is_bot_banned','is_subscribed','language_code'].forEach(function(key) {
      if (params.has(key)) {
        d[key] = params.get(key);
      }
    });
  });
});
