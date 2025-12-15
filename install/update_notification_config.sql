-- 更新通知配置表，添加企业微信群机器人webhook和通知类型字段
-- 注意：如果字段已存在，执行此SQL会报错，这是正常的，可以忽略

-- 添加企业微信群机器人webhook字段（如果不存在）
-- 如果报错 "Duplicate column name"，说明字段已存在，可以忽略
ALTER TABLE notification_config 
ADD COLUMN wechat_work_webhook VARCHAR(500) COMMENT '企业微信群机器人webhook地址' AFTER bark_key;

-- 添加通知类型字段（如果不存在）
-- 如果报错 "Duplicate column name"，说明字段已存在，可以忽略
ALTER TABLE notification_config 
ADD COLUMN notification_type ENUM('bark', 'wechat_work') DEFAULT 'bark' COMMENT '通知类型：bark或wechat_work' AFTER is_enabled;

-- 如果已有数据，保持notification_type为bark
UPDATE notification_config SET notification_type = 'bark' WHERE notification_type IS NULL;

