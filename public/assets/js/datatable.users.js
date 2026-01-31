$(document).ready(function() {
  createDatatable('#usersTable', '/dashboard/users/data', [
    {data: 'user_id'},
    {data: 'username'},
    {data: 'first_name'},
    {data: 'last_name'},
    {data: 'alias'},
    {
      data: null,
      className: 'text-end',
      render: function(data, type, row) {
        return `<a href="/dashboard/users/${row.id}"><i class="bi bi-eye" title="Просмотр"></i></a>`;
      },
      sortable: false,
    },
  ]);
});
