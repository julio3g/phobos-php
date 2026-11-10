-- Phobos PHP — esquema PostgreSQL (idempotente: pode ser executado mais de uma vez)

CREATE TABLE IF NOT EXISTS users (
    id            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name          TEXT        NOT NULL,
    email         TEXT        NOT NULL UNIQUE,
    password_hash TEXT        NOT NULL,
    role          TEXT        NOT NULL DEFAULT 'admin' CHECK (role IN ('admin', 'member')),
    created_at    TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at    TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS clients (
    id           BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name         TEXT        NOT NULL,
    trade_name   TEXT,
    email        TEXT,
    phone        TEXT,
    person_type  TEXT        NOT NULL CHECK (person_type IN ('individual', 'company')),
    cpf          TEXT UNIQUE,
    cnpj         TEXT UNIQUE,
    street       TEXT,
    city         TEXT,
    state        CHAR(2),
    zip_code     TEXT,
    created_at   TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at   TIMESTAMPTZ NOT NULL DEFAULT now(),
    -- PF exige CPF e não tem CNPJ; PJ exige CNPJ e não tem CPF
    CONSTRAINT clients_document_check CHECK (
        (person_type = 'individual' AND cpf IS NOT NULL AND cnpj IS NULL) OR
        (person_type = 'company'    AND cnpj IS NOT NULL AND cpf IS NULL)
    )
);

CREATE TABLE IF NOT EXISTS materials (
    id               BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name             TEXT           NOT NULL,
    brand            TEXT,
    variant          TEXT,
    image_url        TEXT,
    purchase_url     TEXT,
    unit             TEXT           NOT NULL CHECK (unit IN ('un', 'm', 'g', 'kg', 'ml', 'pair')),
    package_price    NUMERIC(12, 2) NOT NULL CHECK (package_price >= 0),
    package_quantity NUMERIC(14, 3) NOT NULL CHECK (package_quantity > 0),
    -- custo por unidade-base, calculado pelo banco
    unit_cost        NUMERIC(18, 6) GENERATED ALWAYS AS (package_price / package_quantity) STORED,
    archived_at      TIMESTAMPTZ,
    created_at       TIMESTAMPTZ    NOT NULL DEFAULT now(),
    updated_at       TIMESTAMPTZ    NOT NULL DEFAULT now()
);
CREATE UNIQUE INDEX IF NOT EXISTS materials_identity_unique
    ON materials (lower(name), lower(coalesce(brand, '')), lower(coalesce(variant, '')));

CREATE TABLE IF NOT EXISTS products (
    id            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name          TEXT           NOT NULL,
    variant       TEXT,
    image_url     TEXT,
    description   TEXT,
    hourly_rate   NUMERIC(12, 2) NOT NULL DEFAULT 0 CHECK (hourly_rate >= 0),
    labor_minutes INTEGER        NOT NULL DEFAULT 0 CHECK (labor_minutes >= 0),
    margin_type   TEXT           NOT NULL DEFAULT 'percent' CHECK (margin_type IN ('percent', 'margin', 'fixed')),
    margin_value  NUMERIC(12, 2) NOT NULL CHECK (margin_value >= 0),
    material_cost NUMERIC(12, 2) NOT NULL DEFAULT 0,
    labor_cost    NUMERIC(12, 2) NOT NULL DEFAULT 0,
    total_cost    NUMERIC(12, 2) GENERATED ALWAYS AS (material_cost + labor_cost) STORED,
    sale_price    NUMERIC(12, 2) NOT NULL DEFAULT 0,
    archived_at   TIMESTAMPTZ,
    created_at    TIMESTAMPTZ    NOT NULL DEFAULT now(),
    updated_at    TIMESTAMPTZ    NOT NULL DEFAULT now(),
    -- margem sobre a venda precisa ser < 100% (markup pode passar de 100%)
    CONSTRAINT products_margin_percent_range CHECK (margin_type <> 'margin' OR margin_value < 100)
);
CREATE UNIQUE INDEX IF NOT EXISTS products_identity_unique
    ON products (lower(name), lower(coalesce(variant, '')));

-- Composição do produto (snapshot de custo do momento em que foi salva)
CREATE TABLE IF NOT EXISTS product_materials (
    id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    product_id  BIGINT         NOT NULL REFERENCES products (id) ON DELETE CASCADE,
    material_id BIGINT         NOT NULL REFERENCES materials (id) ON DELETE RESTRICT,
    quantity    NUMERIC(14, 3) NOT NULL CHECK (quantity > 0),
    unit_cost   NUMERIC(18, 6) NOT NULL,
    total_cost  NUMERIC(12, 2) NOT NULL,
    UNIQUE (product_id, material_id)
);
CREATE INDEX IF NOT EXISTS product_materials_material_idx ON product_materials (material_id);

CREATE TABLE IF NOT EXISTS orders (
    id             BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    number         INTEGER        NOT NULL UNIQUE,
    client_id      BIGINT         NOT NULL REFERENCES clients (id) ON DELETE RESTRICT,
    status         TEXT           NOT NULL DEFAULT 'draft'
        CHECK (status IN ('draft', 'confirmed', 'in_production', 'completed', 'cancelled')),
    payment_status TEXT           NOT NULL DEFAULT 'pending'
        CHECK (payment_status IN ('pending', 'partial', 'paid', 'refunded')),
    subtotal       NUMERIC(12, 2) NOT NULL DEFAULT 0,
    discount       NUMERIC(12, 2) NOT NULL DEFAULT 0 CHECK (discount >= 0),
    shipping       NUMERIC(12, 2) NOT NULL DEFAULT 0 CHECK (shipping >= 0),
    cost_total     NUMERIC(12, 2) NOT NULL DEFAULT 0,
    paid_amount    NUMERIC(12, 2) NOT NULL DEFAULT 0 CHECK (paid_amount >= 0),
    notes          VARCHAR(150),
    total          NUMERIC(12, 2) GENERATED ALWAYS AS (subtotal - discount + shipping) STORED,
    created_at     TIMESTAMPTZ    NOT NULL DEFAULT now(),
    updated_at     TIMESTAMPTZ    NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS orders_client_idx ON orders (client_id);
CREATE INDEX IF NOT EXISTS orders_status_idx ON orders (status);

CREATE TABLE IF NOT EXISTS order_items (
    id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    order_id   BIGINT         NOT NULL REFERENCES orders (id) ON DELETE CASCADE,
    product_id BIGINT         NOT NULL REFERENCES products (id) ON DELETE RESTRICT,
    quantity   NUMERIC(14, 3) NOT NULL CHECK (quantity > 0),
    unit_price NUMERIC(12, 2) NOT NULL,
    unit_cost  NUMERIC(12, 2) NOT NULL,
    total      NUMERIC(12, 2) NOT NULL,
    UNIQUE (order_id, product_id)
);
