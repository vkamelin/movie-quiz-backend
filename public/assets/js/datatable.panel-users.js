$(document).ready(function() {
  createDatatable('#panelUsersTable', '/dashboard/users/data', [
    {data: 'id'},
    {data: 'email'},
    {data: 'telegram_user_id'},
    {data: 'created_at'},
    {data: 'updated_at'},
    {
      data: null,
      className: 'text-end',
      render: function(data, type, row) {
        return `<a href="/dashboard/users/${row.id}/edit" class="btn btn-outline-secondary"><i class="bi bi-pencil-square" title="Редактировать"></i></a>`;
      },
      sortable: false,
    },
  ]);
});
