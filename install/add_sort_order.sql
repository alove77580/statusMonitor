-- 为monitor_sites表添加排序字段
USE status_monitor;

-- 添加sort_order字段
ALTER TABLE monitor_sites ADD COLUMN sort_order INT DEFAULT 0 COMMENT '排序顺序';

-- 为现有数据设置默认排序（按ID排序）
UPDATE monitor_sites SET sort_order = id;

-- 创建索引以提高排序查询性能
CREATE INDEX idx_sort_order ON monitor_sites(sort_order); 