-- ---------------------------------------------------------------------------
-- Halo Audio · Gate-E payment integration
-- customers 1──* payments 1──* transactions
-- ---------------------------------------------------------------------------
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS customers (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(64)  NOT NULL,
  email        VARCHAR(64)  NOT NULL,
  mobile_code  VARCHAR(4)   NOT NULL DEFAULT '973',
  mobile       VARCHAR(15)  NOT NULL,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_customers_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id      INT UNSIGNED  NOT NULL,
  order_code       VARCHAR(64)   NOT NULL COMMENT 'Our unique code, sent to Gate-E as payment.code',
  gate_payment_id  VARCHAR(32)   NULL     COMMENT 'Payment id returned by Gate-E',
  product_sku      VARCHAR(32)   NOT NULL,
  product_name     VARCHAR(128)  NOT NULL,
  finish           VARCHAR(32)   NOT NULL,
  quantity         INT UNSIGNED  NOT NULL DEFAULT 1,
  unit_price       DECIMAL(12,3) NOT NULL,
  amount           DECIMAL(12,3) NOT NULL,
  amount_paid      DECIMAL(12,3) NOT NULL DEFAULT 0,
  currency         CHAR(3)       NOT NULL DEFAULT 'BHD',
  status           ENUM('initiated','pending','paid','failed','error') NOT NULL DEFAULT 'initiated',
  gateway_status   VARCHAR(32)   NULL     COMMENT 'Raw payment status from Gate-E (paid / unpaid ...)',
  payment_link     VARCHAR(255)  NULL,
  create_response  JSON          NULL,
  last_response    JSON          NULL,
  created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  completed_at     TIMESTAMP     NULL,
  UNIQUE KEY uq_payments_order_code (order_code),
  UNIQUE KEY uq_payments_gate_id (gate_payment_id),
  INDEX idx_payments_status (status),
  CONSTRAINT fk_payments_customer FOREIGN KEY (customer_id) REFERENCES customers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS transactions (
  id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  payment_id           INT UNSIGNED  NOT NULL,
  gate_transaction_id  VARCHAR(32)   NOT NULL,
  reference_number     VARCHAR(64)   NULL,
  status               VARCHAR(16)   NOT NULL,
  amount               DECIMAL(12,3) NOT NULL,
  paid_amount          DECIMAL(12,3) NOT NULL DEFAULT 0,
  currency             CHAR(3)       NOT NULL,
  response_code        VARCHAR(32)   NULL,
  response_message     VARCHAR(255)  NULL,
  card_brand           VARCHAR(32)   NULL,
  card_masked          VARCHAR(24)   NULL     COMMENT 'First 6 + last 4 only',
  rrn                  VARCHAR(32)   NULL,
  authorization_code   VARCHAR(32)   NULL,
  gateway_name         VARCHAR(64)   NULL,
  mode                 VARCHAR(8)    NULL,
  paid_at              DATETIME      NULL,
  raw                  JSON          NULL,
  synced_from          ENUM('redirect','webhook','view') NOT NULL,
  created_at           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_tx_gate_id (gate_transaction_id),
  CONSTRAINT fk_tx_payment FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
