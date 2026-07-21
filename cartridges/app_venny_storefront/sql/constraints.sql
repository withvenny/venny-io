-- app_venny_storefront constraints

SELECT venny_add_constraint('catalogs', 'ck_catalogs_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('catalogs', 'ck_catalogs_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('catalogs', 'ck_catalogs_attributes_object', 'CHECK (jsonb_typeof(catalog_attributes) = ''object'')');
SELECT venny_add_constraint('catalogs', 'ck_catalogs_images_json', 'CHECK (catalog_images IS NULL OR jsonb_typeof(catalog_images) IN (''array'', ''object''))');
SELECT venny_add_constraint('catalogs', 'uq_catalogs_app_slug', 'UNIQUE (created_for_app_id, catalog_slug)');
SELECT venny_add_constraint('catalogs', 'fk_catalogs_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');

SELECT venny_add_constraint('categories', 'ck_categories_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('categories', 'ck_categories_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('categories', 'ck_categories_attributes_object', 'CHECK (jsonb_typeof(category_attributes) = ''object'')');
SELECT venny_add_constraint('categories', 'ck_categories_images_json', 'CHECK (category_images IS NULL OR jsonb_typeof(category_images) IN (''array'', ''object''))');
SELECT venny_add_constraint('categories', 'uq_categories_catalog_slug', 'UNIQUE (category_catalog_id, category_slug)');
SELECT venny_add_constraint('categories', 'fk_categories_catalog', 'FOREIGN KEY (category_catalog_id) REFERENCES catalogs(catalog_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('categories', 'fk_categories_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');

SELECT venny_add_constraint('products', 'ck_products_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('products', 'ck_products_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('products', 'ck_products_attributes_object', 'CHECK (jsonb_typeof(product_attributes) = ''object'')');
SELECT venny_add_constraint('products', 'ck_products_images_json', 'CHECK (product_images IS NULL OR jsonb_typeof(product_images) IN (''array'', ''object''))');
SELECT venny_add_constraint('products', 'ck_products_inventory_nonnegative', 'CHECK (product_inventory >= 0)');
SELECT venny_add_constraint('products', 'ck_products_base_price_nonnegative', 'CHECK (product_base_price >= 0)');
SELECT venny_add_constraint('products', 'uq_products_app_sku', 'UNIQUE (created_for_app_id, product_sku)');
SELECT venny_add_constraint('products', 'fk_products_catalog', 'FOREIGN KEY (product_catalog_id) REFERENCES catalogs(catalog_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('products', 'fk_products_category', 'FOREIGN KEY (product_category_id) REFERENCES categories(category_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('products', 'fk_products_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');

SELECT venny_add_constraint('items', 'ck_items_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('items', 'ck_items_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('items', 'ck_items_attributes_object', 'CHECK (jsonb_typeof(item_attributes) = ''object'')');
SELECT venny_add_constraint('items', 'ck_items_quantity_nonnegative', 'CHECK (item_quantity IS NULL OR item_quantity >= 0)');
SELECT venny_add_constraint('items', 'ck_items_sale_price_nonnegative', 'CHECK (item_sale_price IS NULL OR item_sale_price >= 0)');
SELECT venny_add_constraint('items', 'fk_items_catalog', 'FOREIGN KEY (item_catalog_id) REFERENCES catalogs(catalog_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('items', 'fk_items_category', 'FOREIGN KEY (item_category_id) REFERENCES categories(category_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('items', 'fk_items_product', 'FOREIGN KEY (item_product_id) REFERENCES products(product_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('items', 'fk_items_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
