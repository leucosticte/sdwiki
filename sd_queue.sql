CREATE TABLE sd_queue(
-- Primary key
sdq_id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
-- Identifies the wiki we're pushing to
sdq_wiki INT UNSIGNED NOT NULL,
-- page_id, rc_cur_id, rev_page; pageid (from api rc)
sdq_page_id int unsigned NOT NULL,
-- log_title, page_title, rc_title, title (512, because it is prefixed by the namespace)
sdq_page_title varchar(512) binary,
-- rc_this_oldid, rev_id; revid (from api rc)
sdq_rev_id INT UNSIGNED NOT NULL DEFAULT 0
);