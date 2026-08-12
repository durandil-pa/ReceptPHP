-- Kör denna fil en gång i phpMyAdmin för att aktivera registrering med godkännande.
-- Befintliga användare förblir godkända och kan fortsätta logga in.

ALTER TABLE users
    ADD COLUMN is_approved TINYINT(1) NOT NULL DEFAULT 1 AFTER role;
