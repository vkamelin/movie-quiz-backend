$(document).ready(function() {
  createDatatable('#preCheckoutTable', '/dashboard/pre-checkout/data', [
    { data: 'pre_checkout_query_id' },
    { data: 'from_user_id' },
    { data: 'currency' },
    { data: 'total_amount' },
    { data: 'shipping_option_id' },
    { data: 'received_at' }
  ]);
});
