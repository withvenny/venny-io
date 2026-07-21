-- app_venny_commerce indexes

CREATE INDEX IF NOT EXISTS idx_transactions_app_status_time ON transactions (created_for_app_id, status, time_started DESC) WHERE active = 1;
CREATE INDEX IF NOT EXISTS idx_transactions_email ON transactions (transaction_email, time_started DESC) WHERE active = 1;
CREATE INDEX IF NOT EXISTS idx_transactions_stripe_paymentintent ON transactions (transaction_stripepaymentintentid) WHERE transaction_stripepaymentintentid IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_orders_customer_time ON orders (order_customer_id, time_started DESC) WHERE active = 1;
CREATE INDEX IF NOT EXISTS idx_orders_app_status_time ON orders (created_for_app_id, status, time_started DESC) WHERE active = 1;

CREATE INDEX IF NOT EXISTS idx_coupons_code_active ON coupons (coupon_code) WHERE active = 1;
CREATE INDEX IF NOT EXISTS idx_coupons_app_status_time ON coupons (created_for_app_id, status, time_started DESC) WHERE active = 1;

CREATE INDEX IF NOT EXISTS idx_customers_app_email ON customers (created_for_app_id, customer_email) WHERE active = 1;
CREATE INDEX IF NOT EXISTS idx_customers_name ON customers (customer_lastname, customer_firstname) WHERE active = 1;
