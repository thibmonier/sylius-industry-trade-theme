-- Blank project job (US-273): the fixture channel gets the theme, a copy of it without theme_name is the "plain" channel.
-- Sylius picks the channel from the request hostname.
SET @src := (SELECT id FROM sylius_channel ORDER BY id LIMIT 1);
UPDATE sylius_channel
SET hostname = 'themed.localhost', theme_name = 'tmonier/sylius-industry-trade-theme'
WHERE id = @src;

SET @plain := (SELECT MAX(id) + 1 FROM sylius_channel);
CREATE TEMPORARY TABLE plain_channel AS SELECT * FROM sylius_channel WHERE id = @src;
UPDATE plain_channel
SET id = @plain, code = 'PLAIN_WEB', name = 'Plain', hostname = 'plain.localhost', theme_name = NULL,
    shop_billing_data_id = NULL, channel_price_history_config_id = NULL;
INSERT INTO sylius_channel SELECT * FROM plain_channel;
INSERT INTO sylius_channel_locales (channel_id, locale_id)
    SELECT @plain, locale_id FROM sylius_channel_locales WHERE channel_id = @src;
INSERT INTO sylius_channel_currencies (channel_id, currency_id)
    SELECT @plain, currency_id FROM sylius_channel_currencies WHERE channel_id = @src;
