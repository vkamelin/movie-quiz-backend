$(document).ready(function() {
  const params = new URLSearchParams(window.location.search);

  createDatatable('#sessionsTable', '/dashboard/sessions/data', [
    { data: 'user_id' },
    { data: 'state' },
    { data: 'created_at' },
    { data: 'updated_at' }
  ], function(d) {
    ['state', 'updated_from', 'updated_to'].forEach(function(key) {
      if (params.has(key)) {
        d[key] = params.get(key);
      }
    });
    // Backward compatibility: support legacy 'period=YYYY-MM-DD,YYYY-MM-DD'
    if (!params.has('updated_from') && !params.has('updated_to') && params.has('period')) {
      const parts = (params.get('period') || '').split(',');
      if (parts[0]) d['updated_from'] = parts[0];
      if (parts[1]) d['updated_to'] = parts[1];
    }
  });
});
