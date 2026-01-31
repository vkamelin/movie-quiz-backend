$(document).ready(function() {
  createDatatable('#shippingTable', '/dashboard/shipping/data', [
    { data: 'shipping_query_id' },
    { data: 'from_user_id' },
    { data: 'invoice_payload' },
    { data: 'shipping_address' },
    { data: 'received_at' }
  ]);
});
