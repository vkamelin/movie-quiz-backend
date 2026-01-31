$(document).ready(function() {
  const params = new URLSearchParams(window.location.search);

  createDatatable('#referralsTable', '/dashboard/referrals/data', [
    { data: 'id' },
    { data: null, render: function(data, type, row) {
        const label = row.inviter_username ? ('@' + row.inviter_username) : row.inviter_user_id;
        return label;
      }
    },
    { data: null, render: function(data, type, row) {
        const label = row.invitee_username ? ('@' + row.invitee_username) : row.invitee_user_id;
        return label;
      }
    },
    { data: 'via_code' },
    { data: 'created_at' }
  ], function(d) {
    ['inviter_user_id','invitee_user_id'].forEach(function(key) {
      if (params.has(key)) {
        d[key] = params.get(key);
      }
    });
  });
});

