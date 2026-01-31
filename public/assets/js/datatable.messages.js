$(document).ready(function() {
  const csrfToken = window.csrfToken;

  createDatatable('#messagesTable', '/dashboard/messages/data', [
    { data: 'id' },
    { data: 'user_id' },
    { data: 'method' },
    { data: 'type' },
    { data: 'status', render: function (data) {
        var s = (data || '').toString().toLowerCase();
        var cls = 'text-bg-secondary';
        if (s === 'success') cls = 'text-bg-success';
        else if (s === 'failed' || s === 'error') cls = 'text-bg-danger';
        else if (s === 'processing') cls = 'text-bg-warning';
        else if (s === 'pending') cls = 'text-bg-secondary';
        return '<span class="badge ' + cls + '">' + (data || '') + '</span>';
      }
    },
    { data: 'priority' },
    { data: 'scheduled_id', render: function(v){
        if (!v) return '';
        return '<a href="/dashboard/scheduled/' + v + '" class="link-secondary">#' + v + '</a>';
      }
    },
    { data: 'error' },
    { data: 'code' },
    { data: 'processed_at' },
    {
      data: null,
      className: 'text-end',
      render: function(data, type, row) {
        const resendForm = '<form method="post" action="/dashboard/messages/' + row.id + '/resend" class="d-inline">'
          + '<input type="hidden" name="_csrf_token" value="' + csrfToken + '">'
          + '<button type="submit" class="btn btn-sm btn-outline-secondary" title="Повторная отправка">'
            + '<i class="bi bi-repeat"></i>'
          + '</button>'
          + '</form>';
        return resendForm;
      },
      orderable: false,
      searchable: false
    }
  ]);
});
