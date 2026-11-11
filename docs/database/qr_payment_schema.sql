-- ============================================================
-- File: qr_payment_schema.sql
-- Module: Payment Gateway (Lab 7 & 8)
-- Description: Adds the tables required by the SiberQR payment
--              integration. bank_transactions belongs to the
--              simulated bank and is never read by the shop
--              directly; the shop reaches it only through the
--              bank API. payment_gateway_log records every call
--              the middleware makes, for auditing and for the
--              error-handling evidence required by Lab 7.
-- Author: Siti Sarah Raihanah Binti Azizan
-- Created: 11/11/2026
-- ============================================================
 
USE olms_g7;
 
-- ------------------------------------------------------------
-- The simulated bank's own ledger.
-- Conceptually this lives inside Bank Siber, not inside
-- StationeryMart. It is stored in the same MySQL instance only
-- because this is a local demonstration; no shop code queries it.
-- ------------------------------------------------------------
DROP TABLE IF EXISTS bank_transactions;
 
CREATE TABLE bank_transactions (
    qr_id         VARCHAR(32)   PRIMARY KEY,
    merchant_id   VARCHAR(50)   NOT NULL,
    order_ref     VARCHAR(50)   NOT NULL,
    amount        DECIMAL(10,2) NOT NULL,
    status        ENUM('Pending', 'Paid', 'Expired', 'Cancelled')
                  NOT NULL DEFAULT 'Pending',
    reference_no  VARCHAR(50)   NULL,
    payer_name    VARCHAR(100)  NULL,
    created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    expires_at    DATETIME      NOT NULL,
    paid_at       DATETIME      NULL,
    INDEX idx_order_ref (order_ref)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 
-- ------------------------------------------------------------
-- Audit log written by the middleware on every API call.
-- Required by Lab 7 activity 1(c): appropriate error messages
-- and return values must be recorded, not only displayed.
-- ------------------------------------------------------------
DROP TABLE IF EXISTS payment_gateway_log;
 
CREATE TABLE payment_gateway_log (
    log_id        INT AUTO_INCREMENT PRIMARY KEY,
    order_id      INT           NULL,
    endpoint      VARCHAR(50)   NOT NULL,
    http_status   INT           NULL,
    outcome       ENUM('Success', 'ClientError', 'ServerError',
                       'Timeout', 'Malformed', 'SignatureFailed')
                  NOT NULL,
    message       VARCHAR(255)  NULL,
    duration_ms   INT           NULL,
    created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 
-- ------------------------------------------------------------
-- The payments table already exists. It needs a wider
-- payment_method column and a column to hold the bank's
-- reference number so a payment can be reconciled later.
-- ------------------------------------------------------------
ALTER TABLE payments
    MODIFY COLUMN payment_method VARCHAR(60) NOT NULL
        DEFAULT 'Cash on Delivery';
 
ALTER TABLE payments
    ADD COLUMN bank_reference VARCHAR(50) NULL AFTER payment_method;
 
ALTER TABLE payments
    ADD COLUMN qr_id VARCHAR(32) NULL AFTER bank_reference;
 
