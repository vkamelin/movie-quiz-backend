$(document).ready(function(){
  const $file = $('#logFile');
  const $level = $('#logLevel');
  const $search = $('#logSearch');

  function loadFiles() {
    const qs = new URLSearchParams(window.location.search);
    const qsFile = qs.get('file') || '';
    return $.post('/dashboard/logs/files', {_csrf_token: window.csrfToken})
      .then(function(resp){
        const files = resp.files || [];
        $file.empty();
        const names = [];
        files.forEach(function(f){
          const opt = $('<option/>').val(f.name).text(f.name + ' (' + f.mtime + ', ' + humanSize(f.size) + ')');
          $file.append(opt);
          names.push(f.name);
        });
        if (files.length > 0) {
          $file.val(files[0].name);
          if (qsFile && names.indexOf(qsFile) !== -1) {
            $file.val(qsFile);
          }
        }
      });
  }

  function humanSize(bytes) {
    const units = ['B','KB','MB','GB'];
    let i = 0; let n = bytes;
    while (n >= 1024 && i < units.length - 1) { n /= 1024; i++; }
    return (Math.round(n * 10)/10) + ' ' + units[i];
  }

  let table;
  loadFiles().then(function(){
    table = createDatatable('#logsTable', '/dashboard/logs/data', [
      { data: 'line_no' },
      { data: 'datetime', render: function(v){ return formatDate(v); } },
      { data: 'level_name', render: function(v){ return levelBadge(v); } },
      { data: 'channel' },
      { data: 'message', render: function(v){ return $('<div/>').text(v || '').html(); } },
      { data: null, render: function(row){
          const cls = row.context_exception_class || '';
          const msg = row.context_exception_message || '';
          if (!cls && !msg) return '';
          return '<span title="' + $('<div/>').text(msg).html() + '">' + $('<div/>').text(cls).html() + '</span>';
        }
      },
      { data: 'request_id' },
      { data: null, className: 'text-end', render: function(row){
          const file = encodeURIComponent($file.val() || '');
          const line = row.line_no || '';
          if (!file || !line) return '';
          return '<a class="btn btn-sm btn-outline-secondary" href="/dashboard/logs/view?file=' + file + '&line=' + line + '"><i class="bi bi-box-arrow-up-right"></i></a>';
        }, orderable: false, searchable: false }
    ], function(d){
      d.file = $file.val();
      d.level = $level.val();
      // DataTables already passes search.value, but we want instant filter button
      d.search = d.search || {};
      d.search.value = $search.val();
    }, {
      order: [[1, 'desc']]
    });
  });

  $('#logReload, #logLevel').on('click change', function(){ table && table.ajax.reload(); });
  $('#logFile').on('change', function(){ table && table.ajax.reload(); });
  $('#logSearch').on('keypress', function(e){ if (e.which === 13) { table && table.ajax.reload(); } });

  function levelBadge(v){
    v = (v || '').toUpperCase();
    const map = {
      'DEBUG': 'bg-secondary',
      'INFO': 'bg-primary',
      'NOTICE': 'bg-info text-dark',
      'WARNING': 'bg-warning text-dark',
      'ERROR': 'bg-danger',
      'CRITICAL': 'bg-danger',
      'ALERT': 'bg-danger',
      'EMERGENCY': 'bg-danger'
    };
    const cls = map[v] || 'bg-light text-dark';
    return '<span class="badge ' + cls + '">' + v + '</span>';
  }

  function pad(n){ return (n < 10 ? '0' : '') + n; }
  function formatDate(s){
    if (!s) return '';
    const d = new Date(s);
    if (isNaN(d.getTime())) return $('<div/>').text(s).html();
    return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()) + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
  }
});
