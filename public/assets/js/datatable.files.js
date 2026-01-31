$(document).ready(function() {
  createDatatable('#filesTable', '/dashboard/files/data', [
    { data: 'id' },
    { data: 'original_name' },
    { data: 'type' },
    { data: 'file_id' },
    { data: 'created_at' },
    {
      data: null,
      className: 'text-end',
      render: function(data, type, row) {
        return '<a href="/dashboard/files/' + row.id + '" class="btn btn-sm btn-outline-secondary me-1">'
          + '<i class="bi bi-eye" title="Просмотр"></i>'
          + '</a>'
          + '<a href="/dashboard/files/' + row.id + '/download" class="btn btn-sm btn-outline-secondary">'
          + '<i class="bi bi-download" title="Скачать"></i>'
          + '</a>';
      },
      orderable: false,
      searchable: false
    }
  ]);
});
