-- ============================================================
--  LIVE DATABASE MIGRATION  -  2026-10-20
--  Unit Tracking / Bulk Goods Feature
--
--  SAFE TO RUN: Skips steps already applied on live DB
--  Run in phpMyAdmin > SQL tab > [Go]
-- ============================================================

-- ── 1. products: add sold_by ──────────────────────────────────────────
ALTER TABLE `products`
    ADD COLUMN IF NOT EXISTS `sold_by` VARCHAR(20) NOT NULL DEFAULT 'unit' AFTER `category`;

-- ── 2. products: add base_unit ────────────────────────────────────────
ALTER TABLE `products`
    ADD COLUMN IF NOT EXISTS `base_unit` VARCHAR(20) NOT NULL DEFAULT 'pcs' AFTER `sold_by`;

-- ── 3. products.quantity INT -> DECIMAL (only runs if still INT) ──────
SET @t = (SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='quantity');
SET @s = IF(@t='int',
    'ALTER TABLE `products` MODIFY COLUMN `quantity` DECIMAL(15,3) NOT NULL DEFAULT 0.000',
    'SELECT 1');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

-- ── 4. products.low_stock_threshold INT -> DECIMAL ────────────────────
SET @t = (SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='low_stock_threshold');
SET @s = IF(@t='int',
    'ALTER TABLE `products` MODIFY COLUMN `low_stock_threshold` DECIMAL(15,3) NOT NULL DEFAULT 5.000',
    'SELECT 1');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

-- ── 5. sale_items.quantity INT -> DECIMAL ─────────────────────────────
SET @t = (SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sale_items' AND COLUMN_NAME='quantity');
SET @s = IF(@t='int',
    'ALTER TABLE `sale_items` MODIFY COLUMN `quantity` DECIMAL(15,3) NOT NULL DEFAULT 1.000',
    'SELECT 1');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

-- ── 6. sale_items: add unit_label ────────────────────────────────────
ALTER TABLE `sale_items`
    ADD COLUMN IF NOT EXISTS `unit_label` VARCHAR(20) NOT NULL DEFAULT 'pcs' AFTER `quantity`;

-- ── 7. sale_items: add portion_label ─────────────────────────────────
ALTER TABLE `sale_items`
    ADD COLUMN IF NOT EXISTS `portion_label` VARCHAR(50) NULL DEFAULT NULL AFTER `unit_label`;

-- ── 8. inventory_logs.change_amount INT -> DECIMAL ────────────────────
SET @t = (SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='inventory_logs' AND COLUMN_NAME='change_amount');
SET @s = IF(@t='int',
    'ALTER TABLE `inventory_logs` MODIFY COLUMN `change_amount` DECIMAL(15,3) NOT NULL DEFAULT 0.000',
    'SELECT 1');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

-- ── 9. purchase_order_items.quantity INT -> DECIMAL ───────────────────
SET @t = (SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='purchase_order_items' AND COLUMN_NAME='quantity');
SET @s = IF(@t='int',
    'ALTER TABLE `purchase_order_items` MODIFY COLUMN `quantity` DECIMAL(15,3) NOT NULL DEFAULT 1.000',
    'SELECT 1');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

-- ── 10. quotation_items.quantity INT -> DECIMAL ───────────────────────
SET @t = (SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='quotation_items' AND COLUMN_NAME='quantity');
SET @s = IF(@t='int',
    'ALTER TABLE `quotation_items` MODIFY COLUMN `quantity` DECIMAL(15,3) NOT NULL DEFAULT 1.000',
    'SELECT 1');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

-- ── 11. Create product_portions table ────────────────────────────────
--       (No FK constraint to avoid errno 150 on mixed-engine setups;
--        cascades are handled by the application layer)
CREATE TABLE IF NOT EXISTS `product_portions` (
    `id`          BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `product_id`  BIGINT            NOT NULL,
    `label`       VARCHAR(50)       NOT NULL  COMMENT 'e.g. 1/2 kg, 250 ml, 1 Litre',
    `quantity`    DECIMAL(15,3)     NOT NULL  COMMENT 'Stock deducted in base units',
    `price`       DECIMAL(15,2)     NOT NULL  COMMENT 'Fixed selling price for this portion',
    `sort_order`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP         NULL DEFAULT NULL,
    `updated_at`  TIMESTAMP         NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `product_portions_product_id_index` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 12. Back-fill defaults for existing rows ─────────────────────────
UPDATE `products`   SET `sold_by`    = 'unit' WHERE `sold_by`    IS NULL OR `sold_by`    = '';
UPDATE `products`   SET `base_unit`  = 'pcs'  WHERE `base_unit`  IS NULL OR `base_unit`  = '';
UPDATE `sale_items` SET `unit_label` = 'pcs'  WHERE `unit_label` IS NULL OR `unit_label` = '';

-- ============================================================
--  DONE. No data deleted. Safe to run again anytime.
-- ============================================================
