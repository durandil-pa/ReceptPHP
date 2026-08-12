-- Kör denna fil en gång i phpMyAdmin för att använda säkra standardroller.
-- Befintliga användares roller ändras inte.

ALTER TABLE users
    MODIFY role VARCHAR(30) NOT NULL DEFAULT 'user';
