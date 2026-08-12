-- Kör denna fil en gång i phpMyAdmin innan de nya PHP-filerna laddas upp.
ALTER TABLE recipes ADD COLUMN source_url VARCHAR(2048) NULL AFTER image_path;
