document.addEventListener('DOMContentLoaded', function () {
  var table = document.getElementById('scheduledMessagesTable');
  if (!table) return;
  var sid = table.getAttribute('data-scheduled-id');
  if (!sid) return;

  var columns = [
    { data: 'id' },
    { data: 'user_id' },
    { data: 'method' },
    { data: 'status', render: function (s) {
        if (s === 'success') return '<span class="badge bg-success">success</span>';
        if (s === 'failed') return '<span class="badge bg-danger">failed</span>';
        if (s === 'pending') return '<span class="badge bg-warning text-dark">pending</span>';
        return s;
      } },
    { data: 'error', defaultContent: '' },
    { data: 'code', defaultContent: '' },
    { data: 'message_id', defaultContent: '' },
    { data: 'processed_at', defaultContent: '' }
  ];

  // createDatatable is defined in datatable.common.js (loaded before this file)
  createDatatable('#scheduledMessagesTable', '/dashboard/scheduled/' + encodeURIComponent(sid) + '/messages', columns);
});

