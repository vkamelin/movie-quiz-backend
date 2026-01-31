$(document).ready(function() {
  const params = new URLSearchParams(window.location.search);
  createDatatable('#chatMembersTable', '/dashboard/chat-members/data', [
    { data: 'chat_id' },
    { data: 'user_id' },
    { data: 'username' },
    { data: 'role' },
    { data: 'state' }
  ], function(d) {
    ['state','chat_id'].forEach(function(key) {
      if (params.has(key)) {
        d[key] = params.get(key);
      }
    });
  });
});
