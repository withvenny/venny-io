-- app_venny_reactions constraints

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_acknowledgements_attributes_object') THEN
        ALTER TABLE acknowledgements ADD CONSTRAINT ck_acknowledgements_attributes_object CHECK (jsonb_typeof(acknowledgement_attributes) = 'object');
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_comments_attributes_object') THEN
        ALTER TABLE comments ADD CONSTRAINT ck_comments_attributes_object CHECK (jsonb_typeof(comment_attributes) = 'object');
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_acknowledgements_type_nonblank') THEN
        ALTER TABLE acknowledgements ADD CONSTRAINT ck_acknowledgements_type_nonblank CHECK (btrim(acknowledgement_type) <> '');
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_comments_body_nonblank') THEN
        ALTER TABLE comments ADD CONSTRAINT ck_comments_body_nonblank CHECK (btrim(comment_body) <> '');
    END IF;
END $$;
