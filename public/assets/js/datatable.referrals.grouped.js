$(document).ready(function() {
  createDatatable('#referralsGroupedTable', '/dashboard/referrals/grouped', [
    {
      data: null,
      render: function(data, type, row) {
        const label = row.inviter_username ? ('@' + row.inviter_username) : row.inviter_user_id;
        return label;
      }
    },
    {
      data: 'cnt',
      render: function(v){ return '<span class="badge text-bg-primary">' + v + '</span>'; }
    },
    { data: 'first_at' },
    { data: 'last_at' },
    {
      data: null,
      className: 'text-end',
      orderable: false,
      searchable: false,
      render: function(data, type, row) {
        const link = '/dashboard/referrals?inviter_user_id=' + row.inviter_user_id;
        return '<a href="' + link + '" class="btn btn-sm btn-outline-secondary">'
             + '<i class="bi bi-people"></i> Показать приглашённых'
             + '</a>';
      }
    }
  ]);
});

