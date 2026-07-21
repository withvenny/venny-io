-- app_venny_commerce constraints

SELECT venny_add_constraint('transactions', 'ck_transactions_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('transactions', 'ck_transactions_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('transactions', 'ck_transactions_attributes_object', 'CHECK (jsonb_typeof(transaction_attributes) = ''object'')');
SELECT venny_add_constraint('transactions', 'ck_transactions_totals_nonnegative', 'CHECK (transaction_subtotal >= 0 AND transaction_discount >= 0 AND transaction_tax >= 0 AND transaction_total >= 0)');
SELECT venny_add_constraint('transactions', 'ck_transactions_attempts_nonnegative', 'CHECK (transaction_attemptcount >= 0)');
SELECT venny_add_constraint('transactions', 'fk_transactions_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');

SELECT venny_add_constraint('orders', 'ck_orders_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('orders', 'ck_orders_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('orders', 'ck_orders_attributes_object', 'CHECK (jsonb_typeof(order_attributes) = ''object'')');
SELECT venny_add_constraint('orders', 'ck_orders_totals_nonnegative', 'CHECK (order_totalproduct >= 0 AND order_totaltax >= 0 AND order_totalshipping >= 0 AND order_totaltaxshipping >= 0 AND (order_totaladjustment IS NULL OR order_totaladjustment >= 0))');
SELECT venny_add_constraint('orders', 'fk_orders_customer', 'FOREIGN KEY (order_customer_id) REFERENCES customers(customer_id) ON UPDATE CASCADE ON DELETE SET NULL NOT VALID');
SELECT venny_add_constraint('orders', 'fk_orders_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');

SELECT venny_add_constraint('coupons', 'ck_coupons_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('coupons', 'ck_coupons_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('coupons', 'ck_coupons_attributes_object', 'CHECK (jsonb_typeof(coupon_attributes) = ''object'')');
SELECT venny_add_constraint('coupons', 'ck_coupons_redemptions_nonnegative', 'CHECK (coupon_redemptions >= 0 AND coupon_attemptcount >= 0 AND (coupon_maximumredemptions IS NULL OR coupon_maximumredemptions >= 0))');
SELECT venny_add_constraint('coupons', 'ck_coupons_money_nonnegative', 'CHECK ((coupon_percent IS NULL OR coupon_percent >= 0) AND (coupon_amount IS NULL OR coupon_amount >= 0) AND (coupon_minimumamount IS NULL OR coupon_minimumamount >= 0) AND (coupon_maximumamount IS NULL OR coupon_maximumamount >= 0) AND coupon_subtotal >= 0 AND coupon_discount >= 0 AND coupon_tax >= 0 AND coupon_total >= 0)');
SELECT venny_add_constraint('coupons', 'fk_coupons_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');

SELECT venny_add_constraint('customers', 'ck_customers_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('customers', 'ck_customers_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('customers', 'ck_customers_attributes_object', 'CHECK (jsonb_typeof(customer_attributes) = ''object'')');
SELECT venny_add_constraint('customers', 'fk_customers_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
