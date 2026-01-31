$(document).ready(function() {
  const params = new URLSearchParams(window.location.search);

  createDatatable('#updatesTable', '/dashboard/updates/data', [
    { data: 'id' },
    { data: 'update_id' },
    {
      data: 'user_id',
      render: function(data) {
        return '<a href="?user_id=' + data + '">' + data + '</a>';
      }
    },
    {
      data: 'message_id',
      render: function(data) {
        return data ? '<a href="?message_id=' + data + '">' + data + '</a>' : '';
      }
    },
    { data: 'type' },
    { data: 'sent_at' },
    { data: 'created_at' },
    {
      data: null,
      className: 'text-end',
      render: function(data, type, row) {
        return '<a href="/dashboard/updates/' + row.id + '"' +
            'class="btn btn-sm btn-outline-secondary" title="JSON">' +
            '<i class="bi bi-filetype-json"></i>' +
            '</a>';
      },
      orderable: false,
      searchable: false
    }
  ], function(d) {
    ['type', 'user_id', 'message_id', 'created_from', 'created_to'].forEach(function(key) {
      if (params.has(key)) {
        d[key] = params.get(key);
      }
    });
  });
});
